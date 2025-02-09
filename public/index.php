<?php
declare(strict_types=1);
header("Content-Type: text/html; charset=UTF-8");

// Ensure SecurityHelper is loaded early to initialize sessions correctly.
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';

// Write debug log entry.
file_put_contents(__DIR__ . '/../debug.log', "[" . date('Y-m-d H:i:s') . "] Debug: index.php started\n", FILE_APPEND);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Prevent redundant dispatcher executions.
if (!isset($GLOBALS['dispatcher_executed'])) {
    $GLOBALS['dispatcher_executed'] = true;
    
    // Determine request URI.
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    file_put_contents(__DIR__ . '/../debug.log', "[" . date('Y-m-d H:i:s') . "] Debug: Routing request for URI: $uri\n", FILE_APPEND);
    
    try {
        // Basic routing: if URI is "/" load home.php, otherwise use FastRoute dispatcher.
        if ($uri === '/' || $uri === '/index.php') {
            $route = 'home';
        } else {
            // Example: extract route from URI e.g., "/about" becomes "about"
            $route = trim($uri, '/');
        }
        
        // Dispatch based on route.
        switch ($route) {
            case 'home':
                file_put_contents(__DIR__ . '/../debug.log', "[" . date('Y-m-d H:i:s') . "] Debug: Dispatching to home view\n", FILE_APPEND);
                $viewFile = __DIR__ . '/views/home.php';
                break;
            // ...existing cases for other routes...
            default:
                file_put_contents(__DIR__ . '/../debug.log', "[" . date('Y-m-d H:i:s') . "] Error: No route found for URI: $uri\n", FILE_APPEND);
                throw new Exception("Page not found");
        }
    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/../debug.log', "[" . date('Y-m-d H:i:s') . "] Error: Dispatcher failed: " . $e->getMessage() . "\n", FILE_APPEND);
        echo "<h1>Error:</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
        exit;
    }
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

<!-- Debugging: Log main content rendering -->
<?php
    file_put_contents(__DIR__ . '/../debug.log', "[" . date('Y-m-d H:i:s') . "] Debug: Loading main content from: $viewFile\n", FILE_APPEND);
    include $viewFile;
    file_put_contents(__DIR__ . '/../debug.log', "[" . date('Y-m-d H:i:s') . "] Debug: Content loaded successfully\n", FILE_APPEND);
?>

<?php include __DIR__ . '/layouts/footer.php'; ?>

</body>
</html>
