<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../App/Helpers/DatabaseHelper.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Helpers\DatabaseHelper;

// ✅ Initialize Secure Database
DatabaseHelper::getSecureInstance();

// ✅ Store Consent with Timestamp
$userReference = $_SERVER['REMOTE_ADDR']; // Track per IP
$timestamp = date('Y-m-d H:i:s');

Capsule::connection('secure')->table('consent_logs')->insert([
    'user_reference' => $userReference,
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'consent_given' => 1,
    'timestamp' => $timestamp
]);

echo json_encode(["status" => "success", "message" => "Consent accepted"]);
