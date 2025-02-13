<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/DatabaseHelper.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use App\Helpers\DatabaseHelper;

// ✅ Initialize Application Database
DatabaseHelper::getInstance();

// ✅ Define Tables
$tables = [
    "users" => "
        CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            surname VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            phone VARCHAR(15),
            address TEXT,
            pesel_or_id VARCHAR(20),
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('user', 'admin') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            email_notifications TINYINT(1) DEFAULT 0,
            sms_notifications TINYINT(1) DEFAULT 0,
            active TINYINT(1) DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    "bookings" => "
        CREATE TABLE IF NOT EXISTS bookings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_reference BIGINT UNSIGNED NOT NULL,
            vehicle_id BIGINT UNSIGNED NOT NULL,
            pickup_date DATE NOT NULL,
            dropoff_date DATE NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            status ENUM('active', 'canceled') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_reference) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (vehicle_id) REFERENCES fleet(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    "notifications" => "
        CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_reference BIGINT UNSIGNED NOT NULL,
            type ENUM('email','sms') NOT NULL,
            message TEXT NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_read TINYINT(1) DEFAULT 0,
            FOREIGN KEY (user_reference) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    "admin_notification_settings" => "
        CREATE TABLE IF NOT EXISTS admin_notification_settings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            admin_reference BIGINT UNSIGNED NOT NULL,
            contract_alerts TINYINT(1) DEFAULT 0,
            maintenance_alerts TINYINT(1) DEFAULT 0,
            booking_reminders TINYINT(1) DEFAULT 0,
            FOREIGN KEY (admin_reference) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    "maintenance_logs" => "
        CREATE TABLE IF NOT EXISTS maintenance_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            vehicle_id BIGINT UNSIGNED NOT NULL,
            description TEXT NOT NULL,
            maintenance_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vehicle_id) REFERENCES fleet(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

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
    "
];

// ✅ Execute Table Creation with Error Handling
$logFilePath = __DIR__ . '/../logs/database_setup.log';
file_put_contents($logFilePath, "🚀 Database Setup Started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

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
