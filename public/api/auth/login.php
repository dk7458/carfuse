<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../App/Helpers/SecurityHelper.php';
require_once __DIR__ . '/../../../App/Helpers/DatabaseHelper.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Helpers\DatabaseHelper;

// ✅ Set Headers
header('Content-Type: application/json');

// ✅ Check for POST Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    logEvent('security', "Method Not Allowed: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// ✅ Retrieve JSON Input
$data = json_decode(file_get_contents("php://input"), true);

// ✅ Validate Input
if (empty($data['email']) || empty($data['password'])) {
    http_response_code(400);
    logEvent('auth', "Login failed: Missing email or password.");
    echo json_encode(["error" => "Missing email or password"]);
    exit;
}

$email = trim($data['email']);
$password = trim($data['password']);

try {
    // ✅ Initialize Eloquent ORM
    DatabaseHelper::getInstance();

    logEvent('database', "Checking user login for email: $email"); // 🔍 Debugging step

    // ✅ Check If User Exists Using Eloquent
    $user = Capsule::table('users')->where('email', $email)->first();

    if (!$user) {
        logEvent('security', "Failed login attempt - User not found: $email");
        http_response_code(401);
        echo json_encode(["error" => "Invalid email or password"]);
        exit;
    }

    if (!password_verify($password, $user->password)) {
        logEvent('security', "Failed login attempt - Incorrect password: $email");
        http_response_code(401);
        echo json_encode(["error" => "Invalid email or password"]);
        exit;
    }

    logEvent('auth', "User ID {$user->id} logged in successfully.");
    
    // ✅ Generate JWT Token
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'default_secret_key';
    $issuedAt = time();
    $expirationTime = $issuedAt + 3600; // Token valid for 1 hour
    $payload = [
        "iat" => $issuedAt,
        "exp" => $expirationTime,
        "user_id" => $user->id,
        "email" => $email
    ];

    $jwt = JWT::encode($payload, $jwtSecret, 'HS256');

    // ✅ Return Response
    echo json_encode([
        "status" => "success",
        "message" => "Login successful",
        "token" => $jwt
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    logEvent('errors', "❌ Database Query Error: " . $e->getMessage());
    echo json_encode(["error" => "Internal Server Error"]);
    exit;
}

// ✅ Function to Log Events Based on Category
function logEvent($category, $message) {
    $logFilePath = __DIR__ . "/../../../logs/{$category}.log";
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFilePath, "[$timestamp] $message\n", FILE_APPEND);
}
