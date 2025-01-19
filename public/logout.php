<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /public/login.php');
    exit;
}

// Set session timeout duration (in seconds)
$timeout_duration = 1800; // 30 minutes

// Check if the session has timed out
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: /public/login.php');
    exit;
}

// Update last activity time stamp
$_SESSION['LAST_ACTIVITY'] = time();

session_unset();
session_destroy();

header('Location: /public/login.php');
exit;
?>
