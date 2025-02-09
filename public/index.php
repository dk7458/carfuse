<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/routes.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

startSecureSession();
// Debugging: List available views
$viewsDirectory = __DIR__ . "/views/";
$availableViews = scandir($viewsDirectory);
echo "<pre>Available Views:\n" . print_r($availableViews, true) . "</pre>";

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
} else {
    echo "View not found: " . htmlspecialchars($viewPath); // Debugging output
}

// 🚀 If no matching view, show 404 page
http_response_code(404);
echo "404 Not Found";
?>
