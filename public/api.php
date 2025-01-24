<?php
/**
 * File: /public_html/public/api.php
 * Description: Centralized API proxy to route requests to appropriate controllers with rate limiting and logging.
 */

require_once '../config.php'; // Global configuration
require_once BASE_PATH . 'includes/global.php'; // Global functions

// Define endpoint categories
$publicEndpoints = ['notifications', 'login', 'register']; // Publicly accessible endpoints
$sensitiveEndpoints = [
    'user', 'admin', 'fleet', 'report', 'settings', 'logs', 'account', 'admin_booking', 
    'admin_notification', 'booking', 'calendar', 'contract', 'dashboard', 'dashboard_summary', 
    'dynamic_pricing', 'export', 'maintenance', 'payment', 'payment_methods', 'payment_status', 
    'summary', 'super_admin', 'user_profile'
];

// Get endpoint and action from the request
$endpoint = $_GET['endpoint'] ?? null;
$action = $_GET['action'] ?? null;

// Logging function
function logApiRequest($endpoint, $action, $status, $message = '') {
    $logFile = BASE_PATH . '/logs/api_requests.log';
    $logMessage = sprintf(
        "[%s] Endpoint: %s | Action: %s | Status: %s | Message: %s\n",
        date('Y-m-d H:i:s'),
        $endpoint,
        $action,
        $status,
        $message
    );
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Rate limiting function
function enforceRateLimit($ip, $limit = 100, $timeWindow = 3600) {
    $rateLimitFile = BASE_PATH . '/logs/rate_limit.json';

    // Load or initialize rate limit data
    $rateLimits = file_exists($rateLimitFile) ? json_decode(file_get_contents($rateLimitFile), true) : [];
    $currentTime = time();

    // Check IP's rate limit
    if (!isset($rateLimits[$ip])) {
        $rateLimits[$ip] = ['count' => 1, 'start_time' => $currentTime];
    } else {
        if ($currentTime - $rateLimits[$ip]['start_time'] < $timeWindow) {
            $rateLimits[$ip]['count']++;
            if ($rateLimits[$ip]['count'] > $limit) {
                return false; // Rate limit exceeded
            }
        } else {
            // Reset rate limit for a new window
            $rateLimits[$ip] = ['count' => 1, 'start_time' => $currentTime];
        }
    }

    // Save rate limit data
    file_put_contents($rateLimitFile, json_encode($rateLimits));
    return true;
}

try {
    // Enforce rate limiting
    $clientIp = $_SERVER['REMOTE_ADDR'];
    if (!enforceRateLimit($clientIp)) {
        logApiRequest($endpoint, $action, 'rate_limited', 'Rate limit exceeded');
        header('HTTP/1.1 429 Too Many Requests');
        echo json_encode(['success' => false, 'error' => 'Rate limit exceeded']);
        exit;
    }

    // Ensure a valid endpoint is provided
    if (!$endpoint) {
        logApiRequest($endpoint, $action, 'error', 'Missing endpoint parameter');
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['success' => false, 'error' => 'Missing endpoint parameter']);
        exit;
    }

    if (in_array($endpoint, $publicEndpoints)) {
        // Public endpoints
        $controllerPath = BASE_PATH . "controllers/{$endpoint}_ctrl.php";
    } elseif (in_array($endpoint, $sensitiveEndpoints)) {
        // Sensitive endpoints require authentication
        if (!isAuthenticated()) {
            throw new Exception('Unauthorized access.');
        }

        // Check if the user has the required role
        if (!hasAccess('admin')) { // Example role check
            throw new Exception('Insufficient permissions.');
        }

        $controllerPath = BASE_PATH . "controllers/{$endpoint}_ctrl.php";
    } else {
        throw new Exception('Invalid endpoint.');
    }

    // Ensure the controller file exists
    if (!file_exists($controllerPath)) {
        throw new Exception("Controller file for endpoint '{$endpoint}' not found.");
    }

    // Include the controller
    require_once $controllerPath;
    logApiRequest($endpoint, $action, 'success', 'Request handled successfully');
} catch (Exception $e) {
    // Log and handle errors
    logApiRequest($endpoint, $action, 'error', $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
