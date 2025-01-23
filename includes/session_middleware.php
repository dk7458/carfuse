<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

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

// Current page
$current_page = $_SERVER['SCRIPT_NAME'];

// Redirect unauthenticated users to login
if (!in_array($current_page, $whitelist)) {
    // Ensure the user is authenticated
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        header('Location: /public/login.php');
        exit;
    }

    // Regenerate session ID for security (every 30 minutes)
    if (!isset($_SESSION['last_regeneration_time'])) {
        $_SESSION['last_regeneration_time'] = time();
    } elseif (time() - $_SESSION['last_regeneration_time'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration_time'] = time();
    }

    // Session timeout (1 hour of inactivity)
    $timeout_duration = 3600; // 1 hour
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // Log the timeout event (optional)
        error_log("Session timed out for user_id: " . $_SESSION['user_id']);

        // Destroy the session and redirect to login with a timeout notice
        session_unset();
        session_destroy();
        header('Location: /public/login.php?timeout=true');
        exit;
    }
    $_SESSION['last_activity'] = time();

    // Generate CSRF token if not set
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Role-based access control
    $role_restricted_pages = [
        '/admin/dashboard.php' => 'admin',
        '/admin/settings.php' => 'admin',
    ];

    if (array_key_exists($current_page, $role_restricted_pages)) {
        $required_role = $role_restricted_pages[$current_page];
        if ($_SESSION['role'] !== $required_role) {
            // Log unauthorized access attempt (optional)
            error_log("Unauthorized access attempt to $current_page by user_id: " . $_SESSION['user_id']);

            // Redirect to a 403 Forbidden page
            header('Location: /public/403.php');
            exit;
        }
    }
}
