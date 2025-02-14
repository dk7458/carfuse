<?php

/**
 * Centralized Bootstrap File
 * Path: bootstrap.php
 *
 * Initializes database connections, logging, encryption, and registers necessary services.
 */

use DI\Container;
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
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Session\SessionManager;
use Illuminate\Session\Store;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Config\Repository as Config;
use Illuminate\Cookie\CookieJar;
use Illuminate\Support\Facades\Facade;

// ✅ Load Dependencies
require_once __DIR__ . '/../vendor/autoload.php';
define('BASE_PATH', __DIR__);

// ✅ Initialize Dependency Container (PHP-DI)
$container = new Container();
Facade::setFacadeApplication($container);

// ✅ Load Configuration Files
$configFiles = ['encryption', 'keymanager', 'filestorage'];
$config = [];

foreach ($configFiles as $file) {
    $path = BASE_PATH . "/config/{$file}.php";
    if (!file_exists($path)) {
        die("❌ Error: Missing required configuration file: {$file}.php\n");
    }
    $config[$file] = require $path;
}

// ✅ Ensure Necessary Directories Exist
$templateDirectory = BASE_PATH . '/../storage/templates';
$fileStorageConfig = $config['filestorage'];

foreach ([$templateDirectory, $fileStorageConfig['base_directory']] as $directory) {
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

// ✅ Initialize Logger
$logger = new Logger('carfuse');
$logFile = BASE_PATH . '/../logs/app.log';

if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0775, true);
}

$streamHandler = new StreamHandler($logFile, Logger::DEBUG);
$formatter = new LineFormatter(null, null, true, true);
$streamHandler->setFormatter($formatter);
$logger->pushHandler($streamHandler);
$container->set(LoggerInterface::class, fn() => $logger);

// ✅ Initialize Encryption Service
$encryptionService = new EncryptionService($config['encryption']['encryption_key']);
$container->set(EncryptionService::class, fn() => $encryptionService);

// ✅ Initialize File Storage
$fileStorage = new FileStorage($fileStorageConfig, $logger, $encryptionService);
$container->set(FileStorage::class, fn() => $fileStorage);
$config['keymanager'] = require BASE_PATH . '/config/keymanager.php';

// ✅ Initialize Eloquent ORM for Database Handling
$capsule = new Capsule;
$capsule->addConnection($config['database']['app_database']);
$capsule->addConnection($config['database']['secure_database'], 'secure');
$capsule->setAsGlobal();
$capsule->bootEloquent();

$container->set(Capsule::class, fn() => $capsule);

// ✅ Initialize Session Manager
$container->set(SessionManager::class, function () use ($container) {
    $config = [
        'driver' => 'file',
        'files' => BASE_PATH . '/../storage/framework/sessions',
        'lifetime' => 120,
        'expire_on_close' => false,
        'encrypt' => false,
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => 'carfuse_session',
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'http_only' => true,
        'same_site' => 'lax',
    ];
    return new SessionManager(new Config(['session' => $config]));
});
$container->set(Store::class, fn() => $container->get(SessionManager::class)->driver());

// ✅ Register Services in the Container
$container->set(DocumentQueue::class, function () use ($fileStorage, $logger) {
    return new DocumentQueue($fileStorage, BASE_PATH . '/../storage/document_queue.json', $logger);
});

$container->set(Validator::class, fn() => new Validator());
$container->set(RateLimiter::class, fn() => new RateLimiter($capsule));
$container->set(AuditService::class, fn() => new AuditService($capsule));

$container->set(DocumentService::class, function () use ($capsule, $logger, $container) {
    return new DocumentService(
        $capsule,
        $container->get(AuditService::class),
        $container->get(FileStorage::class),
        $container->get(EncryptionService::class),
        new TemplateService(BASE_PATH . '/../storage/templates'),
        $logger
    );
});

$container->set(TokenService::class, fn() => new TokenService($config['encryption']['jwt_secret'], $config['encryption']['jwt_refresh_secret']));
$container->set(NotificationService::class, fn() => new NotificationService($capsule, $logger, $config['notifications']));
$container->set(NotificationQueue::class, fn() => new NotificationQueue($container->get(NotificationService::class), BASE_PATH . '/../storage/notification_queue.json', $logger));
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

$container->set(TemplateService::class, fn() => new TemplateService(BASE_PATH . '/../storage/templates'));

$container->set(KeyManager::class, function () use ($config, $logger) {
    return new KeyManager($config['keymanager']['keys'], $logger);
});

// ✅ Return the DI container
return $container;
d