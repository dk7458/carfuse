<?php
// setup_secure_database.php
// Description: Initializes the secure database using the new PDO-based DatabaseHelper,
// creates required secure tables, and migrates legacy logs if applicable.
// Changelog: 2025-03-01 - Added forced error reporting to reveal hidden errors.
// Dependencies: Requires autoload.php, DatabaseHelper, and proper configuration.

// Enable full error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/DatabaseHelper.php';

use App\Helpers\DatabaseHelper;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Set up log file path in the current directory
$logFilePath = __DIR__ . '/setup_secure_db.log';

// Initialize the logger early for DatabaseHelper
try {
    $logger = new Logger('db_setup');
    $logger->pushHandler(new StreamHandler($logFilePath, Logger::DEBUG));
    // Set the static logger on DatabaseHelper
    DatabaseHelper::setLogger($logger);
    $logger->info("🚀 Logger initialized successfully for DatabaseHelper");
} catch (Exception $e) {
    file_put_contents($logFilePath, "[" . date('Y-m-d H:i:s') . "] ❌ Failed to initialize logger: " . $e->getMessage() . "\n", FILE_APPEND);
    echo "[❌] Failed to initialize logger. Check log for details.\n";
    exit(1);
}

// Helper function to log messages with timestamps
function logMessage($message) {
    global $logFilePath, $logger;
    if (isset($logger)) {
        $logger->info($message);
    } else {
        file_put_contents($logFilePath, "[" . date('Y-m-d H:i:s') . "] " . $message . "\n", FILE_APPEND);
    }
    // Also output to the console for immediate feedback
    echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
}

// Log the start of the setup
logMessage("🚀 Secure Database Setup Started");

// Step 1: Initialize Secure Database using DatabaseHelper
try {
    logMessage("Initializing secure database instance...");
    $secureDbHelper = DatabaseHelper::getSecureInstance();
    $pdoSecure = $secureDbHelper->getPdo();
    logMessage("✅ Secure database connection established.");
} catch (Exception $e) {
    logMessage("❌ Failed to initialize secure database: " . $e->getMessage());
    echo "[❌] Secure database initialization failed. Check log for details.\n";
    exit(1);
}

// Step 2: Define Secure Tables
logMessage("Defining secure tables...");
$tables = [
    // Consent logs table
    "consent_logs" => "
        CREATE TABLE IF NOT EXISTS consent_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_reference VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            consent_given TINYINT(1) DEFAULT 0,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // Unified audit_logs table for all logging needs
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

    // Contracts table
    "contracts" => "
        CREATE TABLE IF NOT EXISTS contracts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            booking_reference BIGINT UNSIGNED NOT NULL,
            user_reference BIGINT UNSIGNED NOT NULL,
            contract_pdf VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // Refresh tokens table - remove foreign key constraint that references users table
    "refresh_tokens" => "
        CREATE TABLE IF NOT EXISTS refresh_tokens (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            token VARCHAR(255) UNIQUE NOT NULL,
            ip_address VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            revoked TINYINT(1) DEFAULT 0,
            revoked_at TIMESTAMP NULL DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    "
];

// Step 3: Execute Table Creation
logMessage("Creating secure tables...");
$allTablesCreated = true;
foreach ($tables as $tableName => $sql) {
    logMessage("Attempting to create table: {$tableName}");
    try {
        $pdoSecure->exec($sql);
        logMessage("✅ Secure Table `{$tableName}` created successfully.");
    } catch (PDOException $e) {
        logMessage("❌ Error creating `{$tableName}`: " . $e->getMessage());
        $allTablesCreated = false;
    }
}

if (!$allTablesCreated) {
    logMessage("⚠️ Some tables failed to create. Check errors above.");
}

// Step 4: Legacy Migration Section
logMessage("Starting legacy migration for legacy logs...");

// Initialize default DB instance for legacy migrations
try {
    logMessage("Initializing default database instance for legacy migration...");
    $defaultDbHelper = DatabaseHelper::getInstance();
    $pdoDefault = $defaultDbHelper->getPdo();
    logMessage("✅ Default database connection established.");
} catch (Exception $e) {
    logMessage("❌ Failed to initialize default database for migration: " . $e->getMessage());
    logMessage("⚠️ Legacy migration will be skipped due to database connection error.");
    $pdoDefault = null;
}

if ($pdoDefault !== null) {
    // Migrate legacy transaction_logs if available
    try {
        logMessage("Checking for legacy 'transaction_logs' table...");
        $stmt = $pdoDefault->query("SHOW TABLES LIKE 'transaction_logs'");
        $hasLegacyTables = $stmt->fetchAll();
        if (!empty($hasLegacyTables)) {
            logMessage("Legacy 'transaction_logs' found. Starting migration...");
            $migrationSql = "
                INSERT INTO audit_logs (action, message, details, user_reference, transaction_reference, category, created_at)
                SELECT 'transaction', 'Legacy transaction log', 
                       JSON_OBJECT('amount', amount, 'status', status, 'type', type),
                       user_id, id, 'payment', created_at
                FROM transaction_logs
            ";
            try {
                $pdoSecure->exec($migrationSql);
                logMessage("✅ Legacy transaction logs migrated to audit_logs.");
            } catch (PDOException $e) {
                logMessage("❌ Error migrating legacy transaction logs: " . $e->getMessage());
            }
        } else {
            logMessage("ℹ️ No legacy 'transaction_logs' table found; skipping migration.");
        }
    } catch (PDOException $e) {
        logMessage("❌ Error checking for legacy 'transaction_logs': " . $e->getMessage());
    }

    // Migrate legacy booking_logs if available
    try {
        logMessage("Checking for legacy 'booking_logs' table...");
        $stmt = $pdoDefault->query("SHOW TABLES LIKE 'booking_logs'");
        $hasBookingLogs = $stmt->fetchAll();
        if (!empty($hasBookingLogs)) {
            logMessage("Legacy 'booking_logs' found. Starting migration...");
            $migrationSql = "
                INSERT INTO audit_logs (action, message, details, user_reference, booking_reference, category, created_at)
                SELECT 'booking_update', note, 
                       JSON_OBJECT('status', status, 'type', log_type),
                       user_id, booking_id, 'booking', created_at
                FROM booking_logs
            ";
            try {
                $pdoSecure->exec($migrationSql);
                logMessage("✅ Legacy booking logs migrated to audit_logs.");
            } catch (PDOException $e) {
                logMessage("❌ Error migrating legacy booking logs: " . $e->getMessage());
            }
        } else {
            logMessage("ℹ️ No legacy 'booking_logs' table found; skipping migration.");
        }
    } catch (PDOException $e) {
        logMessage("❌ Error checking for legacy 'booking_logs': " . $e->getMessage());
    }
} else {
    logMessage("⚠️ Legacy migration skipped due to database connection issues.");
}

// Step 5: Finalize Setup
if ($allTablesCreated) {
    logMessage("✅ Secure Database Setup Completed Successfully at " . date('Y-m-d H:i:s'));
    echo "[🚀] Secure database setup completed successfully. Check 'setup_secure_db.log' for details.\n";
    exit(0);
} else {
    logMessage("⚠️ Secure Database Setup Completed with Warnings at " . date('Y-m-d H:i:s'));
    echo "[⚠️] Secure database setup completed with some issues. Check 'setup_secure_db.log' for details.\n";
    exit(1);
}
