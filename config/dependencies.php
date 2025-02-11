<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Ensure autoload is included

use DI\Container;
use App\Services\Validator;
use App\Services\RateLimiter;
use App\Services\Auth\TokenService;
use App\Services\NotificationService;
use App\Services\UserService;
use App\Services\PaymentService;
use App\Services\BookingService;
use App\Services\MetricsService;
use App\Services\ReportService;
use App\Services\RevenueService;
use App\Services\EncryptionService;
use App\Services\Security\KeyManager;
use App\Queues\NotificationQueue;
use App\Queues\DocumentQueue;
use DocumentManager\Services\DocumentService;
use DocumentManager\Services\FileStorage;
use DocumentManager\Services\TemplateService;
use DocumentManager\Services\SignatureService;
use AuditManager\Services\AuditService;
use App\Models\Payment;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use App\Services\PayUService;
use GuzzleHttp\Client;

// ✅ Initialize Dependency Container
$container = new Container();

// ✅ Load configuration
$configDirectory = __DIR__;
$config = [];

foreach (glob("{$configDirectory}/*.php") as $filePath) {
    $fileName = basename($filePath, '.php');
    if ($fileName !== 'dependencies') {
        $config[$fileName] = require $filePath;
    }
}

// ✅ Ensure necessary directories exist
$templateDirectory = __DIR__ . '/../storage/templates';
$fileStorageConfig = $config['storage'];

foreach ([$templateDirectory, $fileStorageConfig['base_directory']] as $directory) {
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

// ✅ Initialize Logger First
$logger = new Logger('carfuse');
$logFile = __DIR__ . '/../logs/app.log';

if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0775, true);
}

$streamHandler = new StreamHandler($logFile, Logger::DEBUG);
$formatter = new LineFormatter(null, null, true, true);
$streamHandler->setFormatter($formatter);
$logger->pushHandler($streamHandler);

$container->set(LoggerInterface::class, $logger);

// ✅ Initialize Encryption Service
$encryptionService = new EncryptionService($config['encryption']['encryption_key']);
$container->set(EncryptionService::class, fn() => $encryptionService);

// ✅ Initialize File Storage Before Using It Anywhere
$fileStorage = new FileStorage($fileStorageConfig, $logger, $encryptionService);
$container->set(FileStorage::class, fn() => $fileStorage);
$config['keymanager'] = require __DIR__ . '/keymanager.php';

// ✅ Initialize PDO Instances
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['database']['app_database']['host'],
            $config['database']['app_database']['database']
        ),
        $config['database']['app_database']['username'],
        $config['database']['app_database']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $securePdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['database']['secure_database']['host'],
            $config['database']['secure_database']['database']
        ),
        $config['database']['secure_database']['username'],
        $config['database']['secure_database']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    throw new RuntimeException("❌ Database connection failed: " . $e->getMessage());
}

// ✅ Register services in the container
$container->set(PDO::class, $pdo);
$container->set('SecurePDO', $securePdo);

$container->set(DocumentQueue::class, function () use ($fileStorage, $logger) {
    return new DocumentQueue($fileStorage, __DIR__ . '/../storage/document_queue.json', $logger);
});

$container->set(Validator::class, fn() => new Validator());
$container->set(RateLimiter::class, fn() => new RateLimiter($pdo));
$container->set(AuditService::class, fn() => new AuditService($securePdo));

$container->set(DocumentService::class, function () use ($pdo, $logger, $container) {
    return new DocumentService(
        $pdo,
        $container->get(AuditService::class),
        $container->get(FileStorage::class),
        $container->get(EncryptionService::class),
        new TemplateService(__DIR__ . '/../storage/templates'),
        $logger
    );
});

$container->set(TokenService::class, fn() => new TokenService($config['encryption']['jwt_secret'], $config['encryption']['jwt_refresh_secret']));
$container->set(NotificationService::class, fn() => new NotificationService($pdo, $logger, $config['notifications']));
$container->set(NotificationQueue::class, fn() => new NotificationQueue($container->get(NotificationService::class), __DIR__ . '/../storage/notification_queue.json', $logger));
$container->set(UserService::class, fn() => new UserService($securePdo, $logger, $config['encryption']['jwt_secret']));
$container->set(Payment::class, fn() => new Payment());

$container->set(PaymentService::class, function () use ($pdo, $logger, $config) {
    return new PaymentService($pdo, $logger, new Payment(), $config['payu']['api_key'], $config['payu']['api_secret']);
});

$container->set(PayUService::class, fn() => new PayUService(new Client(), $logger, $config['payu']));
$container->set(BookingService::class, fn() => new BookingService($pdo, $logger));
$container->set(MetricsService::class, fn() => new MetricsService($pdo));
$container->set(ReportService::class, fn() => new ReportService($pdo));
$container->set(RevenueService::class, fn() => new RevenueService($pdo));

$container->set(SignatureService::class, function () use ($config, $container) {
    return new SignatureService(
        new Client(),
        $config['signature'],
        $container->get(FileStorage::class),
        $container->get(EncryptionService::class),
        $container->get(LoggerInterface::class)
    );
});

$container->set(TemplateService::class, fn() => new TemplateService(__DIR__ . '/../storage/templates'));

$container->set(KeyManager::class, function () use ($config, $logger) {
    return new KeyManager($config['keymanager']['keys'], $logger);
});

// ✅ Return the DI container
return $container;
