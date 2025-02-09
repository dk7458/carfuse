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
file_put_contents($logFile, "[INDEX] Request URI: " . $_SERVER['REQUEST_URI'] . PHP_EOL, FILE_APPEND);

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
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

// Log route dispatch information
file_put_contents($logFile, "[INDEX] Route Info: " . print_r($routeInfo, true) . PHP_EOL, FILE_APPEND);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo '404 Not Found';
        file_put_contents($logFile, "[INDEX] 404 Not Found" . PHP_EOL, FILE_APPEND);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        echo '405 Method Not Allowed';
        file_put_contents($logFile, "[INDEX] 405 Method Not Allowed" . PHP_EOL, FILE_APPEND);
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        // Log the handler and variables
        file_put_contents($logFile, "[INDEX] Handler: $handler, Vars: " . print_r($vars, true) . PHP_EOL, FILE_APPEND);
        require __DIR__ . "/../views/$handler";
        file_put_contents($logFile, "[INDEX] 200 OK: $requestUri" . PHP_EOL, FILE_APPEND);
        break;
}
exit();
?>
