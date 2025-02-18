<?php

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Helpers\SecurityHelper;
use App\Helpers\ApiHelper;

// ✅ Define the FastRoute Dispatcher
return simpleDispatcher(function (RouteCollector $router) {

    // ✅ Define Explicit View Routes
    $viewRoutes = [
        '/' => 'views/home.php',
        '/dashboard' => 'views/dashboard.php',
        '/profile' => 'views/user/profile.php',
        '/reports' => 'views/user/reports.php',
        '/auth/login' => 'views/auth/login.php',
        '/auth/register' => 'views/auth/register.php',
        '/auth/password_reset' => 'views/auth/password_reset.php',
        '/documents/signing_page' => 'views/documents/signing_page.php',
    ];

    foreach ($viewRoutes as $route => $filePath) {
        $router->addRoute(['GET', 'POST'], $route, function () use ($filePath) {
            require __DIR__ . '/../public/' . $filePath;
        });
    }

    // ✅ Define API Routes with Authentication
    $apiRoutes = [
        '/api/auth/login' => 'api/auth/login.php',
        '/api/auth/register' => 'api/auth/register.php',
        '/api/auth/refresh' => 'api/auth/refresh.php',
        '/api/auth/logout' => 'api/auth/logout.php',
        '/api/user/profile' => 'api/user/profile.php',
        '/api/user/settings' => 'api/user/settings.php',
        '/api/user/notifications' => 'api/user/notifications.php',
        '/api/dashboard/metrics' => 'api/dashboard/metrics.php',
        '/api/dashboard/reports' => 'api/dashboard/reports.php',
        '/api/bookings/create' => 'api/bookings/create.php',
        '/api/bookings/view' => 'api/bookings/view.php',
        '/api/bookings/cancel' => 'api/bookings/cancel.php',
        '/api/bookings/reschedule' => 'api/bookings/reschedule.php',
        '/api/payments/process' => 'api/payments/process.php',
        '/api/payments/refund' => 'api/payments/refund.php',
        '/api/payments/history' => 'api/payments/history.php',
        '/api/reports/generate' => 'api/reports/generate.php',
        '/api/reports/view' => 'api/reports/view.php',
        '/api/admin/users' => 'api/admin/users.php',
        '/api/admin/dashboard' => 'api/admin/dashboard.php',
        '/api/admin/logs' => 'api/admin/logs.php',
        '/api/documents/upload' => 'api/documents/upload.php',
        '/api/documents/sign' => 'api/documents/sign.php',
        '/api/documents/view' => 'api/documents/view.php',
        '/api/system/logs' => 'api/system/logs.php',
        '/api/system/status' => 'api/system/status.php',
    ];

    foreach ($apiRoutes as $route => $filePath) {
        $router->addRoute(['GET', 'POST'], $route, function () use ($filePath) {
            require __DIR__ . '/../public/' . $filePath;
        });
    }

    // ✅ Default Route for Unmatched Requests
    $router->addRoute(['GET', 'POST'], '/{any:.+}', function () {
        http_response_code(404);
        echo json_encode(["error" => "Page not found"]);
    });
});
