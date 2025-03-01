<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/DatabaseHelper.php';

use App\Helpers\DatabaseHelper;

// Initialize Secure Database using the new PDO-based DatabaseHelper
$secureDbHelper = DatabaseHelper::getSecureInstance();
$pdoSecure = $secureDbHelper->getPdo();

// Log Setup
$logFilePath = __DIR__ . '/../logs/secure_db_setup.log';
file_put_contents($logFilePath, "🚀 Secure Database Setup Started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Define Secure Tables (No Cross-Database Foreign Keys)
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
            action VARCHAR(255) NOT NULL,
            message VARCHAR(255) NULL,
            details TEXT NOT NULL,
            user_reference BIGINT UNSIGNED NULL,
            booking_reference BIGINT UNSIGNED NULL,
            transaction_reference BIGINT UNSIGNED NULL,
            ip_address VARCHAR(45),
            category VARCHAR(50) NOT NULL,
            severity VARCHAR(20) DEFAULT 'info',
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

// Execute Table Creation with Error Handling using PDO
foreach ($tables as $tableName => $sql) {
    try {
        $pdoSecure->exec($sql);
        file_put_contents($logFilePath, "[✅] Secure Table `{$tableName}` created successfully.\n", FILE_APPEND);
    } catch (Exception $e) {
        file_put_contents($logFilePath, "[❌] Error creating `{$tableName}`: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// Migration script for older logs (if applicable)
try {
    // Get default (app) PDO instance for legacy tables
    $defaultDbHelper = DatabaseHelper::getInstance();
    $pdoDefault = $defaultDbHelper->getPdo();
    
    // Migrate legacy transaction_logs if they exist
    $stmt = $pdoDefault->query("SHOW TABLES LIKE 'transaction_logs'");
    $hasLegacyTables = $stmt->fetchAll();
    if (!empty($hasLegacyTables)) {
        $migrationSql = "
            INSERT INTO audit_logs (action, message, details, user_reference, transaction_reference, category, created_at)
            SELECT 'transaction', 'Legacy transaction log', 
                   JSON_OBJECT('amount', amount, 'status', status, 'type', type),
                   user_id, id, 'payment', created_at
            FROM transaction_logs
        ";
        $pdoSecure->exec($migrationSql);
        file_put_contents($logFilePath, "[✅] Legacy transaction logs migrated to audit_logs.\n", FILE_APPEND);
    }
    
    // Migrate legacy booking_logs if they exist
    $stmt = $pdoDefault->query("SHOW TABLES LIKE 'booking_logs'");
    $hasBookingLogs = $stmt->fetchAll();
    if (!empty($hasBookingLogs)) {
        $migrationSql = "
            INSERT INTO audit_logs (action, message, details, user_reference, booking_reference, category, created_at)
            SELECT 'booking_update', note, 
                   JSON_OBJECT('status', status, 'type', log_type),
                   user_id, booking_id, 'booking', created_at
            FROM booking_logs
        ";
        $pdoSecure->exec($migrationSql);
        file_put_contents($logFilePath, "[✅] Legacy booking logs migrated to audit_logs.\n", FILE_APPEND);
    }
    
} catch (Exception $e) {
    file_put_contents($logFilePath, "[❌] Error migrating legacy logs: " . $e->getMessage() . "\n", FILE_APPEND);
}

file_put_contents($logFilePath, "✅ Secure Database Setup Completed Successfully.\n", FILE_APPEND);
echo "[🚀] Secure database setup completed. Check `logs/secure_db_setup.log` for details.\n";
