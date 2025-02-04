<?php

use App\Services\Validator;
use App\Services\RateLimiter;
use App\Services\Auth\TokenService;
use App\Services\NotificationService;
use App\Services\UserService;
use App\Services\PaymentService;
use App\Controllers\UserController;
use App\Queues\NotificationQueue;
use DocumentManager\Services\DocumentService;
use App\Services\EncryptionService;
use DocumentManager\Services\FileStorage;
use AuditManager\Services\AuditService;
use App\Models\PaymentModel;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use App\Services\PayUService;
use GuzzleHttp\Client;

// Load Configuration
$configFiles = ['database', 'encryption'];
$config = [];

foreach ($configFiles as $file) {
    $path = __DIR__ . "/{$file}.php";
    if (!file_exists($path)) {
        throw new RuntimeException("❌ Missing configuration file: {$file}.php");
    }
    $config[$file] = require $path;
}

// Initialize Logger
try {
    $logger = new Logger('carfuse');
    $logFile = __DIR__ . '/../logs/app.log';

    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0775, true);
    }

    $streamHandler = new StreamHandler($logFile, Logger::DEBUG);
    $formatter = new LineFormatter(null, null, true, true);
    $streamHandler->setFormatter($formatter);
    $logger->pushHandler($streamHandler);
} catch (Exception $e) {
    throw new RuntimeException("❌ Logger setup failed: " . $e->getMessage());
}

// Initialize Database Connections
try {
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['database']['app_database']['host'],
            $config['database']['app_database']['database']
        ),
        $config['database']['app_database']['username'],
        $config['database']['app_database']['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $securePdo = new PDO(
        sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['database']['secure_database']['host'],
            $config['database']['secure_database']['database']
        ),
        $config['database']['secure_database']['username'],
        $config['database']['secure_database']['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    throw new RuntimeException("❌ Database connection failed: " . $e->getMessage());
}

// Initialize Services
return [
    PDO::class => $pdo,
    'SecurePDO' => $securePdo,
    LoggerInterface::class => $logger,
    Validator::class => new Validator(),
    RateLimiter::class => new RateLimiter($pdo),
    AuditService::class => new AuditService($securePdo),
    EncryptionService::class => new EncryptionService(),
    FileStorage::class => new FileStorage([
        'base_directory' => '../storage/documents'
    ], $logger),
    
    DocumentService::class => new DocumentService(
        $securePdo,
        new AuditService($securePdo),
        new FileStorage(__DIR__ . '/../storage/documents'),
        new EncryptionService()
    ),
    TokenService::class => new TokenService($config['encryption']['encryption_key']),
    NotificationQueue::class => new NotificationQueue(new NotificationService($pdo, $logger), __DIR__ . '/../storage/notification_queue.json'),
    NotificationService::class => new NotificationService($pdo, $logger),
    UserService::class => new UserService($securePdo, $logger),
    PaymentModel::class => new PaymentModel($pdo),
    PaymentService::class => new PaymentService($pdo, $logger, new PaymentModel($pdo)),
    PayUService::class => new PayUService(new Client(), $logger, [
        'merchant_key' => $config['encryption']['payu_merchant_key'],
        'merchant_salt' => $config['encryption']['payu_merchant_salt'],
        'endpoint' => $config['encryption']['payu_api_endpoint'],
    ]),
    UserController::class => new UserController(
        $pdo,
        $securePdo,
        $logger,
        ['jwt_secret' => $config['encryption']['encryption_key']],
        new Validator(),
        new RateLimiter(5, 900),
        new AuditService($securePdo),
        new NotificationService($pdo, $logger)
    )
];
