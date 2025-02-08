<?php
$module = $_GET['module'] ?? 'home';
$view = __DIR__ . "/dashboard_modules/{$module}.php";

if (!file_exists($view)) {
    $view = __DIR__ . "/dashboard_modules/404.php"; // Load 404 if module doesn't exist
}

include __DIR__ . '/../layouts/main.php';
?>
