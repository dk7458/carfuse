<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

require_once __DIR__ . '/../vendor/autoload.php';
use App\Helpers\DatabaseHelper;

header('Content-Type: application/json');

try {
    $db = DatabaseHelper::getInstance();
    $pdo = $db->getConnection()->getPdo();

    if ($pdo) {
        echo json_encode(["status" => "success", "message" => "Database connection successful", "database" => $_ENV['DB_DATABASE']]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to obtain PDO instance"]);
    }
} catch (Exception $e) {
    error_log("[DEBUG] Database connection test failed: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Database connection failed", "error" => $e->getMessage()]);
}
