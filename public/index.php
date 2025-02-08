<?php
declare(strict_types=1);
header("Content-Type: text/html; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

// Define available routes
$routes = [
    '/' => 'home.php',
    '/dashboard' => 'views/user/dashboard.php',
    '/admin/dashboard' => 'views/admin/dashboard.php',
    '/bookings' => 'views/user/bookings.php',
    '/payments' => 'views/user/payments.php',
    '/documents' => 'views/user/documents.php',
];

// Get current path
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestedRoute = rtrim($requestUri, '/');

// Route dynamically or return 404
if (array_key_exists($requestedRoute, $routes)) {
    $page = __DIR__ . '/' . $routes[$requestedRoute];
} else {
    http_response_code(404);
    $page = __DIR__ . '/views/errors/404.php';
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfuse - Wynajmij auto szybko i łatwo</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <script src="/public/js/shared.js" defer></script>
</head>
<body>

<?php include __DIR__ . '/layouts/header.php'; ?>

<!-- Dynamically load content -->
<div id="content">
    <?php include $page; ?>
</div>

<?php include __DIR__ . '/layouts/footer.php'; ?>

</body>
</html>
