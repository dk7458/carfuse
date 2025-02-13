<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../App/Helpers/DatabaseHelper.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Helpers\DatabaseHelper;

// ✅ Initialize Secure Database
DatabaseHelper::getSecureInstance();

// ✅ Remove Consent Log
$userReference = $_SERVER['REMOTE_ADDR'];
Capsule::connection('secure')->table('consent_logs')
    ->where('user_reference', $userReference)
    ->delete();

echo json_encode(["status" => "success", "message" => "Consent revoked"]);
