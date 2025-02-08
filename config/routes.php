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
    // Middleware-like Authentication Handling
    function requireAuth() {
        require_once BASE_PATH . '/App/Helpers/SecurityHelper.php';
        if (!isUserLoggedIn()) {
            http_response_code(403);
            echo json_encode(["error" => "Unauthorized"]);
            exit();
        }
    }

    // Home Page (Always Loads Index)
    $router->get('/', function () {
        require BASE_PATH . '/public/index.php';
    });

    // Dynamic View Routing for User Dashboard
    $router->get('/{view}', function ($vars) {
        $allowedViews = ['dashboard', 'bookings', 'payments', 'documents', 'notifications', 'profile', 'settings'];
        $view = $vars['view'];

        if (in_array($view, $allowedViews)) {
            require BASE_PATH . "/public/views/user/$view.php";
        } else {
            http_response_code(404);
            require BASE_PATH . "/public/views/errors/404.php";
        }
    });

    // Dynamic API Routing with Authentication Check
    $router->get('/api/{endpoint}', function ($vars) {
        requireAuth(); // Ensure user is logged in
        $endpoint = $vars['endpoint'];
        $apiPath = BASE_PATH . "/public/api/$endpoint.php";

        if (file_exists($apiPath)) {
            require $apiPath;
        } else {
            http_response_code(404);
            echo json_encode(["error" => "API endpoint not found"]);
        }
    });

    // Authentication Routes
    $router->get('/login', [AuthController::class, 'loginView']);
    $router->get('/register', [AuthController::class, 'registerView']);
    $router->post('/register', [UserController::class, 'register']);
    $router->post('/login', [UserController::class, 'login']);
    $router->post('/profile/update', [UserController::class, 'updateProfile']);
    $router->post('/password/reset/request', [UserController::class, 'requestPasswordReset']);
    $router->post('/password/reset', [UserController::class, 'resetPassword']);

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
});
