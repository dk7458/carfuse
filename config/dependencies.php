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
use DocumentManager\Services\EncryptionService;
use DocumentManager\Services\FileStorage;
use AuditManager\Services\AuditService;
use App\Models\PaymentModel;

use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use App\Services\PayUService;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

return [
    /**
     * Database Connection for App
     */
    PDO::class => function () {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                $_ENV['DB_HOST'] ?? '127.0.0.1',
                $_ENV['DB_NAME'] ?? 'carfuse'
            );
            $username = $_ENV['DB_USER'] ?? 'root';
            $password = $_ENV['DB_PASSWORD'] ?? '';

            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (Exception $e) {
            throw new RuntimeException('Failed to create PDO instance: ' . $e->getMessage());
        }
    },

    /**
     * Secure Database Connection
     * Handles sensitive user data and audits.
     */
    'SecurePDO' => function () {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $_ENV['SECURE_DB_HOST'] ?? '127.0.0.1',
            $_ENV['SECURE_DB_NAME'] ?? 'carfuse_secure'
        );
        $username = $_ENV['SECURE_DB_USER'] ?? 'root';
        $password = $_ENV['SECURE_DB_PASSWORD'] ?? '';

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    },

    /**
     * Logger Configuration
     */
    LoggerInterface::class => function () {
        try {
            $logger = new Logger('carfuse');
            $handler = new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG);
            $formatter = new LineFormatter(null, null, true, true);
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);
            return $logger;
        } catch (Exception $e) {
            throw new RuntimeException('Failed to configure logger: ' . $e->getMessage());
        }
    },

    /**
     * Validator Service
     */
    Validator::class => function () {
        return new Validator();
    },

    /**
     * Rate Limiter Service
     */
    RateLimiter::class => function () {
        return new RateLimiter(5, 900); // 5 attempts, 15-minute window
    },

    /**
     * Audit Service
     * Logs critical system events for auditing purposes.
     */
    AuditService::class => function ($container) {
        try {
            return new AuditService($container['SecurePDO']);
        } catch (Exception $e) {
            throw new RuntimeException('Failed to create AuditService: ' . $e->getMessage());
        }
    },

    /**
     * Encryption Service
     * Provides encryption and decryption functionality.
     */
    EncryptionService::class => function () {
        return new EncryptionService($_ENV['ENCRYPTION_KEY'] ?? 'your-encryption-key');
    },

    /**
     * Document File Storage Service
     * Handles file operations for document storage.
     */
    FileStorage::class => function () {
        return new FileStorage(__DIR__ . '/../storage/documents');
    },

    /**
     * Document Service
     */
    DocumentService::class => function ($container) {
        try {
            return new DocumentService(
                $container['SecurePDO'],
                $container[AuditService::class],
                $container[FileStorage::class],
                $container[EncryptionService::class]
            );
        } catch (Exception $e) {
            throw new RuntimeException('Failed to create DocumentService: ' . $e->getMessage());
        }
    },

    /**
     * Token Service
     */
    TokenService::class => function () {
        $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
        if (empty($jwtSecret)) {
            throw new RuntimeException('JWT_SECRET is not set in the environment.');
        }
        return new TokenService($jwtSecret);
    },

    /**
     * Notification Queue
     */
    NotificationQueue::class => function ($container) {
        return new NotificationQueue(
            $container[NotificationService::class],
            __DIR__ . '/../storage/notification_queue.json'
        );
    },

    /**
     * Notification Service
     */
    NotificationService::class => function ($container) {
        try {
            return new NotificationService(
                $container[PDO::class],
                $container[LoggerInterface::class]
            );
        } catch (Exception $e) {
            throw new RuntimeException('Failed to create NotificationService: ' . $e->getMessage());
        }
    },

    /**
     * User Service
     */
    UserService::class => function ($container) {
        try {
            return new UserService(
                $container['SecurePDO'],
                $container[LoggerInterface::class]
            );
        } catch (Exception $e) {
            throw new RuntimeException('Failed to create UserService: ' . $e->getMessage());
        }
    },

    /**
     * Payment Model
     */
    PaymentModel::class => function ($container) {
        return new PaymentModel($container[PDO::class]);
    },

    /**
     * Payment Service
     */
    PaymentService::class => function ($container) {
        try {
            return new PaymentService(
                $container[PDO::class],
                $container[LoggerInterface::class],
                $container[PaymentModel::class]
            );
        } catch (Exception $e) {
            throw new RuntimeException('Failed to create PaymentService: ' . $e->getMessage());
        }
    },

    /**
     * PayU Service
     */
    PayUService::class => function ($container) {
        try {
            return new PayUService(
                new GuzzleHttp\Client(),
                $container[LoggerInterface::class],
                [
                    'merchant_key' => $_ENV['PAYU_MERCHANT_KEY'],
                    'merchant_salt' => $_ENV['PAYU_MERCHANT_SALT'],
                    'endpoint' => $_ENV['PAYU_API_ENDPOINT'],
                ]
            );
        } catch (Exception $e) {
            throw new RuntimeException('Failed to create PayUService: ' . $e->getMessage());
        }
    },

    /**
     * User Controller
     */
    UserController::class => function ($container) {
        try {
            return new UserController(
                $container[PDO::class],
                $container['SecurePDO'],
                $container[LoggerInterface::class],
                ['jwt_secret' => $_ENV['JWT_SECRET'] ?? 'your-secret-key'],
                $container[Validator::class],
                $container[RateLimiter::class],
                $container[AuditService::class],
                $container[NotificationService::class]
            );
        } catch (Exception $e) {
            throw new RuntimeException('Failed to create UserController: ' . $e->getMessage());
        }
    },

    'db' => [
        'host' => getenv('DB_HOST'),
        'user' => getenv('DB_USER'),
        'password' => getenv('DB_PASSWORD'),
        'dbname' => getenv('DB_NAME'),
    ],
];
