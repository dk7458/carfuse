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
use Illuminate\Database\Capsule\Manager as Capsule;

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

// ✅ Initialize Eloquent ORM for Database Handling
$capsule = new Capsule;
$capsule->addConnection($config['database']['app_database']);
$capsule->addConnection($config['database']['secure_database'], 'secure');
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Removed direct PDO connection container entries
// $container->set('DB', fn() => $capsule->getConnection());
// $container->set('SecureDB', fn() => $capsule->getConnection('secure'));

$container->set(Capsule::class, $capsule);

// ✅ Register services in the container
$container->set(DocumentQueue::class, function () use ($fileStorage, $logger) {
    return new DocumentQueue($fileStorage, __DIR__ . '/../storage/document_queue.json', $logger);
});

$container->set(Validator::class, fn() => new Validator());
// Provide the Capsule instance directly so services can use Eloquent models.
$container->set(RateLimiter::class, fn() => new RateLimiter($capsule));
$container->set(AuditService::class, fn() => new AuditService($capsule)); 

$container->set(DocumentService::class, function () use ($capsule, $logger, $container) {
    return new DocumentService(
        $capsule, // use Capsule instance for queries via models
        $container->get(AuditService::class),
        $container->get(FileStorage::class),
        $container->get(EncryptionService::class),
        new TemplateService(__DIR__ . '/../storage/templates'),
        $logger
    );
});

$container->set(TokenService::class, fn() => new TokenService($config['encryption']['jwt_secret'], $config['encryption']['jwt_refresh_secret']));
$container->set(NotificationService::class, fn() => new NotificationService($capsule, $logger, $config['notifications']));
$container->set(NotificationQueue::class, fn() => new NotificationQueue($container->get(NotificationService::class), __DIR__ . '/../storage/notification_queue.json', $logger));
$container->set(UserService::class, fn() => new UserService($capsule, $logger, $config['encryption']['jwt_secret']));

$container->set(Payment::class, fn() => new Payment());

$container->set(PaymentService::class, function () use ($capsule, $logger, $config) {
    return new PaymentService($capsule, $logger, new Payment(), $config['payu']['api_key'], $config['payu']['api_secret']);
});

$container->set(PayUService::class, fn() => new PayUService(new Client(), $logger, $config['payu']));
$container->set(BookingService::class, fn() => new BookingService($capsule, $logger));
$container->set(MetricsService::class, fn() => new MetricsService($capsule));
$container->set(ReportService::class, fn() => new ReportService($capsule));
$container->set(RevenueService::class, fn() => new RevenueService($capsule));

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
