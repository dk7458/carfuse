<?php
declare(strict_types=1);
header("Content-Type: text/html; charset=UTF-8");

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../app/helpers/SecurityHelper.php';
require_once __DIR__ . '/../config/routes.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logFile = __DIR__ . '/../logs/debug.log';
file_put_contents($logFile, "[Index] Request: " . $_SERVER['REQUEST_URI'] . PHP_EOL, FILE_APPEND);

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($requestUri === '/') {
    require __DIR__ . '/../views/home.php';
    exit();
}

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) use ($routes) {
    foreach ($routes as $route => $file) {
        $r->addRoute('GET', "/$route", $file);
    }
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$routeInfo = $dispatcher->dispatch($httpMethod, $requestUri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode(["error" => "Not Found"]);
        file_put_contents($logFile, "[Index] 404 Not Found: $requestUri" . PHP_EOL, FILE_APPEND);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo json_encode(["error" => "Method Not Allowed"]);
        file_put_contents($logFile, "[Index] 405 Method Not Allowed: $requestUri" . PHP_EOL, FILE_APPEND);
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        require __DIR__ . "/../views/$handler";
        file_put_contents($logFile, "[Index] 200 OK: $requestUri" . PHP_EOL, FILE_APPEND);
        break;
}
exit();
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
