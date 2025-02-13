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
    http_response_code(405);
    logEvent('security', "Method Not Allowed: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// ✅ Retrieve JSON Input
$data = json_decode(file_get_contents("php://input"), true);

// ✅ Validate Input
if (empty($data['email'])) {
    http_response_code(400);
    logEvent('auth', "Password reset request failed: Missing email.");
    echo json_encode(["error" => "Missing email"]);
    exit;
}

$email = trim($data['email']);

try {
    // ✅ Initialize Eloquent ORM
    DatabaseHelper::getInstance();

    // ✅ Check If User Exists
    $user = Capsule::table('users')->where('email', $email)->first();

    if (!$user) {
        logEvent('security', "Password reset requested for non-existent email: $email");
        echo json_encode(["status" => "success", "message" => "If the email exists, a reset link has been sent."]);
        exit;
    }

    // ✅ Generate Secure Token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // 1-hour expiration
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    // ✅ Store Reset Token
    Capsule::table('password_resets')->insert([
        'email' => $email,
        'token' => password_hash($token, PASSWORD_BCRYPT),
        'ip_address' => $ipAddress,
        'expires_at' => $expiresAt,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    // ✅ Send Email (Mock Implementation)
    logEvent('auth', "Password reset token generated for $email: $token");

    // ✅ Return Response
    echo json_encode([
        "status" => "success",
        "message" => "If the email exists, a reset link has been sent."
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
