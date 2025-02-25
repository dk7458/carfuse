<?php

/**
 * Controller Dependencies Configuration
 * 
 * This file contains all controller-related dependency registrations for the DI container.
 * It centralizes controller registrations to make the main dependencies.php cleaner.
 */

use DI\Container;
use Psr\Log\LoggerInterface;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\AdminController;
use App\Controllers\DashboardController;
use App\Controllers\ApiController;
use App\Controllers\DocumentController;
use App\Controllers\PaymentController;
use App\Controllers\ReportController;
use App\Services\Auth\AuthService;
use App\Services\Auth\TokenService;
use App\Services\UserService;
use App\Services\AdminService;
use App\Services\DashboardService;
use App\Services\DocumentService;
use App\Services\PaymentService;
use App\Services\ReportService;
use App\Middleware\AuthMiddleware;
use App\Helpers\ExceptionHandler;

/**
 * Register all controller dependencies in the container
 * 
 * @param Container $container The DI container
 * @return Container The container with registered controllers
 */
function registerControllers(Container $container): Container
{
    // Register authentication and user controllers
    registerAuthControllers($container);
    
    // Register admin and dashboard controllers
    registerAdminControllers($container);
    
    // Register feature-specific controllers
    registerFeatureControllers($container);
    
    $container->get(LoggerInterface::class)->info("All controllers registered successfully");
    
    return $container;
}

/**
 * Register authentication and user-related controllers
 */
function registerAuthControllers(Container $container): void
{
    // Auth Controller - handles login, registration, password reset, etc.
    $container->set(AuthController::class, function($c) {
        return new AuthController(
            $c->get(AuthService::class),
            $c->get(TokenService::class),
            $c->get(ExceptionHandler::class),
            $c->get('auth_logger'),
            $c->get('audit_logger')
        );
    });
    
    // User Controller - handles user profile, settings, etc.
    $container->set(UserController::class, function($c) {
        return new UserController(
            $c->get(UserService::class),
            $c->get(ExceptionHandler::class),
            $c->get('user_logger'),
            $c->get('audit_logger'),
            $c->get(AuthMiddleware::class)
        );
    });
}

/**
 * Register admin and dashboard controllers
 */
function registerAdminControllers(Container $container): void
{
    // Admin Controller - handles administrative functions
    if (class_exists(AdminController::class)) {
        $container->set(AdminController::class, function($c) {
            return new AdminController(
                $c->get(AdminService::class),
                $c->get(UserService::class),
                $c->get(AuthMiddleware::class),
                $c->get(ExceptionHandler::class),
                $c->get('admin_logger'),
                $c->get('audit_logger')
            );
        });
    }
    
    // Dashboard Controller - handles dashboard views and data
    if (class_exists(DashboardController::class)) {
        $container->set(DashboardController::class, function($c) {
            return new DashboardController(
                $c->get(DashboardService::class),
                $c->get(AuthMiddleware::class),
                $c->get(ExceptionHandler::class),
                $c->get('api_logger')
            );
        });
    }
}

/**
 * Register feature-specific controllers
 */
function registerFeatureControllers(Container $container): void
{
    // API Controller - handles generic API endpoints
    if (class_exists(ApiController::class)) {
        $container->set(ApiController::class, function($c) {
            return new ApiController(
                $c->get(ExceptionHandler::class),
                $c->get('api_logger')
            );
        });
    }
    
    // Document Controller - handles document management
    if (class_exists(DocumentController::class)) {
        $container->set(DocumentController::class, function($c) {
            return new DocumentController(
                $c->get(DocumentService::class),
                $c->get(AuthMiddleware::class),
                $c->get(ExceptionHandler::class),
                $c->get('api_logger')
            );
        });
    }
    
    // Payment Controller - handles payment processing
    if (class_exists(PaymentController::class)) {
        $container->set(PaymentController::class, function($c) {
            return new PaymentController(
                $c->get(PaymentService::class),
                $c->get(AuthMiddleware::class),
                $c->get(ExceptionHandler::class),
                $c->get('api_logger')
            );
        });
    }
    
    // Report Controller - handles report generation
    if (class_exists(ReportController::class)) {
        $container->set(ReportController::class, function($c) {
            return new ReportController(
                $c->get(ReportService::class),
                $c->get(AuthMiddleware::class),
                $c->get(ExceptionHandler::class),
                $c->get('api_logger')
            );
        });
    }
}

/**
 * Register middleware for controllers
 */
function registerControllerMiddleware(Container $container): void
{
    // Auth Middleware - handles authentication for protected routes
    $container->set(AuthMiddleware::class, function($c) {
        return new AuthMiddleware(
            $c->get(TokenService::class),
            $c->get('auth_logger')
        );
    });
}

// If this file is included directly, return a container with registered controllers
if (!isset($container) && basename($_SERVER['SCRIPT_FILENAME']) == basename(__FILE__)) {
    $container = new Container();
    
    // We need logger and services to be available
    require_once __DIR__ . '/logging_dependencies.php';
    require_once __DIR__ . '/service_dependencies.php';
    
    $container = registerLoggers($container);
    
    // Mock configuration for standalone usage
    $config = [
        'encryption' => ['jwt_secret' => 'test', 'jwt_refresh_secret' => 'test'],
    ];
    
    // Register services needed by controllers
    $container = registerServices($container, $config);
    
    // Register middleware
    registerControllerMiddleware($container);
    
    // Register controllers
    $container = registerControllers($container);
    
    return $container;
}
