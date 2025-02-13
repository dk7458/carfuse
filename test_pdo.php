<?php

// ✅ Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Set headers for JSON response
header('Content-Type: application/json');

// ✅ Load environment variables if using .env
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// ✅ Load .env configuration
$dotenv = Dotenv::createImmutable(__DIR__ . '/');
$dotenv->safeLoad();

// ✅ Database Credentials (From .env or Hardcoded)
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_DATABASE'] ?? 'app_db';
$dbUser = $_ENV['DB_USERNAME'] ?? 'root';
$dbPass = $_ENV['DB_PASSWORD'] ?? '';

// ✅ Connection string
$dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";

try {
    // ✅ Establish PDO Connection
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Database connected successfully!"
    ]);

    // ✅ Execute a test query
    $stmt = $pdo->query("SHOW TABLES;");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        "tables" => $tables
    ]);

} catch (PDOException $e) {
    // ✅ Log & Return Connection Error
    error_log("[PDO ERROR] " . $e->getMessage(), 3, __DIR__ . "/logs/errors.log");
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed",
        "error" => $e->getMessage()
    ]);
}
