<?php
// setup_database.php
// Description: Initializes the main application database using PDO via DatabaseHelper,
// creates required tables, and performs any necessary migrations.
// Dependencies: Requires autoload.php, DatabaseHelper, and proper configuration.

// Enable full error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/DatabaseHelper.php';

use App\Helpers\DatabaseHelper;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Set up log file path 
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFilePath = $logDir . '/database_setup.log';

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
logMessage("🚀 Main Database Setup Started");

// Step 1: Initialize Database using DatabaseHelper
try {
    logMessage("Initializing main database instance...");
    $dbHelper = DatabaseHelper::getInstance();
    $pdo = $dbHelper->getPdo();
    logMessage("✅ Main database connection established.");
} catch (Exception $e) {
    logMessage("❌ Failed to initialize main database: " . $e->getMessage());
    echo "[❌] Main database initialization failed. Check log for details.\n";
    exit(1);
}

// Step 2: Define Main Application Tables
logMessage("Defining application tables...");
$tables = [
    // FLEET TABLE
    "fleet" => "
        CREATE TABLE IF NOT EXISTS fleet (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            make VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            model VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            registration_number VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
            availability TINYINT(1) DEFAULT 1,
            last_maintenance_date DATE DEFAULT NULL,
            next_maintenance_date DATE DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // USERS TABLE (MERGED COLUMNS)
    "users" => "
        CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            surname VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            email VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
            phone VARCHAR(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            address TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            pesel_or_id VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            password_hash VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            role ENUM('user', 'admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            email_notifications TINYINT(1) DEFAULT 0,
            sms_notifications TINYINT(1) DEFAULT 0,
            active TINYINT(1) DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // BOOKINGS TABLE (MERGED COLUMNS)
    "bookings" => "
        CREATE TABLE IF NOT EXISTS bookings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            vehicle_id BIGINT UNSIGNED NOT NULL,
            pickup_date DATE NOT NULL,
            dropoff_date DATE NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            status ENUM('active', 'canceled') DEFAULT 'active',
            canceled_at TIMESTAMP NULL DEFAULT NULL,
            refund_status ENUM('none', 'requested', 'processed') DEFAULT 'none',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (vehicle_id) REFERENCES fleet(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // NOTIFICATIONS TABLE
    "notifications" => "
        CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_reference BIGINT UNSIGNED NOT NULL,
            type ENUM('email','sms') NOT NULL,
            message TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_read TINYINT(1) DEFAULT 0,
            FOREIGN KEY (user_reference) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // ADMIN NOTIFICATION SETTINGS
    "admin_notification_settings" => "
        CREATE TABLE IF NOT EXISTS admin_notification_settings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            admin_id BIGINT UNSIGNED NOT NULL,
            contract_alerts TINYINT(1) DEFAULT 0,
            maintenance_alerts TINYINT(1) DEFAULT 0,
            booking_reminders TINYINT(1) DEFAULT 0,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // MAINTENANCE LOGS
    "maintenance_logs" => "
        CREATE TABLE IF NOT EXISTS maintenance_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            vehicle_id BIGINT UNSIGNED NOT NULL,
            description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            maintenance_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vehicle_id) REFERENCES fleet(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // AVAILABILITY TABLE
    "availability" => "
        CREATE TABLE IF NOT EXISTS availability (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            vehicle_id BIGINT UNSIGNED NOT NULL,
            date DATE NOT NULL,
            status ENUM('available','unavailable') DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (vehicle_id, date),
            FOREIGN KEY (vehicle_id) REFERENCES fleet(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // PASSWORD RESETS
    "password_resets" => "
        CREATE TABLE IF NOT EXISTS password_resets (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            token VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            ip_address VARCHAR(45),
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_token (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // REFUND LOGS (From second version)
    "refund_logs" => "
        CREATE TABLE IF NOT EXISTS refund_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            booking_id BIGINT UNSIGNED NOT NULL,
            refunded_amount DECIMAL(10,2) NOT NULL,
            refund_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // PAYMENT METHODS TABLE
    "payment_methods" => "
        CREATE TABLE IF NOT EXISTS payment_methods (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            method_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            details VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            is_default TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // Access tokens table
    "access_tokens" => "
        CREATE TABLE IF NOT EXISTS access_tokens (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            token VARCHAR(255) UNIQUE NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    "
];

// Step 3: Execute Table Creation
logMessage("Creating application tables...");
$allTablesCreated = true;
foreach ($tables as $tableName => $sql) {
    logMessage("Attempting to create table: {$tableName}");
    try {
        $pdo->exec($sql);
        logMessage("✅ Table `{$tableName}` created successfully.");
    } catch (PDOException $e) {
        logMessage("❌ Error creating `{$tableName}`: " . $e->getMessage());
        $allTablesCreated = false;
    }
}

// Step 4: Check for data seeding needs
logMessage("Checking if data seeding is needed...");
try {
    // Check if admin user exists using PDO
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminCount = (int)$stmt->fetchColumn();
    
    if ($adminCount === 0) {
        logMessage("No admin user found. Creating default admin account...");
        try {
            $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT); // Change in production!
            $stmt = $pdo->prepare("
                INSERT INTO users (name, surname, email, password_hash, role, created_at, updated_at, active) 
                VALUES (:name, :surname, :email, :password_hash, :role, :created_at, :updated_at, :active)
            ");
            $stmt->execute([
                ':name' => 'Admin',
                ':surname' => 'User',
                ':email' => 'admin@carfuse.com',
                ':password_hash' => $hashedPassword,
                ':role' => 'admin',
                ':created_at' => date('Y-m-d H:i:s'),
                ':updated_at' => date('Y-m-d H:i:s'),
                ':active' => 1
            ]);
            logMessage("✅ Default admin user created successfully.");
        } catch (PDOException $e) {
            logMessage("❌ Error creating admin user: " . $e->getMessage());
        }
    } else {
        logMessage("ℹ️ Admin user already exists. Skipping creation.");
    }
} catch (PDOException $e) {
    logMessage("❌ Error checking for admin user: " . $e->getMessage());
}

// Step 5: Finalize Setup
if ($allTablesCreated) {
    logMessage("✅ Main Database Setup Completed Successfully at " . date('Y-m-d H:i:s'));
    echo "[🚀] Application database setup completed successfully. Check '{$logFilePath}' for details.\n";
    exit(0);
} else {
    logMessage("⚠️ Main Database Setup Completed with Warnings at " . date('Y-m-d H:i:s'));
    echo "[⚠️] Application database setup completed with some issues. Check '{$logFilePath}' for details.\n";
    exit(1);
}
