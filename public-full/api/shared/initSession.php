<?php

$apiLogFile = __DIR__ . '/../../logs/api.log';

function logApiError($message) {
    global $apiLogFile;
    file_put_contents($apiLogFile, date('Y-m-d H:i:s') . " - $message\n", FILE_APPEND);
}

function isProtectedRoute($route) {
    $protectedRoutes = [
        '/profile/update',
        '/password/reset/request',
        '/password/reset',
        '/payments/process',
        '/payments/refund',
        '/bookings',
        '/notifications',
        '/admin',
        '/documents',
        '/api'
    ];
    foreach ($protectedRoutes as $protectedRoute) {
        if (strpos($route, $protectedRoute) === 0) {
            return true;
        }
    }
    return false;
}

session_start();

$requestUri = $_SERVER['REQUEST_URI'];

if (isProtectedRoute($requestUri)) {
    if (!isset($_SESSION['user_id'])) {
        logApiError("Unauthorized access attempt to $requestUri");
        http_response_code(403);
        echo json_encode(["error" => "Unauthorized"]);
        exit();
    }
}

// Allow guest users to access the homepage without authentication
if ($requestUri === '/' || $requestUri === '/home') {
    return;
}

// Set the response header to JSON
header('Content-Type: application/json');

// Check if the session is valid
if (validateSessionIntegrity()) {
    // Session is active
    $response = [
        'status' => 'active',
        'user_id' => $_SESSION['user_id']
    ];
    echo json_encode($response);
} else {
    // Session is invalid
    http_response_code(403);
    $response = [
        'error' => 'Unauthorized'
    ];
    echo json_encode($response);

    // Log unauthorized session attempts
    $logMessage = sprintf("[%s] Unauthorized session attempt from IP: %s\n", date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR']);
    file_put_contents(__DIR__ . '/../../logs/api.log', $logMessage, FILE_APPEND);
}
?>
