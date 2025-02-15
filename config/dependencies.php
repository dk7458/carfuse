<?php
// Bootstrap Laravel's Container for Facades
use Illuminate\Container\Container as LaravelContainer;
use Illuminate\Support\Facades\Facade;
$laravelContainer = new LaravelContainer();
LaravelContainer::setInstance($laravelContainer);
Facade::setFacadeApplication($laravelContainer);

// Explicitly bind configuration and SessionManager before session functions are used
use Illuminate\Config\Repository as Config;
use Illuminate\Session\SessionManager;
$sessionConfig = [
    'driver'         => 'file',
    'files'          => __DIR__ . '/../storage/framework/sessions',
    'lifetime'       => 120,
    'expire_on_close'=> false,
    'encrypt'        => false,
    'cookie'         => 'carfuse_session',
    'path'           => '/',
    'secure'         => false, // Change to true in production
    'http_only'      => true,
    'same_site'      => 'lax',
];
$laravelContainer->bind('config', fn() => new Config(['session' => $sessionConfig]));
$laravelContainer->singleton(SessionManager::class, fn($app) => new SessionManager($app));
$laravelContainer->alias(SessionManager::class, 'session');

require_once __DIR__ . '/../vendor/autoload.php'; // ✅ Ensure autoload is included
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php'; // Include once

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

// ✅ Initialize Dependency Container
$container = new DIContainer();

// ✅ Load configuration from `config/` directory
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
$fileStorageConfig = $config['filestorage'] ?? [];

foreach ([$templateDirectory, $fileStorageConfig['base_directory'] ?? null] as $directory) {
    if ($directory && !is_dir($directory)) {
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

// Update LoggerInterface registration if needed
$container->set(LoggerInterface::class, fn() => $logger);

// ✅ Initialize Encryption Service
$encryptionService = new EncryptionService($config['encryption']['encryption_key'] ?? '');
$container->set(EncryptionService::class, fn() => $encryptionService);

// ✅ Initialize File Storage Before Using It Anywhere
$fileStorage = new FileStorage($fileStorageConfig, $logger, $encryptionService);
$container->set(FileStorage::class, fn() => $fileStorage);
$config['keymanager'] = require __DIR__ . '/keymanager.php';

// ✅ Initialize Databases Using DatabaseHelper
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

// ✅ Register Session Handling
$container->set(SessionManager::class, function () use ($sessionConfig) {
    return new SessionManager(new Config(['session' => $sessionConfig]));
});

// Bind the "session" target so that dependencies expecting it resolve correctly.
$container->set('session', fn() => $container->get(SessionManager::class)->driver());

// ✅ Bind Session Facade
$container->set(Session::class, fn() => $container->get(SessionManager::class)->driver());

// ✅ Register Services
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
    $config['encryption']['jwt_secret'] ?? '',
    $config['encryption']['jwt_refresh_secret'] ?? '',
    $container->get(LoggerInterface::class)
));

$container->set(NotificationService::class, fn() => new NotificationService($database, $logger, $config['notifications'] ?? []));
$container->set(NotificationQueue::class, fn() => new NotificationQueue($container->get(NotificationService::class), __DIR__ . '/../storage/notification_queue.json', $logger));

$container->set(UserService::class, fn() => new UserService(
    $container->get(LoggerInterface::class),
    $config['encryption']['jwt_secret'] ?? ''
));

$container->set(Payment::class, fn() => new Payment());

$container->set(PaymentService::class, function () use ($database, $logger, $config) {
    return new PaymentService($database, $logger, new Payment(), $config['payu']['api_key'] ?? '', $config['payu']['api_secret'] ?? '');
});

$container->set(PayUService::class, fn() => new PayUService(new Client(), $logger, $config['payu'] ?? []));
$container->set(BookingService::class, fn() => new BookingService($database, $logger));
$container->set(MetricsService::class, fn() => new MetricsService($database));
$container->set(ReportService::class, fn() => new ReportService($database));
$container->set(RevenueService::class, fn() => new RevenueService($database));

$container->set(SignatureService::class, function () use ($config, $container) {
    return new SignatureService(
        new Client(),
        $config['signature'] ?? [],
        $container->get(FileStorage::class),
        $container->get(EncryptionService::class),
        $container->get(LoggerInterface::class)
    );
});

$container->set(TemplateService::class, fn() => new TemplateService(__DIR__ . '/../storage/templates'));

$container->set(KeyManager::class, fn() => new KeyManager($config['keymanager']['keys'] ?? [], $logger));

$container->set(AuthService::class, fn() => new AuthService($container->get(LoggerInterface::class)));

// ✅ Return the DI container
return $container;
