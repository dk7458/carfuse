<?php

use FastRoute\RouteCollector;
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

$router = new RouteCollector();

// Welcome Page
$router->get('/', function () {
    echo 'Welcome to Carfuse!';
});

// Authentication Views
$router->get('login', [AuthController::class, 'loginView']);
$router->get('register', [AuthController::class, 'registerView']);

// Password Reset Views
$router->get('password/reset', [AuthController::class, 'passwordResetRequestView']);
$router->get('password/reset/confirm', [AuthController::class, 'passwordResetView']);

// Payment Views
$router->get('payments/history', [PaymentController::class, 'viewPaymentHistory']);
$router->get('payments/installments', [PaymentController::class, 'viewInstallments']);
$router->get('payments/refunds', [PaymentController::class, 'viewRefunds']);

// Payment API Routes
$router->post('payment/process', [PaymentController::class, 'processPayment']);
$router->post('payment/refund', [PaymentController::class, 'refundPayment']);
$router->post('payment/installment', [PaymentController::class, 'setupInstallment']);
$router->get('payment/transactions/{user_id}', [PaymentController::class, 'getUserTransactions']);

// Dashboard
$router->get('dashboard', [DashboardController::class, 'userDashboard']);
$router->get('bookings', [DashboardController::class, 'getUserBookings']);

// Booking Actions
$router->get('bookings/{id}', [BookingController::class, 'viewBooking']);
$router->post('bookings/{id}/reschedule', [BookingController::class, 'rescheduleBooking']);
$router->post('bookings/{id}/cancel', [BookingController::class, 'cancelBooking']);

// Notifications API Routes
$router->get('notifications', [NotificationController::class, 'getNotifications']);
$router->post('notifications/mark-as-read', [NotificationController::class, 'markAsRead']);
$router->post('notifications/delete', [NotificationController::class, 'deleteNotification']);
$router->post('notifications/send', [NotificationController::class, 'sendNotification']);

// Notification Queue Processing
$router->post('notifications/process-queue', [NotificationQueueController::class, 'processQueue']);

// Admin Dashboard
$router->get('admin/dashboard', [AdminDashboardController::class, 'index']);
$router->get('admin/dashboard/data', [AdminDashboardController::class, 'getDashboardData']);

// Admin Report Routes
$router->get('admin/reports', [ReportController::class, 'viewAdminReports']);
$router->post('admin/reports/generate', [ReportController::class, 'generateAdminReport']);

// User Report Routes
$router->get('user/reports', [ReportController::class, 'viewUserReports']);
$router->post('user/reports/generate', [ReportController::class, 'generateUserReport']);

// Audit Log Routes
$router->get('admin/audit-logs', [AuditController::class, 'index']);
$router->post('admin/audit-logs/fetch', [AuditController::class, 'fetchLogs']);
$router->post('admin/audit-logs/log', [AuditController::class, 'logAction']);

// Document Manager Routes
$router->post('documents/upload-template', [DocumentController::class, 'uploadTemplate']);
$router->post('documents/generate-contract/{bookingId}/{userId}', [DocumentController::class, 'generateContract']);
$router->post('documents/upload-terms', [DocumentController::class, 'uploadTerms']);
$router->post('documents/generate-invoice/{bookingId}', [DocumentController::class, 'generateInvoice']);
$router->delete('documents/{documentId}', [DocumentController::class, 'deleteDocument']);

return $router;
