<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/App/Helpers/DatabaseHelper.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Helpers\DatabaseHelper;

// ✅ Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Set headers for JSON response
header('Content-Type: application/json');

try {
    // ✅ Initialize Secure Database Connection
    DatabaseHelper::getSecureInstance();
    
    // ✅ Test Query on Secure Database (Replace `transactions` with an actual table)
    $transactions = Capsule::connection('secure')->table('transactions')->limit(5)->get();

    echo json_encode([
        "status" => "success",
        "message" => "Secure database connected successfully!",
        "data" => $transactions
    ]);
} catch (Exception $e) {
    // ✅ Log & Return Connection Error
    error_log("[SECURE DB ERROR] " . $e->getMessage(), 3, __DIR__ . "/../logs/errors.log");
    echo json_encode([
        "status" => "error",
        "message" => "Secure database connection failed",
        "error" => $e->getMessage()
    ]);
}
