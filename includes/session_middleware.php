<?php
// Start a session if none is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Whitelist of pages that do not require authentication
$whitelist = [
    '/public/index.php',
    '/public/login.php',
    '/public/register.php',
    '/public/reset_password.php',
    // Add other public pages here
];

// Check if the current page is in the whitelist
$current_page = $_SERVER['SCRIPT_NAME'];
if (!in_array($current_page, $whitelist)) {
    // Redirect to login if the user is not authenticated
    if (!isset($_SESSION['user_id'])) {
        // Store the intended destination to redirect after login
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        header('Location: /public/login.php');
        exit;
    }

    // Regenerate session ID periodically to enhance security
    if (!isset($_SESSION['last_regeneration_time'])) {
        $_SESSION['last_regeneration_time'] = time();
    } elseif (time() - $_SESSION['last_regeneration_time'] > 1800) { // Regenerate every 30 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration_time'] = time();
    }

    // Set session timeout
    $timeout_duration = 3600; // 1 hour
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // Log the timeout event (optional)
        error_log("Session timed out for user_id: " . $_SESSION['user_id']);

        // Unset all session variables and destroy the session
        session_unset();
        session_destroy();

        // Redirect with a timeout notice
        header('Location: /public/login.php?timeout=true');
        exit;
    }
    $_SESSION['last_activity'] = time();

    // CSRF token generation and validation
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Role-based access control (optional)
    $role_restricted_pages = [
        '/admin/dashboard.php' => 'admin',
        '/admin/settings.php' => 'admin',
    ];

    if (array_key_exists($current_page, $role_restricted_pages)) {
        $required_role = $role_restricted_pages[$current_page];
        if ($_SESSION['role'] !== $required_role) {
            header('Location: /public/403.php'); // Redirect to a 403 Forbidden page
            exit;
        }
    }
}
?>

