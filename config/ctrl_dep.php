<?php

use App\Controllers\UserController;
use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\NotificationController;
use App\Controllers\AdminController;
use App\Controllers\SignatureController;
use App\Controllers\DashboardController;
use App\Controllers\AdminDashboardController;
use App\Controllers\PaymentController;
use App\Controllers\DocumentController;
use App\Controllers\ReportController;
use App\Controllers\AuditController;
use App\Controllers\ApiController;

use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;
use DI\Container;
use Psr\Log\LoggerInterface;

use App\Models\User;
use App\Services\AuditService;
use App\Services\AdminService;
use App\Services\Validator;
use App\Services\Auth\TokenService;
use App\Helpers\ExceptionHandler;
use App\Services\Auth\AuthService;
use App\Services\NotificationService;
use App\Services\UserService;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\MetricsService;
use App\Services\SignatureService;
use App\Services\DocumentService;
use App\Services\ReportService;
use App\Helpers\DatabaseHelper;
use App\Services\RateLimiter;

return function (Container $container) {
    // Access the global loggers
    global $logger, $loggers;

    // Bind ResponseFactoryInterface to an implementation
    if (!$container->has(ResponseFactoryInterface::class)) {
        $container->set(ResponseFactoryInterface::class, function() {
            return new ResponseFactory();
        });
    }
    
    // Controllers
    $container->set(ApiController::class, function($c) use ($logger, $loggers) {
        return new ApiController(
            $loggers['api'] ?? $logger,
            $c->get(AuditService::class),
            $c->get(ExceptionHandler::class)
        );
    });
    
    $container->set(UserController::class, function($c) use ($logger, $loggers) {
        return new UserController(
            $loggers['user'] ?? $loggers['api'] ?? $logger,
            $c->get(Validator::class),
            $c->get(TokenService::class),
            $c->get(ExceptionHandler::class),
            $c->get(AuthService::class),
            $c->get(AuditService::class),
            $c->get(User::class)
        );
    });

    $container->set(AuthController::class, function($c) use ($logger, $loggers) {
        return new AuthController(
            $loggers['auth'] ?? $logger,
            $c->get(AuthService::class),
            $c->get(TokenService::class),
            $c->get(RateLimiter::class),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(BookingController::class, function($c) use ($logger, $loggers) {
        return new BookingController(
            $loggers['booking'] ?? $logger,
            $c->get(BookingService::class),
            $c->get(PaymentService::class),
            $c->get(Validator::class),
            $c->get(AuditService::class),
            $c->get(NotificationService::class),
            $c->get(ResponseFactoryInterface::class),
            $c->get(TokenService::class),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(NotificationController::class, function($c) use ($logger, $loggers) {
        return new NotificationController(
            $loggers['notification'] ?? $loggers['api'] ?? $logger,
            $c->get(ExceptionHandler::class),
            $c->get(AuditService::class),
            $c->get(TokenService::class),
            $c->get(NotificationService::class)
        );
    });

    $container->set(AdminController::class, function($c) use ($logger, $loggers) {
        return new AdminController(
            $loggers['admin'] ?? $logger,
            $c->get(AdminService::class),
            $c->get(ResponseFactoryInterface::class),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(SignatureController::class, function($c) use ($logger, $loggers) {
        return new SignatureController(
            $loggers['security'] ?? $logger,
            $c->get(SignatureService::class),
            $c->get(ExceptionHandler::class),
            $c->get(AuditService::class),
            $c->get(TokenService::class)
        );
    });

    $container->set(DashboardController::class, function($c) use ($logger, $loggers) {
        return new DashboardController(
            $loggers['dashboard'] ?? $loggers['api'] ?? $logger,
            $c->get(BookingService::class),
            $c->get(MetricsService::class),
            $c->get(NotificationService::class),
            $c->get(UserService::class),
            $c->get(AuditService::class),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(AdminDashboardController::class, function($c) use ($logger, $loggers) {
        return new AdminDashboardController(
            $loggers['admin'] ?? $logger,
            $c->get(ExceptionHandler::class),
            $c->get(AuditService::class)
        );
    });

    $container->set(PaymentController::class, function($c) use ($logger, $loggers) {
        return new PaymentController(
            $loggers['payment'] ?? $logger,
            $c->get(PaymentService::class),
            $c->get(Validator::class),
            $c->get(NotificationService::class),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(DocumentController::class, function($c) use ($logger, $loggers) {
        return new DocumentController(
            $loggers['document'] ?? $logger,
            $c->get(DocumentService::class),
            $c->get(Validator::class),
            $c->get(AuditService::class),
            $c->get(ExceptionHandler::class)
       );
    });

    $container->set(ReportController::class, function($c) use ($logger, $loggers) {
        return new ReportController(
            $loggers['report'] ?? $logger,
            $c->get(ReportService::class),
            $c->get(NotificationService::class),
            $c->get(ExceptionHandler::class),
            $c->get(AuditService::class)
        );
    });

    $container->set(AuditController::class, function($c) use ($logger, $loggers) {
        return new AuditController(
            $loggers['audit'] ?? $logger,
            $c->get(AuditService::class),
            $c->get(ExceptionHandler::class)
        );
    });
    
    // Lazy-load controllers only when needed
    $container->get(LoggerInterface::class)->info("Controllers registered successfully.");
};
