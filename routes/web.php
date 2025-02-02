<?php

// Welcome Page
$router->get('/', function () {
    echo 'Welcome to Carfuse!';
});

// Authentication Views
$router->get('login', [App\Controllers\AuthController::class, 'loginView']);
$router->get('register', [App\Controllers\AuthController::class, 'registerView']);

// Password Reset Views
$router->get('password/reset', [App\Controllers\AuthController::class, 'passwordResetRequestView']);
$router->get('password/reset/confirm', [App\Controllers\AuthController::class, 'passwordResetView']);

// Payment Views
$router->get('payments/history', [App\Controllers\PaymentController::class, 'viewPaymentHistory']);
$router->get('payments/installments', [App\Controllers\PaymentController::class, 'viewInstallments']);
$router->get('payments/refunds', [App\Controllers\PaymentController::class, 'viewRefunds']);

// Payment API Routes
$router->post('payment/process', [App\Controllers\PaymentController::class, 'processPayment']);
$router->post('payment/refund', [App\Controllers\PaymentController::class, 'refundPayment']);
$router->post('payment/installment', [App\Controllers\PaymentController::class, 'setupInstallment']);
$router->get('payment/transactions/{user_id}', [App\Controllers\PaymentController::class, 'getUserTransactions']);

// Dashboard
$router->get('dashboard', [App\Controllers\DashboardController::class, 'userDashboard']);
$router->get('bookings', [App\Controllers\DashboardController::class, 'getUserBookings']);

// Booking Actions
$router->get('bookings/{id}', [App\Controllers\BookingController::class, 'viewBooking']);
$router->post('bookings/{id}/reschedule', [App\Controllers\BookingController::class, 'rescheduleBooking']);
$router->post('bookings/{id}/cancel', [App\Controllers\BookingController::class, 'cancelBooking']);

// Notifications API Routes
$router->get('notifications', [App\Controllers\NotificationController::class, 'getNotifications']);
$router->post('notifications/mark-as-read', [App\Controllers\NotificationController::class, 'markAsRead']);
$router->post('notifications/delete', [App\Controllers\NotificationController::class, 'deleteNotification']);
$router->post('notifications/send', [App\Controllers\NotificationController::class, 'sendNotification']);

// Notification Queue Processing
$router->post('notifications/process-queue', [App\Controllers\NotificationQueueController::class, 'processQueue']);

// Admin Dashboard
$router->get('admin/dashboard', [App\Controllers\AdminDashboardController::class, 'index']);
$router->get('admin/dashboard/data', [App\Controllers\AdminDashboardController::class, 'getDashboardData']);

// Admin Report Routes
$router->get('admin/reports', [App\Controllers\ReportController::class, 'viewAdminReports']);
$router->post('admin/reports/generate', [App\Controllers\ReportController::class, 'generateAdminReport']);

// User Report Routes
$router->get('user/reports', [App\Controllers\ReportController::class, 'viewUserReports']);
$router->post('user/reports/generate', [App\Controllers\ReportController::class, 'generateUserReport']);

// Audit Log Routes
$router->get('admin/audit-logs', [AuditManager\Controllers\AuditController::class, 'index']);
$router->post('admin/audit-logs/fetch', [AuditManager\Controllers\AuditController::class, 'fetchLogs']);
$router->post('admin/audit-logs/log', [AuditManager\Controllers\AuditController::class, 'logAction']);

// Document Manager Routes
$router->post('documents/upload-template', [DocumentManager\Controllers\DocumentController::class, 'uploadTemplate']);
$router->post('documents/generate-contract/{bookingId}/{userId}', [DocumentManager\Controllers\DocumentController::class, 'generateContract']);
$router->post('documents/upload-terms', [DocumentManager\Controllers\DocumentController::class, 'uploadTerms']);
$router->post('documents/generate-invoice/{bookingId}', [DocumentManager\Controllers\DocumentController::class, 'generateInvoice']);
$router->delete('documents/{documentId}', [DocumentManager\Controllers\DocumentController::class, 'deleteDocument']);

return $router;
