<?php

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
use App\Controllers\UserController;
use App\Queues\NotificationQueue;
use App\Queues\DocumentQueue;
use DocumentManager\Services\DocumentService;
use DocumentManager\Services\FileStorage;
use DocumentManager\Services\TemplateService;
use DocumentManager\Services\SignatureService;
use AuditManager\Services\AuditService;
use App\Models\PaymentModel;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use App\Services\PayUService;
use GuzzleHttp\Client;

// ✅ Load all configuration files dynamically
$configDirectory = __DIR__;
$config = [];

foreach (glob("{$configDirectory}/*.php") as $filePath) {
    $fileName = basename($filePath, '.php');

    if ($fileName === 'dependencies') {
        continue;
    }

    $config[$fileName] = require $filePath;
}

// ✅ Ensure necessary directories exist
$templateDirectory = __DIR__ . '/../storage/templates';
$fileStorageConfig = $config['storage'];

foreach ([$templateDirectory, $fileStorageConfig['base_directory']] as $directory) {
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

// ✅ Initialize PDO Instances
$pdo = new PDO(
    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['database']['app_database']['host'], $config['database']['app_database']['database']),
    $config['database']['app_database']['username'],
    $config['database']['app_database']['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$securePdo = new PDO(
    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['database']['secure_database']['host'], $config['database']['secure_database']['database']),
    $config['database']['secure_database']['username'],
    $config['database']['secure_database']['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// ✅ Initialize Logger
$logger = new Logger('carfuse');
$logFile = __DIR__ . '/../logs/app.log';

if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0775, true);
}

$streamHandler = new StreamHandler($logFile, Logger::DEBUG);
$formatter = new LineFormatter(null, null, true, true);
$streamHandler->setFormatter($formatter);
$logger->pushHandler($streamHandler);

// ✅ Centralized Services
$encryptionService = new EncryptionService($config['encryption']['encryption_key']);
$fileStorage = new FileStorage($fileStorageConfig, $logger, $encryptionService);
$auditService = new AuditService($securePdo);

// ✅ Register services
return [
    PDO::class => $pdo,
    'SecurePDO' => $securePdo,
    LoggerInterface::class => $logger,
    
    DocumentQueue::class => new DocumentQueue(
        $fileStorage,
        __DIR__ . '/../storage/document_queue.json',
        $logger
    ),    

    Validator::class => new Validator(),
    RateLimiter::class => new RateLimiter($pdo),
    AuditService::class => $auditService,
    EncryptionService::class => $encryptionService,
    FileStorage::class => $fileStorage,

    DocumentService::class => new DocumentService(
        $config['document'],
        $auditService,
        $fileStorage,
        $encryptionService,
        new TemplateService($templateDirectory),
        $logger
    ),

    TokenService::class => new TokenService(
        $config['encryption']['jwt_secret'],
        $config['encryption']['jwt_refresh_secret']
    ),

    NotificationService::class => new NotificationService(
        $pdo,
        $logger,
        $config['notifications']
    ),

    NotificationQueue::class => new NotificationQueue(
        new NotificationService($pdo, $logger, $config['notifications']),
        __DIR__ . '/../storage/notification_queue.json',
        $logger
    ),

    UserService::class => new UserService(
        $securePdo,
        $logger,
        $config['encryption']['jwt_secret']
    ),

    PaymentModel::class => new PaymentModel($pdo),
    
    PaymentService::class => new PaymentService(
        $pdo,
        $logger,
        new PaymentModel($pdo),
        $config['payu']['api_key'],
        $config['payu']['api_secret']
    ),

    PayUService::class => new PayUService(
        new Client(),
        $logger,
        $config['payu']
    ),

    BookingService::class => new BookingService($pdo, $logger),
    MetricsService::class => new MetricsService($pdo),
    ReportService::class => new ReportService($pdo),
    RevenueService::class => new RevenueService($pdo),

    SignatureService::class => new SignatureService(
        new Client(),
        $config['signature']['api_endpoint'],
        $config['signature']['api_key'],
        $fileStorage,
        $encryptionService,
        $logger
    ),

    TemplateService::class => new TemplateService($templateDirectory),

    KeyManager::class => new KeyManager(
        $config['security']['keys'],
        $logger
    ),
];
