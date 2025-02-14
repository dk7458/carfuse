<?php

/**
 * Dependency Injection Configuration
 * 
 * This file initializes and registers all application dependencies,
 * including services, database connections, logging, encryption, and queue management.
 * 
 * Path: config/dependencies.php
 */

require_once __DIR__ . '/../vendor/autoload.php'; // ✅ Ensure autoload is included

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
use Illuminate\Config\Repository as Config;
use Illuminate\Cookie\CookieJar;
use Illuminate\Support\Facades\Session;

// ✅ Initialize Dependency Container
$container = new Container();

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
$fileStorageConfig = $config['filestorage'];

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

$container->set(LoggerInterface::class, fn() => $logger);

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
$container->set(Capsule::class, fn() => $capsule);

// ✅ Register Session Handling
$container->set(SessionManager::class, function () use ($container) {
    $config = [
        'driver' => 'file',
        'files' => __DIR__ . '/../storage/framework/sessions',
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

// Bind Session Facade
$container->set(Session::class, fn() => $container->get(SessionManager::class)->driver());

// ✅ Register Services
$container->set(DocumentQueue::class, fn() => new DocumentQueue($fileStorage, __DIR__ . '/../storage/document_queue.json', $logger));
$container->set(Validator::class, fn() => new Validator());
$container->set(RateLimiter::class, fn() => new RateLimiter($capsule));
$container->set(AuditService::class, fn() => new AuditService($capsule));

$container->set(DocumentService::class, function () use ($capsule, $logger, $container) {
    return new DocumentService(
        $capsule,
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

$container->set(KeyManager::class, fn() => new KeyManager($config['keymanager']['keys'], $logger));

// ✅ Return the DI container
return $container;
