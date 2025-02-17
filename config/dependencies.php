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
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use GuzzleHttp\Client;

// Initialize PHP-DI container.
$container = new Container();

// Load configuration files.
$configDirectory = __DIR__;
$config = [];
foreach (glob("{$configDirectory}/*.php") as $filePath) {
    $fileName = basename($filePath, '.php');
    if ($fileName !== 'dependencies') {
        $config[$fileName] = require $filePath;
    }
}

// Ensure required directories exist.
$templateDirectory = __DIR__ . '/../storage/templates';
$fileStorageConfig = $config['filestorage'] ?? [];
if (!is_dir($templateDirectory)) {
    mkdir($templateDirectory, 0775, true);
}
if (!empty($fileStorageConfig['base_directory']) && !is_dir($fileStorageConfig['base_directory'])) {
    mkdir($fileStorageConfig['base_directory'], 0775, true);
}

// Initialize Logger (Monolog) from logger.php.
$logger = require_once __DIR__ . '/../logger.php';
$container->set(LoggerInterface::class, $logger);

// Initialize EncryptionService.
$encryptionService = new EncryptionService($config['encryption']['encryption_key'] ?? '');
$container->set(EncryptionService::class, $encryptionService);

// Initialize FileStorage.
$fileStorage = new FileStorage($fileStorageConfig, $logger, $encryptionService);
$container->set(FileStorage::class, $fileStorage);

// Load key manager configuration.
$config['keymanager'] = require __DIR__ . '/keymanager.php';

// Initialize DatabaseHelper instances.
try {
    $database = DatabaseHelper::getInstance();
    $secure_database = DatabaseHelper::getSecureInstance();
    $logger->info("✅ Both databases initialized successfully.");
} catch (Exception $e) {
    $logger->error("❌ Database initialization failed: " . $e->getMessage());
    die("❌ Database initialization failed. Check logs for details.\n");
}
$container->set('db', $database);
$container->set('secure_db', $secure_database);

// Register Services.
$container->set(Validator::class, fn() => new Validator($container->get(Psr\Log\LoggerInterface::class)));
$container->set(RateLimiter::class, new RateLimiter($database));
$container->set(AuditService::class, new AuditService($logger));
$container->set(TokenService::class, new TokenService(
    getenv('JWT_SECRET') ?: '',
    getenv('JWT_REFRESH_SECRET') ?: '',
    $logger
));
$container->set(AuthService::class, new AuthService($logger));
$container->set(NotificationService::class, new NotificationService($database, $logger, $config['notifications'] ?? []));
$container->set(NotificationQueue::class, new NotificationQueue($container->get(NotificationService::class), __DIR__ . '/../storage/notification_queue.json', $logger));
$container->set(UserService::class, new UserService($logger, $config['encryption']['jwt_secret'] ?? ''));
$container->set(Payment::class, new Payment());
$container->set(PaymentService::class, new PaymentService(
    $database,
    $logger,
    new Payment(),
    getenv('PAYU_API_KEY') ?: '',
    getenv('PAYU_API_SECRET') ?: ''
));
$container->set(BookingService::class, new BookingService($database, $logger));
$container->set(MetricsService::class, new MetricsService($database));
$container->set(ReportService::class, new ReportService($database));
$container->set(RevenueService::class, new RevenueService($database));
$container->set(SignatureService::class, new SignatureService(
    new Client(),
    [],
    $fileStorage,
    $encryptionService,
    $logger
));
$container->set(TemplateService::class, new TemplateService(__DIR__ . '/../storage/templates'));
$container->set(KeyManager::class, new KeyManager($config['keymanager']['keys'] ?? [], $logger));

// Return the DI container.
return $container;
