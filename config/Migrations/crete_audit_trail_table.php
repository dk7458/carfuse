<?php
/**
 * File: create_audit_trail_table.php
 * Purpose: Creates the `audit_trails` table for recording audit trail logs.
 */

require_once __DIR__ . '/../../bootstrap.php'; // Ensure this points to the correct path for database initialization.

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "Connected to the database successfully.\n";

    $query = "
        CREATE TABLE IF NOT EXISTS audit_trails (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(255) NOT NULL,
            details TEXT NOT NULL,
            user_id INT NULL,
            booking_id INT NULL,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ";

    $pdo->exec($query);
    echo "Table `audit_trails` created successfully.\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}
