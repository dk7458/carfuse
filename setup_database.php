<?php
/**
 * File: setup_database.php
 * Purpose: Initializes and modifies the database for the Carfuse system.
 * Usage: Run this file once to create and modify necessary tables in the database.
 */

require_once 'config/database.php'; // Ensure database connection is loaded

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "Connected to the database successfully.\n";

    // Queries to create or modify tables
    $queries = [
        "CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            report_type ENUM('bookings', 'payments', 'users') NOT NULL,
            filters TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );",

        // Create notifications table
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('email', 'sms', 'webhook', 'push') NOT NULL,
            message TEXT NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_read TINYINT(1) DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );",

        // Create admin_notification_settings table
        "CREATE TABLE IF NOT EXISTS admin_notification_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            contract_alerts TINYINT(1) DEFAULT 0,
            maintenance_alerts TINYINT(1) DEFAULT 0,
            booking_reminders TINYINT(1) DEFAULT 0,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
        );",

        // Create availability table
        "CREATE TABLE IF NOT EXISTS availability (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vehicle_id INT NOT NULL,
            date DATE NOT NULL,
            status ENUM('available', 'unavailable') DEFAULT 'available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (vehicle_id, date),
            FOREIGN KEY (vehicle_id) REFERENCES fleet(id) ON DELETE CASCADE
        );",

        // Add refund_status column to bookings table
        "ALTER TABLE bookings
            ADD COLUMN IF NOT EXISTS refund_status ENUM('none', 'requested', 'processed') DEFAULT 'none';",

        // Add payment_status column to bookings table
        "ALTER TABLE bookings
            ADD COLUMN IF NOT EXISTS payment_status ENUM('paid', 'pending', 'refunded') DEFAULT 'pending';",

        // Create booking_logs table
        "CREATE TABLE IF NOT EXISTS booking_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
        );",

        // Create transaction_logs table
        "CREATE TABLE IF NOT EXISTS transaction_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            booking_id INT NULL,
            amount DECIMAL(10,2) NOT NULL,
            type ENUM('payment', 'refund') NOT NULL,
            status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
        );",

        // Add reason column to refund_logs table
        "ALTER TABLE refund_logs
            ADD COLUMN IF NOT EXISTS reason VARCHAR(255) DEFAULT NULL;",

        // Add push_notifications and update other notification-related columns in users table
        "ALTER TABLE users
            ADD COLUMN IF NOT EXISTS push_notifications TINYINT(1) DEFAULT 0,
            MODIFY COLUMN email_notifications TINYINT(1) DEFAULT 0,
            MODIFY COLUMN sms_notifications TINYINT(1) DEFAULT 0,
            MODIFY COLUMN active TINYINT(1) DEFAULT 1;"
    ];

    // Execute all queries
    foreach ($queries as $query) {
        $pdo->exec($query);
        echo "Executed query: $query\n";
    }

    echo "Database setup completed successfully.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
