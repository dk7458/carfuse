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
use App\Queues\NotificationQueue;
use App\Queues\DocumentQueue;
use DocumentManager\Services\DocumentService;
use DocumentManager\Services\FileStorage;
use DocumentManager\Services\TemplateService;
use DocumentManager\Services\SignatureService;
use AuditManager\Services\AuditService;
use App\Models\Payment;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use App\Services\PayUService;
use GuzzleHttp\Client;
use DI\Container;

return function (Container $container) {
    // ✅ Load configuration dynamically
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
    $fileStorageConfig = $config['storage'];

    foreach ([$templateDirectory, $fileStorageConfig['base_directory']] as $directory) {
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }

    // ✅ Initialize PDO Instances
    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4',
                $config['database']['app_database']['host'],
                $config['database']['app_database']['database']
            ),
            $config['database']['app_database']['username'],
            $config['database']['app_database']['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        $securePdo = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4',
                $config['database']['secure_database']['host'],
                $config['database']['secure_database']['database']
            ),
            $config['database']['secure_database']['username'],
            $config['database']['secure_database']['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) {
        throw new RuntimeException("❌ Database connection failed: " . $e->getMessage());
    }

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
    $container->set(PDO::class, $pdo);
    $container->set('SecurePDO', $securePdo);
    $container->set(LoggerInterface::class, $logger);

    $container->set(DocumentQueue::class, function () use ($fileStorage, $logger) {
        return new DocumentQueue($fileStorage, __DIR__ . '/../storage/document_queue.json', $logger);
    });

    $container->set(Validator::class, function () {
        return new Validator();
    });

    $container->set(RateLimiter::class, function () use ($pdo) {
        return new RateLimiter($pdo);
    });

    $container->set(AuditService::class, function () use ($auditService) {
        return $auditService;
    });

    $container->set(EncryptionService::class, function () use ($encryptionService) {
        return $encryptionService;
    });

    $container->set(FileStorage::class, function () use ($fileStorage) {
        return $fileStorage;
    });

    $container->set(DocumentService::class, function () use ($pdo, $auditService, $fileStorage, $encryptionService, $logger, $templateDirectory) {
        return new DocumentService($pdo, $auditService, $fileStorage, $encryptionService, new TemplateService($templateDirectory), $logger);
    });

    $container->set(TokenService::class, function () use ($config) {
        return new TokenService($config['encryption']['jwt_secret'], $config['encryption']['jwt_refresh_secret']);
    });

    $container->set(NotificationService::class, function () use ($pdo, $logger, $config) {
        return new NotificationService($pdo, $logger, $config['notifications']);
    });

    $container->set(NotificationQueue::class, function () use ($pdo, $logger, $config) {
        return new NotificationQueue(new NotificationService($pdo, $logger, $config['notifications']), __DIR__ . '/../storage/notification_queue.json', $logger);
    });

    $container->set(UserService::class, function () use ($securePdo, $logger, $config) {
        return new UserService($securePdo, $logger, $config['encryption']['jwt_secret']);
    });

    $container->set(Payment::class, function () {
        return new Payment();
    });

    $container->set(PaymentService::class, function () use ($pdo, $logger, $config) {
        return new PaymentService($pdo, $logger, new Payment(), $config['payu']['api_key'], $config['payu']['api_secret']);
    });

    $container->set(PayUService::class, function () use ($logger, $config) {
        return new PayUService(new Client(), $logger, $config['payu']);
    });

    $container->set(BookingService::class, function () use ($pdo, $logger) {
        return new BookingService($pdo, $logger);
    });

    $container->set(MetricsService::class, function () use ($pdo) {
        return new MetricsService($pdo);
    });

    $container->set(ReportService::class, function () use ($pdo) {
        return new ReportService($pdo);
    });

    $container->set(RevenueService::class, function () use ($pdo) {
        return new RevenueService($pdo);
    });

    $container->set(SignatureService::class, function () use ($config, $fileStorage, $encryptionService, $logger) {
        return new SignatureService(new Client(), $config['signature'], $fileStorage, $encryptionService, $logger);
    });

    $container->set(TemplateService::class, function () use ($templateDirectory) {
        return new TemplateService($templateDirectory);
    });

    $container->set(KeyManager::class, function () use ($config, $logger) {
        return new KeyManager($config['keymanager']['keys'], $logger);
    });

    return $container;
};
