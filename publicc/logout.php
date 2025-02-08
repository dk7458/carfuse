<?php



setcookie("PHPSESSID", "", time() - 3600, "/");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset();
session_destroy();
header("Location: /");
exit;

http_response_code(200);
echo json_encode(["message" => "Logged out successfully."]);
