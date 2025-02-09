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
    if (!function_exists('requireAuth')) {
        function requireAuth() {
            require_once BASE_PATH . '/App/Helpers/SecurityHelper.php';
            if (!isUserLoggedIn()) {
                http_response_code(403);
                echo json_encode(["error" => "Unauthorized"]);
                exit();
            }
        }
    }

    // Home Page: now use a dedicated view to avoid recursion
    $router->get('/', function () {
        file_put_contents(__DIR__ . "/../debug.log", "Home Route Hit\n", FILE_APPEND);
        require BASE_PATH . "/public/views/home.php"; // use a dedicated home view file
    });
    

    // Authentication Routes (Static First)
    $router->get('/login', [AuthController::class, 'loginView']);
    $router->get('/register', [AuthController::class, 'registerView']);
    $router->post('/register', [UserController::class, 'register']);
    $router->post('/login', [UserController::class, 'login']);
    $router->post('/profile/update', [UserController::class, 'updateProfile']);
    $router->post('/password/reset/request', [UserController::class, 'requestPasswordReset']);
    $router->post('/password/reset', [UserController::class, 'resetPassword']);

    // Dashboard & Profile (Static First)
    $router->get('/dashboard', [DashboardController::class, 'userDashboard']);
    $router->get('/profile', function () { require BASE_PATH . "/public/views/user/profile.php"; });

    // Payments (Static First)
    $router->get('/payments/history', [PaymentController::class, 'viewPaymentHistory']);
    $router->get('/payments/installments', [PaymentController::class, 'viewInstallments']);
    $router->post('/payments/process', [PaymentController::class, 'processPayment']);
    $router->post('/payments/refund', [PaymentController::class, 'refundPayment']);

    // Bookings (Static First)
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

    // Dynamic API Routing (Auth Required)
    $router->get('/api/{endpoint}', function ($vars) {
        requireAuth();
        $endpoint = $vars['endpoint'];
        $apiPath = BASE_PATH . "/public/api/$endpoint.php";

        if (file_exists($apiPath)) {
            require $apiPath;
        } else {
            http_response_code(404);
            echo json_encode(["error" => "API endpoint not found"]);
        }
    });

    // Admin Routes (Static First)
    $router->get('/admin/dashboard', [AdminDashboardController::class, 'index']);
    $router->get('/admin/reports', [ReportController::class, 'index']);
    $router->post('/admin/reports/generate', [ReportController::class, 'generateReport']);
    $router->post('/user/reports/generate', [ReportController::class, 'generateUserReport']);

    // Audit Logs
    $router->post('/admin/audit-logs/fetch', [AuditController::class, 'fetchLogs']);

    // Document Manager Routes
    $router->post('/documents/upload-template', [DocumentController::class, 'uploadTemplate']);
    $router->post('/documents/generate-contract/{bookingId}/{userId}', [DocumentController::class, 'generateContract']);
    $router->post('/documents/upload-terms', [DocumentController::class, 'uploadTerms']);
    $router->post('/documents/generate-invoice/{bookingId}', [DocumentController::class, 'generateInvoice']);
    $router->delete('/documents/{documentId}', [DocumentController::class, 'deleteDocument']);

    // Dynamic View Routing (Must be Last)
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
});
