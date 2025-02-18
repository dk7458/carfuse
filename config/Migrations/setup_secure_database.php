<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/DatabaseHelper.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Helpers\DatabaseHelper;

// ✅ Initialize Secure Database
DatabaseHelper::getSecureInstance();

// ✅ Log Setup
$logFilePath = __DIR__ . '/../logs/secure_db_setup.log';
file_put_contents($logFilePath, "🚀 Secure Database Setup Started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// ✅ Define Secure Tables (No Cross-Database Foreign Keys)
$tables = [
    "consent_logs" => "
        CREATE TABLE IF NOT EXISTS consent_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_reference VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            consent_given TINYINT(1) DEFAULT 0,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    "logs" => "
        CREATE TABLE IF NOT EXISTS logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_reference BIGINT UNSIGNED NOT NULL,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    "audit_trails" => "
        CREATE TABLE IF NOT EXISTS audit_trails (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(255) NOT NULL,
            details TEXT NOT NULL,
            user_reference BIGINT UNSIGNED NULL,
            booking_reference BIGINT UNSIGNED NULL,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    "contracts" => "
        CREATE TABLE IF NOT EXISTS contracts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            booking_reference BIGINT UNSIGNED NOT NULL,
            user_reference BIGINT UNSIGNED NOT NULL,
            contract_pdf VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    "
];

// ✅ Execute Table Creation with Error Handling
foreach ($tables as $tableName => $sql) {
    try {
        Capsule::connection('secure')->statement($sql);
        file_put_contents($logFilePath, "[✅] Secure Table `{$tableName}` created successfully.\n", FILE_APPEND);
    } catch (Exception $e) {
        file_put_contents($logFilePath, "[❌] Error creating `{$tableName}`: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

file_put_contents($logFilePath, "✅ Secure Database Setup Completed Successfully.\n", FILE_APPEND);
echo "[🚀] Secure database setup completed. Check `logs/secure_db_setup.log` for details.\n";
