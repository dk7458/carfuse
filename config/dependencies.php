<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use App\Helpers\DatabaseHelper;
use App\Services\Validator;
use App\Services\RateLimiter;
use App\Services\Auth\TokenService;
use App\Services\Auth\AuthService;
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
use App\Services\DocumentService;
use App\Services\FileStorage;
use App\Services\TemplateService;
use App\Services\SignatureService;
use App\Services\AuditService;
use App\Models\Payment;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;

// ✅ Initialize PHP-DI container.
$container = new Container();

// ✅ Load configuration files.
$configDirectory = __DIR__;
$config = [];
foreach (glob("{$configDirectory}/*.php") as $filePath) {
    $fileName = basename($filePath, '.php');
    if ($fileName !== 'dependencies') {
        $config[$fileName] = require $filePath;
    }
}

// ✅ Ensure required directories exist.
$templateDirectory = __DIR__ . '/../storage/templates';
$fileStorageConfig = $config['filestorage'] ?? [];
if (!is_dir($templateDirectory)) {
    mkdir($templateDirectory, 0775, true);
}
if (!empty($fileStorageConfig['base_directory']) && !is_dir($fileStorageConfig['base_directory'])) {
    mkdir($fileStorageConfig['base_directory'], 0775, true);
}

// ✅ Initialize Logger (Monolog) from logger.php.
$logger = require_once __DIR__ . '/../logger.php';
$container->set(LoggerInterface::class, fn() => $logger);

// ✅ Initialize EncryptionService.
$encryptionService = new EncryptionService($config['encryption']['encryption_key'] ?? '');
$container->set(EncryptionService::class, fn() => $encryptionService);

// ✅ Initialize FileStorage.
$fileStorage = new FileStorage($fileStorageConfig, $logger, $encryptionService);
$container->set(FileStorage::class, fn() => $fileStorage);

// ✅ Load key manager configuration.
$config['keymanager'] = require __DIR__ . '/keymanager.php';

// ✅ Initialize DatabaseHelper instances.
try {
    $database = DatabaseHelper::getInstance();
    $secure_database = DatabaseHelper::getSecureInstance();
    $logger->info("✅ Both databases initialized successfully.");
} catch (Exception $e) {
    $logger->error("❌ Database initialization failed: " . $e->getMessage());
    die("❌ Database initialization failed. Check logs for details.\n");
}
$container->set('db', fn() => $database);
$container->set('secure_db', fn() => $secure_database);

// ✅ Register Services with logger as the first parameter.
$container->set(Validator::class, fn() => new Validator($logger));
$container->set(RateLimiter::class, fn() => new RateLimiter($logger, $database));
$container->set(AuditService::class, fn() => new AuditService($logger));
$container->set(TokenService::class, fn() => new TokenService($_ENV['JWT_SECRET'] ?? '', $_ENV['JWT_REFRESH_SECRET'] ?? '', $logger));
$container->set(NotificationService::class, fn() => new NotificationService($logger, $config['notifications'] ?? [], $database));
$container->set(UserService::class, fn() => new UserService($logger, $database, $config['encryption']['jwt_secret'] ?? ''));
$container->set(PaymentService::class, fn() => new PaymentService($logger, $database, new Payment(), getenv('PAYU_API_KEY') ?: '', getenv('PAYU_API_SECRET') ?: ''));
$container->set(BookingService::class, fn() => new BookingService($logger, $database));
$container->set(MetricsService::class, fn() => new MetricsService($logger, $database));
$container->set(ReportService::class, fn() => new ReportService($logger, $database));
$container->set(RevenueService::class, fn() => new RevenueService($logger, $database));

// ✅ For SignatureService, pass an empty array as the $config.
$container->set(SignatureService::class, fn() => new SignatureService($config['signature'], $fileStorage, $encryptionService, $logger));

// ✅ Register DocumentService.
$container->set(DocumentService::class, fn() => new DocumentService(
    $logger,
    $container->get(AuditService::class),
    $fileStorage,
    $encryptionService,
    $container->get(TemplateService::class)
));

// ✅ Register AuthService.
$container->set(AuthService::class, fn() => new AuthService($logger, $database, $config['encryption']));

// ✅ Register KeyManager.
$container->set(KeyManager::class, fn() => new KeyManager($logger, $config['keymanager']['keys'] ?? []));

// ✅ Return the DI container.
return $container;
