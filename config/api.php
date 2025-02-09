<?php
$endpoint = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$apiPath = __DIR__ . "/../public/api/$endpoint.php";

if (file_exists($apiPath)) {
    require $apiPath;
} else {
    http_response_code(404);
    file_put_contents(__DIR__ . '/../logs/debug.log', date('Y-m-d H:i:s') . " - API Not Found: $endpoint\n", FILE_APPEND);
    echo json_encode(["error" => "API Not Found"]);
}
exit;
?>
