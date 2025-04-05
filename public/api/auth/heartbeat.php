<?php
/**
 * Session Heartbeat API
 * Keeps user sessions alive during periods of activity
 */

// Include bootstrap
require_once dirname(dirname(dirname(__DIR__))) . '/app/bootstrap.php';

// Apply security middleware to validate CSRF token
\App\Middleware\SecurityMiddleware::apply();

// Check if user is authenticated
if (!\App\Services\SecurityService::isAuthenticated()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Użytkownik nie jest zalogowany'
    ]);
    exit;
}

// Refresh session timestamp
$_SESSION['last_activity'] = time();

// Return success response
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'Sesja odświeżona',
    'timestamp' => time(),
    'expires' => time() + \App\Services\SecurityService::SESSION_LIFETIME
]);
