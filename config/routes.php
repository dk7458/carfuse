<?php

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use App\Controllers\AdminController;
use App\Controllers\AdminDashboardController;
use App\Controllers\AuditController;
use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\DashboardController;
use App\Controllers\DocumentController;
use App\Controllers\NotificationController;
use App\Controllers\PaymentController;
use App\Controllers\ReportController;
use App\Controllers\SignatureController;
use App\Controllers\UserController;
use DI\Container;

return function (Container $container) {
    return simpleDispatcher(function (RouteCollector $router) use ($container) {
        // Get controller instances from the container
        $authController = $container->get(AuthController::class);
        $userController = $container->get(UserController::class);
        $adminController = $container->get(AdminController::class);
        $adminDashboardController = $container->get(AdminDashboardController::class);
        $auditController = $container->get(AuditController::class);
        $bookingController = $container->get(BookingController::class);
        $dashboardController = $container->get(DashboardController::class);
        $documentController = $container->get(DocumentController::class);
        $notificationController = $container->get(NotificationController::class);
        $paymentController = $container->get(PaymentController::class);
        $reportController = $container->get(ReportController::class);
        $signatureController = $container->get(SignatureController::class);

        // Basic view routes
        $router->addRoute(['GET'], '/', fn() => require '../public/views/home.php');
        $router->addRoute(['GET'], '/dashboard', fn() => require '../public/views/dashboard.php');
        $router->addRoute(['GET'], '/profile', fn() => require '../public/views/user/profile.php');
        $router->addRoute(['GET'], '/reports', fn() => require '../public/views/user/reports.php');
        $router->addRoute(['GET'], '/auth/login', fn() => require '../public/views/auth/login.php');
        $router->addRoute(['GET'], '/auth/register', fn() => require '../public/views/auth/register.php');
        $router->addRoute(['GET'], '/auth/password_reset', fn() => require '../public/views/auth/password_reset.php');
        $router->addRoute(['GET'], '/documents/signing_page', fn() => require '../public/views/documents/signing_page.php');

        // Auth routes
        $router->addRoute(['POST'], '/api/auth/login', [$authController, 'login']);
        $router->addRoute(['POST'], '/api/auth/register', [$authController, 'register']);
        $router->addRoute(['POST'], '/api/auth/refresh', [$authController, 'refresh']);
        $router->addRoute(['POST'], '/api/auth/logout', [$authController, 'logout']);
        $router->addRoute(['GET'], '/api/auth/user', [$authController, 'userDetails']);
        $router->addRoute(['POST'], '/api/auth/reset-password-request', [$authController, 'resetPasswordRequest']);
        $router->addRoute(['POST'], '/api/auth/reset-password', [$authController, 'resetPassword']);

        // User routes
        $router->addGroup('/api/users', function (RouteCollector $r) use ($userController) {
            $r->addRoute(['POST'], '/register', [$userController, 'registerUser']);
            $r->addRoute(['GET'], '/profile', [$userController, 'getUserProfile']);
            $r->addRoute(['PUT'], '/profile', [$userController, 'updateProfile']);
            $r->addRoute(['POST'], '/password/request', [$userController, 'requestPasswordReset']);
            $r->addRoute(['POST'], '/password/reset', [$userController, 'resetPassword']);
            $r->addRoute(['GET'], '/dashboard', [$userController, 'userDashboard']);
        });

        // Admin routes
        $router->addGroup('/api/admin', function (RouteCollector $r) use ($adminController, $adminDashboardController) {
            $r->addRoute(['GET'], '/users', [$adminController, 'getAllUsers']);
            $r->addRoute(['PUT'], '/users/{userId}/role', [$adminController, 'updateUserRole']);
            $r->addRoute(['DELETE'], '/users/{userId}', [$adminController, 'deleteUser']);
            $r->addRoute(['GET'], '/dashboard/data', [$adminController, 'getDashboardData']);
            $r->addRoute(['POST'], '/create', [$adminController, 'createAdmin']);
            $r->addRoute(['GET'], '/dashboard', [$adminDashboardController, 'index']);
        });

        // Audit routes
        $router->addGroup('/api/audit', function (RouteCollector $r) use ($auditController) {
            $r->addRoute(['GET'], '', [$auditController, 'index']);
            $r->addRoute(['POST'], '/fetch', [$auditController, 'fetchLogs']);
            $r->addRoute(['GET'], '/{id:\d+}', [$auditController, 'getLog']);
            $r->addRoute(['POST'], '/export', [$auditController, 'exportLogs']);
        });

        // Dashboard routes
        $router->addGroup('/api/dashboard', function (RouteCollector $r) use ($dashboardController) {
            $r->addRoute(['GET'], '', [$dashboardController, 'userDashboard']);
            $r->addRoute(['GET'], '/bookings', [$dashboardController, 'getUserBookings']);
            $r->addRoute(['GET'], '/statistics', [$dashboardController, 'fetchStatistics']);
            $r->addRoute(['GET'], '/notifications', [$dashboardController, 'fetchNotifications']);
            $r->addRoute(['GET'], '/profile', [$dashboardController, 'fetchUserProfile']);
        });

        // Booking routes
        $router->addGroup('/api/bookings', function (RouteCollector $r) use ($bookingController) {
            $r->addRoute(['GET'], '/{id:\d+}', [$bookingController, 'viewBooking']);
            $r->addRoute(['POST'], '/{id:\d+}/reschedule', [$bookingController, 'rescheduleBooking']);
            $r->addRoute(['POST'], '/{id:\d+}/cancel', [$bookingController, 'cancelBooking']);
            $r->addRoute(['GET'], '/{bookingId:\d+}/logs', [$bookingController, 'getBookingLogs']);
            $r->addRoute(['GET'], '/user', [$bookingController, 'getUserBookings']);
            $r->addRoute(['POST'], '', [$bookingController, 'createBooking']);
        });

        // Document routes
        $router->addGroup('/api/documents', function (RouteCollector $r) use ($documentController) {
            $r->addRoute(['POST'], '/templates', [$documentController, 'uploadTemplate']);
            $r->addRoute(['GET'], '/templates', [$documentController, 'getTemplates']);
            $r->addRoute(['GET'], '/templates/{templateId:\d+}', [$documentController, 'getTemplate']);
            $r->addRoute(['GET'], '/contracts/{bookingId:\d+}', [$documentController, 'generateContract']);
            $r->addRoute(['POST'], '/terms', [$documentController, 'uploadTerms']);
            $r->addRoute(['GET'], '/invoices/{bookingId:\d+}', [$documentController, 'generateInvoice']);
            $r->addRoute(['DELETE'], '/{documentId:\d+}', [$documentController, 'deleteDocument']);
        });

        // Notification routes
        $router->addGroup('/api/notifications', function (RouteCollector $r) use ($notificationController) {
            $r->addRoute(['GET'], '', [$notificationController, 'viewNotifications']);
            $r->addRoute(['GET'], '/user', [$notificationController, 'getUserNotifications']);
            $r->addRoute(['GET'], '/ajax', [$notificationController, 'fetchNotificationsAjax']);
            $r->addRoute(['POST'], '/mark-read', [$notificationController, 'markNotificationAsRead']);
            $r->addRoute(['POST'], '/delete', [$notificationController, 'deleteNotification']);
            $r->addRoute(['POST'], '/send', [$notificationController, 'sendNotification']);
        });

        // Payment routes
        $router->addGroup('/api/payments', function (RouteCollector $r) use ($paymentController) {
            $r->addRoute(['POST'], '/process', [$paymentController, 'processPayment']);
            $r->addRoute(['POST'], '/refund', [$paymentController, 'refundPayment']);
            $r->addRoute(['GET'], '/transactions', [$paymentController, 'getUserTransactions']);
            $r->addRoute(['GET'], '/{transactionId:\d+}', [$paymentController, 'getPaymentDetails']);
            $r->addRoute(['POST'], '/methods', [$paymentController, 'addPaymentMethod']);
            $r->addRoute(['GET'], '/methods', [$paymentController, 'getUserPaymentMethods']);
            $r->addRoute(['POST'], '/gateway/process', [$paymentController, 'processGatewayPayment']);
            $r->addRoute(['POST', 'GET'], '/gateway/callback/{gateway}', [$paymentController, 'handleGatewayCallback']);
        });

        // Report routes
        $router->addGroup('/api/reports', function (RouteCollector $r) use ($reportController) {
            $r->addRoute(['GET'], '', [$reportController, 'index']);
            $r->addRoute(['POST'], '/generate', [$reportController, 'generateReport']);
            $r->addRoute(['GET'], '/user', [$reportController, 'userReports']);
            $r->addRoute(['POST'], '/user/generate', [$reportController, 'generateUserReport']);
        });

        // Signature routes
        $router->addGroup('/api/signatures', function (RouteCollector $r) use ($signatureController) {
            $r->addRoute(['POST'], '/upload', [$signatureController, 'uploadSignature']);
            $r->addRoute(['POST'], '/verify/{userId:\d+}', [$signatureController, 'verifySignature']);
            $r->addRoute(['GET'], '/{userId:\d+}', [$signatureController, 'getSignature']);
        });

        // Settings routes
        $router->addGroup('/admin/api/settings', function (RouteCollector $r) {
            $r->addRoute(['GET'], '', 'App\Controllers\SettingsController:getSettings');
            $r->addRoute(['POST'], '/save', 'App\Controllers\SettingsController:saveSettings');
        });

        // Catch-All for Unmatched Requests
        $router->addRoute(['GET', 'POST'], '/{any:.+}', function () {
            http_response_code(404);
            echo json_encode(["error" => "Not Found"]);
        });
    });
};
