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
use DI\Container;
use Psr\Log\LoggerInterface;
use App\Services\AuditService;
use App\Services\Validator;
use App\Services\Auth\TokenService;
use App\Helpers\ExceptionHandler;
use App\Services\Auth\AuthService;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\StatisticsService;
use App\Services\NotificationService;
use App\Services\SignatureService;
use App\Services\DocumentService;
use App\Services\ReportService;
use App\Helpers\DatabaseHelper;
use App\Services\RateLimiter;

return function (Container $container) {
    // Controllers
    $container->set(UserController::class, function($c) {
        return new UserController(
            $c->get(LoggerInterface::class),
            $c->get(Validator::class),
            $c->get(TokenService::class),
            $c->get(ExceptionHandler::class),
            $c->get(AuthService::class),
            $c->get(AuditService::class)
        );
    });

    $container->set(AuthController::class, function($c) {
        return new AuthController(
            $c->get(LoggerInterface::class),
            $c->get(AuthService::class),
            $c->get(TokenService::class),
            $c->get(DatabaseHelper::class),
            $c->get(RateLimiter::class),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(BookingController::class, function($c) {
        return new BookingController(
            $c->get(LoggerInterface::class),
            $c->get(BookingService::class),
            $c->get(PaymentService::class),
            $c->get(Validator::class)
        );
    });

    $container->set(NotificationController::class, function($c) {
        return new NotificationController(
            $c->get(LoggerInterface::class),
            $c->get(ExceptionHandler::class),
            $c->get(AuditService::class)
        );
    });

    $container->set(AdminController::class, function($c) {
        return new AdminController(
            $c->get(LoggerInterface::class),
            $c->get(AuditService::class),
            $c->get(ResponseFactoryInterface::class),
            $c->get(TokenService::class)
        );
    });

    $container->set(SignatureController::class, function($c) {
        return new SignatureController(
            $c->get(LoggerInterface::class),
            $c->get(SignatureService::class),
            $c->get(ExceptionHandler::class),
            $c->get(AuditService::class)
        );
    });

    $container->set(DashboardController::class, function($c) {
        return new DashboardController(
            $c->get(LoggerInterface::class),
            $c->get(BookingService::class),
            $c->get(StatisticsService::class),
            $c->get(NotificationService::class)
        );
    });

    $container->set(AdminDashboardController::class, function($c) {
        return new AdminDashboardController(
            $c->get(LoggerInterface::class),
            $c->get(ExceptionHandler::class),
            $c->get(AuditService::class)
        );
    });

    $container->set(PaymentController::class, function($c) {
        return new PaymentController(
            $c->get(LoggerInterface::class),
            $c->get(PaymentService::class),
            $c->get(Validator::class),
            $c->get(NotificationService::class)
        );
    });

    $container->set(DocumentController::class, function($c) {
        return new DocumentController(
            $c->get(LoggerInterface::class),
            $c->get(DocumentService::class),
            $c->get(Validator::class),
            $c->get(AuditService::class)
        );
    });

    $container->set(ReportController::class, function($c) {
        return new ReportController(
            $c->get(LoggerInterface::class),
            $c->get(ReportService::class),
            $c->get(NotificationService::class),
            $c->get(ExceptionHandler::class)
        );
    });

    $container->set(AuditController::class, function($c) {
        return new AuditController(
            $c->get(LoggerInterface::class),
            $c->get(AuditService::class),
            $c->get(ExceptionHandler::class)
        );
    });
    
    $container->set(ApiController::class, function($c) {
        return new ApiController(
            $c->get(LoggerInterface::class),
            $c->get(ResponseFactoryInterface::class),
            $c->get(ExceptionHandler::class),
            $c->get(AuditService::class)
        );
    });
};
