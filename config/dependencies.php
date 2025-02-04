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
use DocumentManager\Services\FileStorage;
use DocumentManager\Services\TemplateService;
use App\Services\EncryptionService;
use AuditManager\Services\AuditService;
use App\Models\PaymentModel;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use App\Services\PayUService;
use GuzzleHttp\Client;

// Load all configuration files from the config directory (excluding dependencies.php)
$configDirectory = __DIR__;
$config = [];

foreach (glob("{$configDirectory}/*.php") as $filePath) {
    $fileName = basename($filePath, '.php');

    // Exclude dependencies.php to prevent recursive loading
    if ($fileName === 'dependencies') {
        continue;
    }

    $config[$fileName] = require $filePath;
}


$templateDirectory = __DIR__ . '/../storage/templates';

// Ensure the directory exists
if (!is_dir($templateDirectory)) {
    mkdir($templateDirectory, 0775, true);
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

// Ensure Encryption Key Exists
if (!isset($config['encryption']['encryption_key']) || strlen($config['encryption']['encryption_key']) < 32) {
    throw new RuntimeException("❌ Encryption key must be at least 32 characters long. Check config/encryption.php.");
}

// Ensure File Storage Path Exists
$fileStorageConfig = ['base_directory' => __DIR__ . '/../storage/documents'];

if (!is_dir($fileStorageConfig['base_directory'])) {
    mkdir($fileStorageConfig['base_directory'], 0775, true);
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
    FileStorage::class => new FileStorage($fileStorageConfig, $logger),
    TemplateService::class => new TemplateService($templateDirectory),
    DocumentService::class => new DocumentService(
        $securePdo,
        new AuditService($securePdo),
        new FileStorage($fileStorageConfig, $logger),
        new EncryptionService(),
        new TemplateService($templateDirectory),
        $logger 
    ),

    TokenService::class => new TokenService($config['encryption']['encryption_key']),
    NotificationQueue::class => new NotificationQueue(new NotificationService($pdo, $logger, $config['notifications']), __DIR__ . '/../storage/notification_queue.json'),
    NotificationService::class => new NotificationService(
        $pdo,
        $logger,
        $config['notifications'] // ✅ Add the missing config array
    ),
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
        new RateLimiter($pdo),
        new AuditService($securePdo),
        new NotificationService($pdo, $logger)
    )
];
