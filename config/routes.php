<?php

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Controllers\AuthController;
use App\Controllers\PaymentController;
use App\Controllers\DashboardController;
use App\Controllers\BookingController;
use App\Controllers\NotificationController;
use App\Controllers\NotificationQueueController;
use App\Controllers\AdminDashboardController;
use App\Controllers\ReportController;
use AuditManager\Controllers\AuditController;
use DocumentManager\Controllers\DocumentController;
use DocumentManager\Controllers\SignatureController;
use App\Controllers\UserController;

return simpleDispatcher(function (RouteCollector $router) {

    // Logging helper for route execution
    function logRoute($route) {
        file_put_contents(BASE_PATH . '/debug.log', date('Y-m-d H:i:s') . " - Executing route: $route\n", FILE_APPEND);
    }

    // Middleware-like Authentication Handling
    function requireAuth() {
        require_once BASE_PATH . '/App/Helpers/SecurityHelper.php';
        if (!isUserLoggedIn()) {
            http_response_code(403);
            echo json_encode(["error" => "Unauthorized"]);
            exit();
        }
    }

    // --- New and static routes (placed prior to dynamic routes) ---

    // Static test route to verify routing works correctly
    $router->get('/test', function () {
        logRoute('/test');
        echo 'Test route working';
    });

    // Home Page route updated to use home.php to prevent recursion.
    $router->get('/', function () {
        logRoute('/');
        require BASE_PATH . '/public/home.php';
    });

    // Authentication Routes (using controller callbacks remain unchanged)
    $router->get('/login', [AuthController::class, 'loginView']);
    $router->get('/register', [AuthController::class, 'registerView']);
    $router->post('/register', [UserController::class, 'register']);
    $router->post('/login', [UserController::class, 'login']);
    $router->post('/profile/update', [UserController::class, 'updateProfile']);
    $router->post('/password/reset/request', [UserController::class, 'requestPasswordReset']);
    $router->post('/password/reset', [UserController::class, 'resetPassword']);

    // --- Added static /dashboard route to prevent wildcard conflict ---
    $router->get('/dashboard', function () {
        logRoute('/dashboard');
        require BASE_PATH . '/public/views/user/dashboard.php';
    });

    // Payment Routes
    $router->post('/payments/process', [PaymentController::class, 'processPayment']);
    $router->post('/payments/refund', [PaymentController::class, 'refundPayment']);
    $router->get('/payments/history', [PaymentController::class, 'viewPaymentHistory']);
    $router->get('/payments/installments', [PaymentController::class, 'viewInstallments']);

    // Bookings Routes
    $router->get('/bookings', [DashboardController::class, 'getUserBookings']);
    $router->get('/bookings/{id}', [BookingController::class, 'viewBooking']);
    $router->post('/bookings/{id}/reschedule', [BookingController::class, 'rescheduleBooking']);
    $router->post('/bookings/{id}/cancel', [BookingController::class, 'cancelBooking']);

    // Notifications API Routes
    $router->get('/notifications', [NotificationController::class, 'getUserNotifications']);
    $router->post('/notifications/mark-as-read', [NotificationController::class, 'markNotificationAsRead']);
    $router->post('/notifications/delete', [NotificationController::class, 'deleteNotification']);
    $router->post('/notifications/send', [NotificationController::class, 'sendNotification']);
    $router->post('/notifications/process-queue', [NotificationQueueController::class, 'processQueue']);

    // Admin Dynamic Routing
    $router->get('/admin/{section}', function ($vars) {
        logRoute('/admin/' . $vars['section']);
        $allowedSections = ['dashboard', 'reports', 'audit-logs', 'users'];
        $section = $vars['section'];
        if (in_array($section, $allowedSections)) {
            require BASE_PATH . "/public/views/admin/$section.php";
        } else {
            http_response_code(404);
            require BASE_PATH . "/public/views/errors/404.php";
        }
    });

    // Admin Report Routes
    $router->post('/admin/reports/generate', [ReportController::class, 'generateReport']);
    $router->post('/user/reports/generate', [ReportController::class, 'generateUserReport']);

    // Audit Log Routes
    $router->post('/admin/audit-logs/fetch', [AuditController::class, 'fetchLogs']);

    // Document Manager Routes
    $router->post('/documents/upload-template', [DocumentController::class, 'uploadTemplate']);
    $router->post('/documents/generate-contract/{bookingId}/{userId}', [DocumentController::class, 'generateContract']);
    $router->post('/documents/upload-terms', [DocumentController::class, 'uploadTerms']);
    $router->post('/documents/generate-invoice/{bookingId}', [DocumentController::class, 'generateInvoice']);
    $router->delete('/documents/{documentId}', [DocumentController::class, 'deleteDocument']);

    // --- Dynamic routes (placed after static routes to avoid conflicts) ---

    // Dynamic View Routing for User Dashboard (/dashboard, /profile, etc.)
    $router->get('/{view}', function ($vars) {
        logRoute('/' . $vars['view']);
        $allowedViews = ['bookings', 'payments', 'documents', 'notifications', 'profile', 'settings'];
        $view = $vars['view'];
        if (in_array($view, $allowedViews)) {
            require BASE_PATH . "/public/views/user/$view.php";
        } else {
            http_response_code(404);
            require BASE_PATH . "/public/views/errors/404.php";
        }
    });

    // Dynamic API Routing with Authentication Check (/api/{endpoint})
    $router->get('/api/{endpoint}', function ($vars) {
        logRoute('/api/' . $vars['endpoint'] . ' - start processing');
        requireAuth(); // Ensure user is logged in
        $endpoint = $vars['endpoint'];
        // Additional logging for security and clarity
        logRoute('Processing API endpoint: ' . $endpoint);
        $apiPath = BASE_PATH . "/public/api/$endpoint.php";
        if (file_exists($apiPath)) {
            require $apiPath;
        } else {
            http_response_code(404);
            echo json_encode(["error" => "API endpoint not found"]);
        }
        logRoute('/api/' . $vars['endpoint'] . ' - finished processing');
    });

});
