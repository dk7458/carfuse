<?php
require_once __DIR__ . '/../config/routes.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

startSecureSession();

// Get the requested URL path
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ✅ Prevent API requests from being misrouted
if (strpos($requestUri, '/api/') === 0) {
    http_response_code(404);
    echo json_encode(["error" => "Invalid API request"]);
    exit;
}

// ✅ Serve home page for `/`
if ($requestUri === '/' || $requestUri === '/index.php') {
    require __DIR__ . '/views/home.php';
    exit;
}

// ✅ Serve other views dynamically
$viewPath = __DIR__ . "/views$requestUri.php";
if (file_exists($viewPath)) {
    require $viewPath;
    exit;
}

// 🚀 If no matching view, show 404 page
http_response_code(404);
echo "404 Not Found";
?>
