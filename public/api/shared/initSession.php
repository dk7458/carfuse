<?php
require_once __DIR__ . '/../../App/Helpers/SecurityHelper.php';

// Start a secure session
session_start();

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
