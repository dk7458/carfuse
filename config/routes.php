<?php

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use DI\Container;

return function (Container $container) {
    return simpleDispatcher(function (RouteCollector $router) use ($container) {
        // Ensure the AuthController is instantiated
        $authController = $container->get(AuthController::class);

        // Ensure the UserController is instantiated
        $userController = $container->get(UserController::class);

        // Define routes
        $router->addRoute(['GET'], '/', fn() => require '../public/views/home.php');
        $router->addRoute(['GET'], '/dashboard', fn() => require '../public/views/dashboard.php');
        $router->addRoute(['GET'], '/profile', fn() => require '../public/views/user/profile.php');
        $router->addRoute(['GET'], '/reports', fn() => require '../public/views/user/reports.php');
        $router->addRoute(['GET'], '/auth/login', fn() => require '../public/views/auth/login.php');
        $router->addRoute(['GET'], '/auth/register', fn() => require '../public/views/auth/register.php');
        $router->addRoute(['GET'], '/auth/password_reset', fn() => require '../public/views/auth/password_reset.php');
        $router->addRoute(['GET'], '/documents/signing_page', fn() => require '../public/views/documents/signing_page.php');

        $router->addRoute(['POST'], '/api/auth/login', [$authController, 'login']);
        $router->addRoute(['POST'], '/api/auth/register', [$authController, 'register']);
        $router->addRoute(['POST'], '/api/auth/refresh', [$authController, 'refresh']);
        $router->addRoute(['POST'], '/api/auth/logout', [$authController, 'logout']);
        $router->addRoute(['POST'], '/api/auth/reset-request', [$authController, 'resetPasswordRequest']);
        $router->addRoute(['GET'], '/api/auth/userDetails', [$authController, 'userDetails']);

        // Protected API Routes (Require Authentication)
        $router->addGroup('/api/user', function (RouteCollector $r) use ($userController) {
            $r->addRoute(['GET'], '/profile', [$userController, 'getUserProfile']);
            $r->addRoute(['POST'], '/updateProfile', [$userController, 'updateProfile']);
            $r->addRoute(['GET'], '/settings', 'App\Middleware\TokenValidationMiddleware');
            $r->addRoute(['GET'], '/notifications', 'App\Middleware\TokenValidationMiddleware');
        });

        $router->addGroup('/api/dashboard', function (RouteCollector $r) {
            $r->addRoute(['GET'], '/metrics', 'App\Middleware\TokenValidationMiddleware');
            $r->addRoute(['GET'], '/reports', 'App\Middleware\TokenValidationMiddleware');
        });

        // Booking API Routes
        $router->addGroup('/api/bookings', function (RouteCollector $r) {
            $r->addRoute(['POST'], '/create', 'App\Controllers\BookingController@createBooking');
            $r->addRoute(['GET'], '/view/{id:\d+}', 'App\Controllers\BookingController@viewBooking');
            $r->addRoute(['POST'], '/cancel/{id:\d+}', 'App\Controllers\BookingController@cancelBooking');
            $r->addRoute(['POST'], '/reschedule/{id:\d+}', 'App\Controllers\BookingController@rescheduleBooking');
        });

        // Payment API Routes
        $router->addGroup('/api/payments', function (RouteCollector $r) {
            $r->addRoute(['POST'], '/process', 'App\Controllers\PaymentController@processPayment');
            $r->addRoute(['POST'], '/refund/{id:\d+}', 'App\Controllers\PaymentController@refundPayment');
            $r->addRoute(['GET'], '/history', 'App\Controllers\PaymentController@paymentHistory');
        });

        // Report API Routes
        $router->addGroup('/api/reports', function (RouteCollector $r) {
            $r->addRoute(['POST'], '/generate', 'App\Controllers\ReportController@generateReport');
            $r->addRoute(['GET'], '/view/{id:\d+}', 'App\Controllers\ReportController@viewReport');
        });

        // Admin API Routes
        $router->addGroup('/api/admin', function (RouteCollector $r) {
            $r->addRoute(['GET'], '/users', 'App\Middleware\AuthMiddleware');
            $r->addRoute(['GET'], '/dashboard', 'App\Middleware\AuthMiddleware');
            $r->addRoute(['GET'], '/logs', 'App\Middleware\AuthMiddleware');
        });

        // Document API Routes
        $router->addGroup('/api/documents', function (RouteCollector $r) {
            $r->addRoute(['POST'], '/upload', 'App\Controllers\DocumentController@uploadDocument');
            $r->addRoute(['POST'], '/sign', 'App\Controllers\DocumentController@signDocument');
            $r->addRoute(['GET'], '/view/{id:\d+}', 'App\Controllers\DocumentController@viewDocument');
        });

        // System API Routes
        $router->addGroup('/api/system', function (RouteCollector $r) {
            $r->addRoute(['GET'], '/logs', 'App\Middleware\AuthMiddleware');
            $r->addRoute(['GET'], '/status', 'App\Middleware\TokenValidationMiddleware');
        });

        // Catch-All for Unmatched Requests
        $router->addRoute(['GET', 'POST'], '/{any:.+}', function () {
            http_response_code(404);
            echo json_encode(["error" => "Not Found"]);
        });
    });
};
