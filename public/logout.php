<?php


session_destroy();
setcookie("PHPSESSID", "", time() - 3600, "/");

http_response_code(200);
echo json_encode(["message" => "Logged out successfully."]);
