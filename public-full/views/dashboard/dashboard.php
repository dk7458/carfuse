<?php
require_once __DIR__ . '/../../helpers/SecurityHelper.php';
$userRole = $_SESSION['user_role'] ?? 'user'; // Assume 'user' role if not set

$module = $_GET['module'] ?? 'home';
$view = __DIR__ . "/dashboard_modules/{$module}.php";

if (!file_exists($view)) {
    $view = __DIR__ . "/dashboard_modules/404.php"; // Load 404 if module doesn't exist
}

// Removed any redundant <script> tags for main.js to prevent double loading

include __DIR__ . '/../layouts/main.php';
?>
