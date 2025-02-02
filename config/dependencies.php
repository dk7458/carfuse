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
        $logger = new Logger('carfuse');
        $handler = new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG);
        $formatter = new LineFormatter(null, null, true, true);
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);
        return $logger;
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
        return new AuditService($container['SecurePDO']);
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
        return new DocumentService(
            $container['SecurePDO'],
            $container[AuditService::class],
            $container[FileStorage::class],
            $container[EncryptionService::class]
        );
    },

    /**
     * Token Service
     */
    TokenService::class => function () {
        return new TokenService($_ENV['JWT_SECRET'] ?? 'your-secret-key');
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
        return new NotificationService(
            $container[PDO::class],
            $container[LoggerInterface::class]
        );
    },

    /**
     * User Service
     */
    UserService::class => function ($container) {
        return new UserService(
            $container['SecurePDO'],
            $container[LoggerInterface::class]
        );
    },

    /**
     * Payment Service
     */
    PaymentService::class => function ($container) {
        return new PaymentService(
            $container[PDO::class],
            $container[LoggerInterface::class]
        );
    },

    /**
     * PayU Service
     */
    PayUService::class => function ($container) {
        return new PayUService(
            new GuzzleHttp\Client(),
            $container[LoggerInterface::class],
            [
                'merchant_key' => $_ENV['PAYU_MERCHANT_KEY'],
                'merchant_salt' => $_ENV['PAYU_MERCHANT_SALT'],
                'endpoint' => $_ENV['PAYU_API_ENDPOINT'],
            ]
        );
    },

    /**
     * User Controller
     */
    UserController::class => function ($container) {
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
    },

    'db' => [
        'host' => getenv('DB_HOST'),
        'user' => getenv('DB_USER'),
        'password' => getenv('DB_PASSWORD'),
        'dbname' => getenv('DB_NAME'),
    ],
];
