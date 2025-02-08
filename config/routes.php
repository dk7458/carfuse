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
    // Home Page
    $router->get('/', function () {
        require BASE_PATH . '/public/index.php';
    });

    // Generic Dynamic View Routing
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

    // Dynamic API Routing
    $router->get('/api/{endpoint}', function ($vars) {
        $endpoint = $vars['endpoint'];
        $apiPath = BASE_PATH . "/public/api/$endpoint.php";

        if (file_exists($apiPath)) {
            require $apiPath;
        } else {
            http_response_code(404);
            echo json_encode(["error" => "API endpoint not found"]);
        }
    });

    // Explicit API Routes (Secure Endpoints)
    $router->get('/api/statistics', [DashboardController::class, 'fetchStatistics']);
    $router->get('/api/notifications', [NotificationController::class, 'fetchNotifications']);
    $router->get('/api/bookings', [DashboardController::class, 'getUserBookings']);
    $router->get('/api/bookings/{id}', [BookingController::class, 'viewBooking']);

    // Authentication Views
    $router->get('/login', [AuthController::class, 'loginView']);
    $router->get('/register', [AuthController::class, 'registerView']);

    // User Authentication API
    $router->post('/register', [UserController::class, 'register']);
    $router->post('/login', [UserController::class, 'login']);
    $router->post('/profile/update', [UserController::class, 'updateProfile']);
    $router->post('/password/reset/request', [UserController::class, 'requestPasswordReset']);
    $router->post('/password/reset', [UserController::class, 'resetPassword']);

    // Password Reset Views
    $router->get('/password/reset', [AuthController::class, 'passwordResetRequestView']);
    $router->get('/password/reset/confirm', [AuthController::class, 'passwordResetView']);

    // Payment API Routes
    $router->post('/payments/process', [PaymentController::class, 'processPayment']);
    $router->post('/payments/refund', [PaymentController::class, 'refundPayment']);
    $router->post('/payments/installments', [PaymentController::class, 'setupInstallment']);
    $router->get('/payments/transactions', [PaymentController::class, 'getUserTransactions']);

    // Payment Views
    $router->get('/payments/history', [PaymentController::class, 'viewPaymentHistory']);
    $router->get('/payments/installments', [PaymentController::class, 'viewInstallments']);
    $router->get('/payments/refunds', [PaymentController::class, 'viewRefunds']);

    // Signature API Routes
    $router->post('/signature/upload', [SignatureController::class, 'uploadSignature']);
    $router->get('/signature/verify/{userId}/{documentHash}', [SignatureController::class, 'verifySignature']);
    $router->get('/signature/{userId}', [SignatureController::class, 'getSignature']);

    // Booking Actions
    $router->post('/bookings/{id}/reschedule', [BookingController::class, 'rescheduleBooking']);
    $router->post('/bookings/{id}/cancel', [BookingController::class, 'cancelBooking']);

    // Notifications API
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

    // User Report Routes
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
