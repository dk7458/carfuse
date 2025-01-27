<?php

use App\Services\Validator;
use App\Services\AuditLogger;
use App\Services\RateLimiter;
use App\Services\Auth\TokenService;
use App\Services\NotificationService;
use App\Services\UserService;
use App\Services\PaymentService;
use App\Controllers\UserController;
use App\Queues\NotificationQueue;
use PDO;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use App\Services\PayUService;


return [
    PayUService::class => function ($container) {
        return new PayUService(
            new GuzzleHttp\Client(),
            $container[Psr\Log\LoggerInterface::class],
            [
                'merchant_key' => $_ENV['PAYU_MERCHANT_KEY'],
                'merchant_salt' => $_ENV['PAYU_MERCHANT_SALT'],
                'endpoint' => $_ENV['PAYU_API_ENDPOINT'],
            ]
        );
    },
    /**
     * Database Connection
     * Configure PDO for database operations
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
     * Logger Configuration
     * Using Monolog for structured application logging
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
     * A custom service for data validation
     */
    Validator::class => function () {
        return new Validator();
    },

    /**
     * Rate Limiter Service
     * A simple rate limiter using a static store
     */
    RateLimiter::class => function () {
        return new RateLimiter(5, 900); // 5 attempts, 15-minute window
    },

    /**
     * Audit Logger
     * Logs critical system events for auditing purposes
     */
    AuditLogger::class => function ($container) {
        return new AuditLogger($container[LoggerInterface::class]);
    },

    /**
     * Token Service
     * Handles token-based authentication for APIs
     */
    TokenService::class => function () {
        return new TokenService($_ENV['JWT_SECRET'] ?? 'your-secret-key');
    },

    /**
     * Notification Queue
     * Handles asynchronous notification processing
     */
    NotificationQueue::class => function ($container) {
        return new NotificationQueue(
            $container[NotificationService::class],
            __DIR__ . '/../storage/notification_queue.json'
        );
    },

    /**
     * Notification Service
     * Handles notification operations and delivery
     */
    NotificationService::class => function ($container) {
        return new NotificationService(
            $container[PDO::class],
            $container[LoggerInterface::class]
        );
    },

    /**
     * User Service
     * Handles user-related operations
     */
    UserService::class => function ($container) {
        return new UserService(
            $container[PDO::class],
            $container[LoggerInterface::class]
        );
    },

    /**
     * Payment Service
     * Handles all payment gateway integrations
     */
    PaymentService::class => function ($container) {
        return new PaymentService(
            $container[PDO::class],
            $container[LoggerInterface::class]
        );
    },

    /**
     * User Controller
     * Manages user operations
     */
    UserController::class => function ($container) {
        return new UserController(
            $container[PDO::class],
            $container[LoggerInterface::class],
            ['jwt_secret' => $_ENV['JWT_SECRET'] ?? 'your-secret-key'],
            $container[Validator::class],
            $container[RateLimiter::class],
            $container[AuditLogger::class]
        );
    },
];
