<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../App/Helpers/SecurityHelper.php';
require_once __DIR__ . '/../../../App/Helpers/DatabaseHelper.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Helpers\DatabaseHelper;

// ✅ Set Headers
header('Content-Type: application/json');

// ✅ Check for POST Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logEvent('security', "Method Not Allowed: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// ✅ Retrieve JSON Input
$data = json_decode(file_get_contents("php://input"), true);

logEvent('auth', "Received registration request.");

// ✅ Validate Input
if (empty($data['email']) || empty($data['password']) || empty($data['name'])) {
    logEvent('auth', "Registration failed: Missing fields.");
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

$email = trim($data['email']);
$password = trim($data['password']);
$name = trim($data['name']);

try {
    // ✅ Initialize Eloquent ORM
    DatabaseHelper::getInstance();
    logEvent('database', "Database connected in register API.");

    // ✅ Check If Email Already Exists
    $existingUser = Capsule::table('users')->where('email', $email)->first();
    if ($existingUser) {
        logEvent('security', "Registration failed: Email already registered ($email)");
        http_response_code(409);
        echo json_encode(["error" => "Email already registered"]);
        exit;
    }

    // ✅ Hash Password Before Storing
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // ✅ Insert New User
    $userId = Capsule::table('users')->insertGetId([
        'name' => $name,
        'email' => $email,
        'password' => $hashedPassword,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    logEvent('auth', "New user registered successfully. ID: $userId");

    // ✅ Return Success Response
    echo json_encode([
        "status" => "success",
        "message" => "Registration successful"
    ]);
    exit;

} catch (Exception $e) {
    logEvent('errors', "Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Internal Server Error"]);
    exit;
}

// ✅ Function to Log Events Based on Category
function logEvent($category, $message) {
    $logFilePath = __DIR__ . "/../../../logs/{$category}.log";
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFilePath, "[$timestamp] $message\n", FILE_APPEND);
}
