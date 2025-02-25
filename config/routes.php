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

    // ✅ Auth API Routes - moved from routes/api.php
    $router->addRoute(['POST'], '/api/auth/login', 'AuthController@login');
    $router->addRoute(['POST'], '/api/auth/register', 'AuthController@register');
    $router->addRoute(['POST'], '/api/auth/refresh', 'AuthController@refreshToken');
    $router->addRoute(['POST'], '/api/auth/logout', 'AuthController@logout');
    $router->addRoute(['POST'], '/api/auth/reset-request', 'AuthController@resetPasswordRequest');
    $router->addRoute(['POST'], '/api/auth/reset', 'AuthController@resetPassword');

    // ✅ User API Routes - moved from routes/api.php
    $router->addRoute(['GET'], '/api/user/profile', 'UserController@getUserProfile');
    $router->addRoute(['PUT'], '/api/user/profile', 'UserController@updateProfile');
    $router->addRoute(['GET'], '/api/user/dashboard', 'UserController@userDashboard');
    $router->addRoute(['GET'], '/api/admin/dashboard', 'UserController@adminAction');

    // ✅ Protected API Routes - simplified with controller references
    $router->addRoute(['GET'], '/api/user/settings', 'UserController@getSettings');
    $router->addRoute(['GET'], '/api/user/notifications', 'UserController@getNotifications');
    $router->addRoute(['GET'], '/api/dashboard/metrics', 'DashboardController@getMetrics');
    $router->addRoute(['GET'], '/api/dashboard/reports', 'DashboardController@getReports');

    // ✅ Booking API Routes
    $router->addRoute(['POST'], '/api/bookings/create', 'BookingController@createBooking');
    $router->addRoute(['GET'], '/api/bookings/view/{id:\d+}', 'BookingController@viewBooking');
    $router->addRoute(['POST'], '/api/bookings/cancel/{id:\d+}', 'BookingController@cancelBooking');
    $router->addRoute(['POST'], '/api/bookings/reschedule/{id:\d+}', 'BookingController@rescheduleBooking');

    // ✅ Payment API Routes
    $router->addRoute(['POST'], '/api/payments/process', 'PaymentController@processPayment');
    $router->addRoute(['POST'], '/api/payments/refund/{id:\d+}', 'PaymentController@refundPayment');
    $router->addRoute(['GET'], '/api/payments/history', 'PaymentController@paymentHistory');

    // ✅ Report API Routes
    $router->addRoute(['POST'], '/api/reports/generate', 'ReportController@generateReport');
    $router->addRoute(['GET'], '/api/reports/view/{id:\d+}', 'ReportController@viewReport');

    // ✅ Admin API Routes
    $router->addRoute(['GET'], '/api/admin/users', 'AdminController@listUsers');
    $router->addRoute(['GET'], '/api/admin/dashboard', 'AdminController@getDashboard');
    $router->addRoute(['GET'], '/api/admin/logs', 'AdminController@viewLogs');

    // ✅ Document API Routes
    $router->addRoute(['POST'], '/api/documents/upload', 'DocumentController@uploadDocument');
    $router->addRoute(['POST'], '/api/documents/sign', 'DocumentController@signDocument');
    $router->addRoute(['GET'], '/api/documents/view/{id:\d+}', 'DocumentController@viewDocument');

    // ✅ System API Routes
    $router->addRoute(['GET'], '/api/system/logs', 'SystemController@viewLogs');
    $router->addRoute(['GET'], '/api/system/status', 'SystemController@getStatus');

    // ✅ Catch-All for Unmatched Requests
    $router->addRoute(['GET', 'POST'], '/{any:.+}', function () {
        http_response_code(404);
        echo json_encode(["error" => "Not Found"]);
    });
});
