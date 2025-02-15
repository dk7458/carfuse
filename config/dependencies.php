<?php
// Load environment variables via Dotenv instead of using Illuminate\Config\Repository
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Bootstrap Laravel's Container for Facades
use Illuminate\Container\Container as LaravelContainer;
use Illuminate\Support\Facades\Facade;
$laravelContainer = new LaravelContainer();
LaravelContainer::setInstance($laravelContainer);
Facade::setFacadeApplication($laravelContainer);

// Build a basic config binding using Dotenv values for session configuration.
$sessionConfig = [
    'driver'          => 'file',
    'files'           => __DIR__ . '/../storage/framework/sessions',
    'lifetime'        => getenv('SESSION_LIFETIME') ?: 120,
    'expire_on_close' => getenv('SESSION_EXPIRE_ON_CLOSE') ?: false,
    'encrypt'         => getenv('SESSION_ENCRYPT') ?: false,
    'cookie'          => getenv('SESSION_COOKIE') ?: 'carfuse_session',
    'path'            => getenv('SESSION_PATH') ?: '/',
    'secure'          => getenv('SESSION_SECURE') ?: false,
    'http_only'       => getenv('SESSION_HTTP_ONLY') ?: true,
    'same_site'       => getenv('SESSION_SAME_SITE') ?: 'lax',
];
$laravelContainer->bind('config', fn() => ['session' => $sessionConfig]);
use Illuminate\Session\SessionManager;
$laravelContainer->singleton(SessionManager::class, fn($app) => new SessionManager($app));
$laravelContainer->alias(SessionManager::class, 'session');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php'; // Include only once

use DI\Container as DIContainer;
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
use App\Services\TransactionService;
use App\Models\Payment;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use App\Services\PayUService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Session;

// Initialize DI container
$container = new DIContainer();

// Load configuration from `config/` directory
$configDirectory = __DIR__;
$config = [];

foreach (glob("{$configDirectory}/*.php") as $filePath) {
    $fileName = basename($filePath, '.php');
    if ($fileName !== 'dependencies') {
        $config[$fileName] = require $filePath;
    }
}

// Ensure necessary directories exist
$templateDirectory = __DIR__ . '/../storage/templates';
$fileStorageConfig = $config['filestorage'] ?? [];

foreach ([$templateDirectory, $fileStorageConfig['base_directory'] ?? null] as $directory) {
    if ($directory && !is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

// Load Logger from logger.php and bind LoggerInterface
$logger = require_once __DIR__ . '/../logger.php';
$container->set(LoggerInterface::class, fn() => $logger);

// Initialize Encryption Service
$encryptionService = new EncryptionService($config['encryption']['encryption_key'] ?? '');
$container->set(EncryptionService::class, fn() => $encryptionService);

// Initialize File Storage Before Using It Anywhere
$fileStorage = new FileStorage($fileStorageConfig, $logger, $encryptionService);
$container->set(FileStorage::class, fn() => $fileStorage);
$config['keymanager'] = require __DIR__ . '/keymanager.php';

// Use DatabaseHelper for all database operations
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

// Bind SessionManager via the Laravel Container and register session binding in DI
$container->set(SessionManager::class, fn() => $laravelContainer->make(SessionManager::class));
$container->set('session', fn() => $container->get(SessionManager::class)->driver());
// Optionally, bind the Session facade if needed:
$container->set(Session::class, fn() => $container->get(SessionManager::class)->driver());

// Register Services
$container->set(DocumentQueue::class, fn() => new DocumentQueue($fileStorage, __DIR__ . '/../storage/document_queue.json', $logger));
$container->set(Validator::class, fn() => new Validator());
$container->set(RateLimiter::class, fn() => new RateLimiter($database));

// Updated service registrations with dependency injection for Logger and other dependencies
$container->set(AuditService::class, fn() => new AuditService($logger));

$container->set(DocumentService::class, function () use ($database, $logger, $container) {
    return new DocumentService(
        $container->get(AuditService::class),
        $container->get(FileStorage::class),
        $container->get(EncryptionService::class),
        new TemplateService(__DIR__ . '/../storage/templates'),
        $logger
    );
});

$container->set(TokenService::class, fn() => new TokenService(
    getenv('JWT_SECRET') ?: '',
    getenv('JWT_REFRESH_SECRET') ?: '',
    $container->get(LoggerInterface::class)
));

$container->set(NotificationService::class, fn() => new NotificationService($database, $logger, $config['notifications'] ?? []));
$container->set(NotificationQueue::class, fn() => new NotificationQueue($container->get(NotificationService::class), __DIR__ . '/../storage/notification_queue.json', $logger));

$container->set(UserService::class, fn() => new UserService(
    $container->get(LoggerInterface::class),
    $config['encryption']['jwt_secret'] ?? ''
));

$container->set(Payment::class, fn() => new Payment());

$container->set(PaymentService::class, function () use ($database, $logger) {
    return new PaymentService(
        $database,
        $logger,
        new Payment(),
        getenv('PAYU_API_KEY') ?: '',
        getenv('PAYU_API_SECRET') ?: ''
    );
});

$container->set(PayUService::class, fn() => new PayUService(new Client(), $logger, $config['payu'] ?? []));
$container->set(BookingService::class, fn() => new BookingService($database, $logger));
$container->set(MetricsService::class, fn() => new MetricsService($database));
$container->set(ReportService::class, fn() => new ReportService($database));
$container->set(RevenueService::class, fn() => new RevenueService($database));

$container->set(SignatureService::class, function () use ($container) {
    return new SignatureService(
        new Client(),
        [], // Assuming additional signature config is not used
        $container->get(FileStorage::class),
        $container->get(EncryptionService::class),
        $container->get(LoggerInterface::class)
    );
});

$container->set(TemplateService::class, fn() => new TemplateService(__DIR__ . '/../storage/templates'));

$container->set(KeyManager::class, fn() => new KeyManager($config['keymanager']['keys'] ?? [], $logger));

$container->set(AuthService::class, fn() => new AuthService($container->get(LoggerInterface::class)));

// Return the DI container
return $container;
