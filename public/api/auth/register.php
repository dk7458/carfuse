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
if (!isset($data['name'], $data['email'], $data['password'])) {
    http_response_code(400);
    logEvent('auth', "Registration failed: Missing required fields.");
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

$name = trim($data['name']);
$surname = trim($data['surname'] ?? ''); // Optional
$email = trim($data['email']);
$password = trim($data['password']);
$phone = trim($data['phone'] ?? null);
$address = trim($data['address'] ?? null);

try {
    // ✅ Initialize Eloquent ORM
    DatabaseHelper::getInstance();

    // ✅ Check if Email Already Exists
    $existingUser = Capsule::table('users')->where('email', $email)->first();
    if ($existingUser) {
        http_response_code(409);
        logEvent('auth', "Registration failed: Email already exists - $email");
        echo json_encode(["error" => "Email already in use"]);
        exit;
    }

    // ✅ Hash Password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // ✅ Insert User into Database
    $userId = Capsule::table('users')->insertGetId([
        'name' => $name,
        'surname' => $surname,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'password_hash' => $hashedPassword, // ✅ Fix: Use `password_hash`
        'created_at' => date('Y-m-d H:i:s')
    ]);

    // ✅ Generate JWT Token
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'default_secret_key';
    $issuedAt = time();
    $expirationTime = $issuedAt + 3600; // Token valid for 1 hour
    $payload = [
        "iat" => $issuedAt,
        "exp" => $expirationTime,
        "user_id" => $userId,
        "email" => $email
    ];

    $jwt = JWT::encode($payload, $jwtSecret, 'HS256');

    // ✅ Log Successful Registration
    logEvent('auth', "User ID {$userId} registered successfully.");

    // ✅ Return Response
    echo json_encode([
        "status" => "success",
        "message" => "User registered successfully",
        "token" => $jwt
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    logEvent('errors', "Database error: " . $e->getMessage());
    echo json_encode(["error" => "Internal Server Error"]);
    exit;
}

// ✅ Function to Log Events Based on Category
function logEvent($category, $message) {
    $logFilePath = __DIR__ . "/../../../logs/{$category}.log";
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFilePath, "[$timestamp] $message\n", FILE_APPEND);
}
