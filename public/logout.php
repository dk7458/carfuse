<?php
// File Path: /public/logout.php
require_once BASE_PATH . 'includes/session_middleware.php';


// Destroy session and log out user
if (isset($_SESSION['user_id'])) {
    logAction($_SESSION['user_id'], 'logout', 'Użytkownik wylogowany.');
}

session_unset();
session_destroy();

header('Location: /public/index.php');
exit;
?>
