<?php

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Middleware\AuthMiddleware;
use App\Middleware\TokenValidationMiddleware;
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
        $router->addRoute(['GET'], '/api/auth/userDetails', [$authController, 'userDetails'])->middleware(AuthMiddleware::class);

        $router->post('/login', [$authController, 'login']);
        $router->post('/register', [$authController, 'register']);
        $router->post('/refresh', [$authController, 'refresh']);
        $router->post('/logout', [$authController, 'logout']);
        $router->get('/user', [$authController, 'userDetails'])->middleware(AuthMiddleware::class);

        // Protected API Routes (Require Authentication)
        $router->addRoute(['GET'], '/api/user/profile', [$userController, 'getUserProfile'])->middleware(AuthMiddleware::class);
        $router->addRoute(['POST'], '/api/user/updateProfile', [$userController, 'updateProfile'])->middleware(AuthMiddleware::class);

        $router->addRoute(['GET'], '/api/user/settings', function (Request $request, RequestHandler $handler) {
            return (new TokenValidationMiddleware())->__invoke($request, $handler);
        });

        $router->addRoute(['GET'], '/api/user/notifications', function (Request $request, RequestHandler $handler) {
            return (new TokenValidationMiddleware())->__invoke($request, $handler);
        });

        $router->addRoute(['GET'], '/api/dashboard/metrics', function (Request $request, RequestHandler $handler) {
            return (new TokenValidationMiddleware())->__invoke($request, $handler);
        });

        $router->addRoute(['GET'], '/api/dashboard/reports', function (Request $request, RequestHandler $handler) {
            return (new TokenValidationMiddleware())->__invoke($request, $handler);
        });

        // Booking API Routes
        $router->addRoute(['POST'], '/api/bookings/create', 'App\Controllers\BookingController@createBooking');
        $router->addRoute(['GET'], '/api/bookings/view/{id:\d+}', 'App\Controllers\BookingController@viewBooking');
        $router->addRoute(['POST'], '/api/bookings/cancel/{id:\d+}', 'App\Controllers\BookingController@cancelBooking');
        $router->addRoute(['POST'], '/api/bookings/reschedule/{id:\d+}', 'App\Controllers\BookingController@rescheduleBooking');

        // Payment API Routes
        $router->addRoute(['POST'], '/api/payments/process', 'App\Controllers\PaymentController@processPayment');
        $router->addRoute(['POST'], '/api/payments/refund/{id:\d+}', 'App\Controllers\PaymentController@refundPayment');
        $router->addRoute(['GET'], '/api/payments/history', 'App\Controllers\PaymentController@paymentHistory');

        // Report API Routes
        $router->addRoute(['POST'], '/api/reports/generate', 'App\Controllers\ReportController@generateReport');
        $router->addRoute(['GET'], '/api/reports/view/{id:\d+}', 'App\Controllers\ReportController@viewReport');

        // Admin API Routes
        $router->addRoute(['GET'], '/api/admin/users', function (Request $request, RequestHandler $handler) {
            return (new AuthMiddleware())->__invoke($request, $handler, 'admin');
        });

        $router->addRoute(['GET'], '/api/admin/dashboard', function (Request $request, RequestHandler $handler) {
            return (new AuthMiddleware())->__invoke($request, $handler, 'admin');
        });

        $router->addRoute(['GET'], '/api/admin/logs', function (Request $request, RequestHandler $handler) {
            return (new AuthMiddleware())->__invoke($request, $handler, 'admin');
        });

        // Document API Routes
        $router->addRoute(['POST'], '/api/documents/upload', 'App\Controllers\DocumentController@uploadDocument');
        $router->addRoute(['POST'], '/api/documents/sign', 'App\Controllers\DocumentController@signDocument');
        $router->addRoute(['GET'], '/api/documents/view/{id:\d+}', 'App\Controllers\DocumentController@viewDocument');

        // System API Routes
        $router->addRoute(['GET'], '/api/system/logs', function (Request $request, RequestHandler $handler) {
            return (new AuthMiddleware())->__invoke($request, $handler, 'admin');
        });

        $router->addRoute(['GET'], '/api/system/status', function (Request $request, RequestHandler $handler) {
            return (new TokenValidationMiddleware())->__invoke($request, $handler);
        });

        // Catch-All for Unmatched Requests
        $router->addRoute(['GET', 'POST'], '/{any:.+}', function () {
            http_response_code(404);
            echo json_encode(["error" => "Not Found"]);
        });
    });
};
