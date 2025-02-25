<?php

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Middleware\AuthMiddleware;
use App\Helpers\SecurityHelper;
use App\Helpers\ApiHelper;

return simpleDispatcher(function (RouteCollector $router) {

    // ✅ Define Public View Routes
    $router->addRoute(['GET'], '/', fn() => require '../public/views/home.php');
    $router->addRoute(['GET'], '/dashboard', fn() => require '../public/views/dashboard.php');
    $router->addRoute(['GET'], '/profile', fn() => require '../public/views/user/profile.php');
    $router->addRoute(['GET'], '/reports', fn() => require '../public/views/user/reports.php');
    $router->addRoute(['GET'], '/auth/login', fn() => require '../public/views/auth/login.php');
    $router->addRoute(['GET'], '/auth/register', fn() => require '../public/views/auth/register.php');
    $router->addRoute(['GET'], '/auth/password_reset', fn() => require '../public/views/auth/password_reset.php');
    $router->addRoute(['GET'], '/documents/signing_page', fn() => require '../public/views/documents/signing_page.php');

    // ✅ Define API Routes with Authentication and Middleware
    $router->addRoute(['POST'], '/api/auth/login', 'App\Controllers\AuthController@login');
    $router->addRoute(['POST'], '/api/auth/register', 'App\Controllers\AuthController@register');
    $router->addRoute(['POST'], '/api/auth/refresh', 'App\Controllers\AuthController@refresh');
    $router->addRoute(['POST'], '/api/auth/logout', 'App\Controllers\AuthController@logout');

    // ✅ Protected API Routes (Require Authentication)
    $router->addRoute(['GET'], '/api/user/profile', function () {
        AuthMiddleware::requireAuth();
        require '../public/api/user/profile.php';
    });

    $router->addRoute(['GET'], '/api/user/settings', function () {
        AuthMiddleware::requireAuth();
        require '../public/api/user/settings.php';
    });

    $router->addRoute(['GET'], '/api/user/notifications', function () {
        AuthMiddleware::requireAuth();
        require '../public/api/user/notifications.php';
    });

    $router->addRoute(['GET'], '/api/dashboard/metrics', function () {
        AuthMiddleware::requireAuth();
        require '../public/api/dashboard/metrics.php';
    });

    $router->addRoute(['GET'], '/api/dashboard/reports', function () {
        AuthMiddleware::requireAuth();
        require '../public/api/dashboard/reports.php';
    });

    // ✅ Booking API Routes
    $router->addRoute(['POST'], '/api/bookings/create', 'App\Controllers\BookingController@createBooking');
    $router->addRoute(['GET'], '/api/bookings/view/{id:\d+}', 'App\Controllers\BookingController@viewBooking');
    $router->addRoute(['POST'], '/api/bookings/cancel/{id:\d+}', 'App\Controllers\BookingController@cancelBooking');
    $router->addRoute(['POST'], '/api/bookings/reschedule/{id:\d+}', 'App\Controllers\BookingController@rescheduleBooking');

    // ✅ Payment API Routes
    $router->addRoute(['POST'], '/api/payments/process', 'App\Controllers\PaymentController@processPayment');
    $router->addRoute(['POST'], '/api/payments/refund/{id:\d+}', 'App\Controllers\PaymentController@refundPayment');
    $router->addRoute(['GET'], '/api/payments/history', 'App\Controllers\PaymentController@paymentHistory');

    // ✅ Report API Routes
    $router->addRoute(['POST'], '/api/reports/generate', 'App\Controllers\ReportController@generateReport');
    $router->addRoute(['GET'], '/api/reports/view/{id:\d+}', 'App\Controllers\ReportController@viewReport');

    // ✅ Admin API Routes
    $router->addRoute(['GET'], '/api/admin/users', function () {
        AuthMiddleware::requireAdmin();
        require '../public/api/admin/users.php';
    });

    $router->addRoute(['GET'], '/api/admin/dashboard', function () {
        AuthMiddleware::requireAdmin();
        require '../public/api/admin/dashboard.php';
    });

    $router->addRoute(['GET'], '/api/admin/logs', function () {
        AuthMiddleware::requireAdmin();
        require '../public/api/admin/logs.php';
    });

    // ✅ Document API Routes
    $router->addRoute(['POST'], '/api/documents/upload', 'App\Controllers\DocumentController@uploadDocument');
    $router->addRoute(['POST'], '/api/documents/sign', 'App\Controllers\DocumentController@signDocument');
    $router->addRoute(['GET'], '/api/documents/view/{id:\d+}', 'App\Controllers\DocumentController@viewDocument');

    // ✅ System API Routes
    $router->addRoute(['GET'], '/api/system/logs', function () {
        AuthMiddleware::requireAdmin();
        require '../public/api/system/logs.php';
    });

    $router->addRoute(['GET'], '/api/system/status', function () {
        AuthMiddleware::requireAuth();
        require '../public/api/system/status.php';
    });

    // ✅ Catch-All for Unmatched Requests
    $router->addRoute(['GET', 'POST'], '/{any:.+}', function () {
        http_response_code(404);
        echo json_encode(["error" => "Not Found"]);
    });
});
