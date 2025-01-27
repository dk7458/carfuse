<?php

// User API Routes
$router->post('/register', [App\Controllers\UserController::class, 'register']);
$router->post('/login', [App\Controllers\UserController::class, 'login']);
$router->post('/profile/update', [App\Controllers\UserController::class, 'updateProfile']);
$router->post('/password/reset/request', [App\Controllers\UserController::class, 'requestPasswordReset']);
$router->post('/password/reset', [App\Controllers\UserController::class, 'resetPassword']);

// Payment API Routes
$router->post('/payments/process', [App\Controllers\PaymentController::class, 'processPayment']);
$router->post('/payments/refund', [App\Controllers\PaymentController::class, 'refundPayment']);
$router->post('/payments/installments', [App\Controllers\PaymentController::class, 'setupInstallment']);
$router->get('/payments/transactions', [App\Controllers\PaymentController::class, 'getUserTransactions']);

return $router;
