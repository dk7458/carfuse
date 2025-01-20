<?php
require __DIR__ . '/../includes/db_connect.php';

function createTable($conn, $tableName, $createQuery) {
    $checkTable = $conn->query("SHOW TABLES LIKE '$tableName'");
    if ($checkTable->num_rows === 0) {
        if ($conn->query($createQuery)) {
            echo "Table '$tableName' created successfully.<br>";
        } else {
            echo "Error creating table '$tableName': " . $conn->error . "<br>";
        }
    } else {
        echo "Table '$tableName' already exists.<br>";
    }
}

function checkAndAddColumn($conn, $tableName, $columnName, $columnDefinition) {
    $result = $conn->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    if ($result->num_rows === 0) {
        if ($conn->query("ALTER TABLE `$tableName` ADD `$columnName` $columnDefinition")) {
            echo "Column '$columnName' added to table '$tableName'.<br>";
        } else {
            echo "Error adding column '$columnName' to table '$tableName': " . $conn->error . "<br>";
        }
    } else {
        echo "Column '$columnName' already exists in table '$tableName'.<br>";
    }
}

// Table Schemas
$schemas = [
    'users' => [
        'create' => "
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                surname VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                phone VARCHAR(15),
                address TEXT,
                pesel_or_id VARCHAR(20),
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('user', 'admin') DEFAULT 'user',
                email_notifications BOOLEAN DEFAULT 0,
                sms_notifications BOOLEAN DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ",
        'columns' => [
            'name' => 'VARCHAR(255) NOT NULL',
            'surname' => 'VARCHAR(255) NOT NULL',
            'email' => 'VARCHAR(255) UNIQUE NOT NULL',
            'phone' => 'VARCHAR(15)',
            'address' => 'TEXT',
            'pesel_or_id' => 'VARCHAR(20)',
            'email_notifications' => 'BOOLEAN DEFAULT 0',
            'sms_notifications' => 'BOOLEAN DEFAULT 0',
        ],
    ],
    'fleet' => [
        'create' => "
            CREATE TABLE fleet (
                id INT AUTO_INCREMENT PRIMARY KEY,
                make VARCHAR(255) NOT NULL,
                model VARCHAR(255) NOT NULL,
                registration_number VARCHAR(20) UNIQUE NOT NULL,
                availability BOOLEAN DEFAULT 1,
                last_maintenance_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ",
        'columns' => [
            'make' => 'VARCHAR(255) NOT NULL',
            'model' => 'VARCHAR(255) NOT NULL',
            'registration_number' => 'VARCHAR(20) UNIQUE NOT NULL',
            'availability' => 'BOOLEAN DEFAULT 1',
            'last_maintenance_date' => 'DATE',
        ],
    ],
    'bookings' => [
        'create' => "
            CREATE TABLE bookings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                vehicle_id INT NOT NULL,
                pickup_date DATE NOT NULL,
                dropoff_date DATE NOT NULL,
                total_price DECIMAL(10, 2) NOT NULL,
                status ENUM('active', 'canceled') DEFAULT 'active',
                rental_contract_pdf VARCHAR(255),
                canceled_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (vehicle_id) REFERENCES fleet(id) ON DELETE CASCADE
            )
        ",
        'columns' => [
            'pickup_date' => 'DATE NOT NULL',
            'dropoff_date' => 'DATE NOT NULL',
            'total_price' => 'DECIMAL(10, 2) NOT NULL',
            'status' => "ENUM('active', 'canceled') DEFAULT 'active'",
        ],
    ],
    'payment_methods' => [
        'create' => "
            CREATE TABLE payment_methods (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                method_name VARCHAR(255) NOT NULL,
                details VARCHAR(255) NOT NULL,
                is_default BOOLEAN DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ",
        'columns' => [
            'method_name' => 'VARCHAR(255) NOT NULL',
            'details' => 'VARCHAR(255) NOT NULL',
            'is_default' => 'BOOLEAN DEFAULT 0',
        ],
    ],
    'notifications' => [
        'create' => "
            CREATE TABLE notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type ENUM('email', 'sms') NOT NULL,
                message TEXT NOT NULL,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ",
        'columns' => [
            'type' => "ENUM('email', 'sms') NOT NULL",
            'message' => 'TEXT NOT NULL',
        ],
    ],
    'logs' => [
        'create' => "
            CREATE TABLE logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(255) NOT NULL,
                details TEXT,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ",
        'columns' => [
            'action' => 'VARCHAR(255) NOT NULL',
            'details' => 'TEXT',
        ],
    ],
    'admin_notification_settings' => [
        'create' => "
            CREATE TABLE admin_notification_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                contract_alerts BOOLEAN DEFAULT 0,
                maintenance_alerts BOOLEAN DEFAULT 0,
                booking_reminders BOOLEAN DEFAULT 0,
                FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ",
        'columns' => [
            'contract_alerts' => 'BOOLEAN DEFAULT 0',
            'maintenance_alerts' => 'BOOLEAN DEFAULT 0',
            'booking_reminders' => 'BOOLEAN DEFAULT 0',
        ],
    ],
];

// Process each table schema
foreach ($schemas as $tableName => $schema) {
    createTable($conn, $tableName, $schema['create']);
    foreach ($schema['columns'] as $columnName => $columnDefinition) {
        checkAndAddColumn($conn, $tableName, $columnName, $columnDefinition);
    }
}

echo "Database schema verification and update completed.<br>";
?>

