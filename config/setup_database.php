<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/DatabaseHelper.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Helpers\DatabaseHelper;

// Initialize the main database connection
DatabaseHelper::getInstance();

// Define all tables with a consistent schema
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

// Log file for setup
$logFilePath = __DIR__ . '/../logs/database_setup.log';
file_put_contents($logFilePath, "🚀 Database Setup Started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Create each table in order
foreach ($tables as $tableName => $sql) {
    try {
        Capsule::statement($sql);
        file_put_contents($logFilePath, "[✅] Table `{$tableName}` created successfully.\n", FILE_APPEND);
    } catch (Exception $e) {
        file_put_contents($logFilePath, "[❌] Error creating `{$tableName}`: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

file_put_contents($logFilePath, "✅ Database setup completed successfully.\n", FILE_APPEND);
echo "[🚀] Application database setup completed. Check `logs/database_setup.log` for details.\n";
