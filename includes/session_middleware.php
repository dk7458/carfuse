<?php
// Start a session if none is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if the user is not authenticated
if (!isset($_SESSION['user_id'])) {
    // Optional: Store the intended destination to redirect after login
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
    header('Location: /public/login.php');
    exit;
}

// Optional: Regenerate session ID periodically to enhance security
if (!isset($_SESSION['last_regeneration_time'])) {
    $_SESSION['last_regeneration_time'] = time();
} elseif (time() - $_SESSION['last_regeneration_time'] > 1800) { // Regenerate every 30 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration_time'] = time();
}

// Additional: Set session timeout (optional)
$timeout_duration = 3600; // 1 hour
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header('Location: /public/login.php?timeout=true'); // Redirect with a timeout notice
    exit;
}
$_SESSION['last_activity'] = time();
?>
