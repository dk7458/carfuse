<?php

// Auth routes
$router->post('/auth/login', [App\Controllers\AuthController::class, 'login']);
$router->post('/auth/register', [App\Controllers\AuthController::class, 'register']);
$router->post('/auth/refresh', [App\Controllers\AuthController::class, 'refreshToken']);
$router->post('/auth/logout', [App\Controllers\AuthController::class, 'logout']);
$router->post('/auth/reset-request', [App\Controllers\AuthController::class, 'resetPasswordRequest']);
$router->post('/auth/reset', [App\Controllers\AuthController::class, 'resetPassword']);

// User routes (all protected by authentication middleware)
$router->get('/user/profile', [App\Controllers\UserController::class, 'getUserProfile']);
$router->put('/user/profile', [App\Controllers\UserController::class, 'updateProfile']);
$router->get('/user/dashboard', [App\Controllers\UserController::class, 'userDashboard']);
$router->get('/admin/dashboard', [App\Controllers\UserController::class, 'adminAction']);
