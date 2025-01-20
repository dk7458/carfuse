<?php
// helpers.php

// Redirect to a specified URL
function redirect($url) {
    header("Location: $url");
    exit;
}

// Sanitize input to prevent XSS attacks
function sanitizeInput($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

// Generate a random token for security purposes
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Format date to a user-friendly format
function formatDate($date, $format = 'd-m-Y H:i') {
    return date($format, strtotime($date));
}

// Log error messages to a file
function logError($message) {
    error_log("[" . date('Y-m-d H:i:s') . "] ERROR: $message\n", 3, __DIR__ . '/errors.log');
}

// Check if the user is authenticated
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

// Verify CSRF token
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Paginate results
function paginate($data, $perPage, $currentPage) {
    $total = count($data);
    $start = ($currentPage - 1) * $perPage;
    $pagedData = array_slice($data, $start, $perPage);

    return [
        'data' => $pagedData,
        'total' => $total,
        'current_page' => $currentPage,
        'total_pages' => ceil($total / $perPage)
    ];
}
?>

