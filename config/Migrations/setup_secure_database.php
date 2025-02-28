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
    // Consent logs remain unchanged
    "consent_logs" => "
        CREATE TABLE IF NOT EXISTS consent_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_reference VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            consent_given TINYINT(1) DEFAULT 0,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // Updated unified audit_logs table for all logging needs
    "audit_logs" => "
        CREATE TABLE IF NOT EXISTS audit_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(255) NOT NULL,       -- e.g., 'booking_created', 'payment_processed', 'user_login'
            message VARCHAR(255) NULL,          -- human-readable message describing event
            details TEXT NOT NULL,              -- stores JSON data with event specifics
            user_reference BIGINT UNSIGNED NULL,-- user ID if applicable
            booking_reference BIGINT UNSIGNED NULL,-- booking ID if applicable 
            transaction_reference BIGINT UNSIGNED NULL,-- transaction ID if applicable
            ip_address VARCHAR(45),             -- client IP
            category VARCHAR(50) NOT NULL,      -- e.g., 'system', 'booking', 'payment', 'user', 'admin'
            severity VARCHAR(20) DEFAULT 'info',-- e.g., 'info', 'warning', 'error', 'critical'
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // Contracts remain unchanged
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

// ✅ Migration script for older logs (if applicable)
try {
    // Check if legacy tables exist and migrate their data
    $hasLegacyTables = Capsule::connection('default')->select("SHOW TABLES LIKE 'transaction_logs'");
    if (!empty($hasLegacyTables)) {
        // Migrate transaction logs
        Capsule::connection('secure')->statement(
            "INSERT INTO audit_logs (action, message, details, user_reference, transaction_reference, category, created_at)
             SELECT 'transaction', 'Legacy transaction log', 
                    JSON_OBJECT('amount', amount, 'status', status, 'type', type),
                    user_id, id, 'payment', created_at
             FROM default.transaction_logs"
        );
        file_put_contents($logFilePath, "[✅] Legacy transaction logs migrated to audit_logs.\n", FILE_APPEND);
    }
    
    // Check for booking_logs legacy table
    $hasBookingLogs = Capsule::connection('default')->select("SHOW TABLES LIKE 'booking_logs'");
    if (!empty($hasBookingLogs)) {
        // Migrate booking logs
        Capsule::connection('secure')->statement(
            "INSERT INTO audit_logs (action, message, details, user_reference, booking_reference, category, created_at)
             SELECT 'booking_update', note, 
                    JSON_OBJECT('status', status, 'type', log_type),
                    user_id, booking_id, 'booking', created_at
             FROM default.booking_logs"
        );
        file_put_contents($logFilePath, "[✅] Legacy booking logs migrated to audit_logs.\n", FILE_APPEND);
    }
    
} catch (Exception $e) {
    file_put_contents($logFilePath, "[❌] Error migrating legacy logs: " . $e->getMessage() . "\n", FILE_APPEND);
}

file_put_contents($logFilePath, "✅ Secure Database Setup Completed Successfully.\n", FILE_APPEND);
echo "[🚀] Secure database setup completed. Check `logs/secure_db_setup.log` for details.\n";
