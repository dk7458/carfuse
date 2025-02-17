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

// Register Services with logger as the first parameter.
$container->set(Validator::class, fn() => new Validator($container->get(Psr\Log\LoggerInterface::class)));

$container->set(RateLimiter::class, new RateLimiter($logger, $database));

$container->set(AuditService::class, new AuditService($logger));

// Reorder parameters: logger comes first.
$container->set(TokenService::class, new TokenService(
    $_ENV['JWT_SECRET'] ?: '',
    $_ENV['JWT_REFRESH_SECRET'] ?: '',
    $logger
));

// NotificationService already had logger first.
$container->set(NotificationService::class, new NotificationService($logger, $config['notifications'] ?? [], $database));

// Ensure logger first.
$container->set(UserService::class, new UserService($logger, $config['encryption']['jwt_secret'] ?? ''));

// PaymentService: logger, then db, then other dependencies.
$container->set(PaymentService::class, new PaymentService(
    $logger,
    $database,
    new Payment(),
    getenv('PAYU_API_KEY') ?: '',
    getenv('PAYU_API_SECRET') ?: ''
));

// BookingService: updated to pass logger first.
$container->set(BookingService::class, new BookingService($logger, $database));

// MetricsService: add logger as first argument.
$container->set(MetricsService::class, new MetricsService($logger, $database));

// ReportService: add logger as first argument.
$container->set(ReportService::class, new ReportService($logger, $database));

// RevenueService: add logger as first argument.
$container->set(RevenueService::class, new RevenueService($logger, $database));

// For SignatureService, pass an empty array as the $config.
$container->set(SignatureService::class, new SignatureService(
    [],             // $config array
    $fileStorage,   // fileStorage should be second
    $encryptionService, // third parameter (EncryptionService instance)
    $logger         // fourth parameter (logger)
));

// TemplateService: now include logger first.
$container->set(TemplateService::class, new TemplateService($logger, __DIR__ . '/../storage/templates'));

// KeyManager: update to pass logger as first parameter.
$container->set(KeyManager::class, new KeyManager($logger, $config['keymanager']['keys'] ?? []));

// Return the DI container.
return $container;
