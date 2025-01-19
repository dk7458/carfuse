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

// Users Table
createTable($conn, 'users', "
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
");

checkAndAddColumn($conn, 'users', 'name', 'VARCHAR(255) NOT NULL');
checkAndAddColumn($conn, 'users', 'surname', 'VARCHAR(255) NOT NULL');
checkAndAddColumn($conn, 'users', 'email', 'VARCHAR(255) UNIQUE NOT NULL');
checkAndAddColumn($conn, 'users', 'phone', 'VARCHAR(15)');
checkAndAddColumn($conn, 'users', 'address', 'TEXT');
checkAndAddColumn($conn, 'users', 'pesel_or_id', 'VARCHAR(20)');
checkAndAddColumn($conn, 'users', 'email_notifications', 'BOOLEAN DEFAULT 0');
checkAndAddColumn($conn, 'users', 'sms_notifications', 'BOOLEAN DEFAULT 0');

// Fleet Table
createTable($conn, 'fleet', "
    CREATE TABLE fleet (
        id INT AUTO_INCREMENT PRIMARY KEY,
        make VARCHAR(255) NOT NULL,
        model VARCHAR(255) NOT NULL,
        registration_number VARCHAR(20) UNIQUE NOT NULL,
        availability BOOLEAN DEFAULT 1,
        last_maintenance_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Bookings Table
createTable($conn, 'bookings', "
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
");

// Notifications Table
createTable($conn, 'notifications', "
    CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('email', 'sms') NOT NULL,
        message TEXT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");

// Logs Table
createTable($conn, 'logs', "
    CREATE TABLE logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");

// Maintenance Logs Table
createTable($conn, 'maintenance_logs', "
    CREATE TABLE maintenance_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vehicle_id INT NOT NULL,
        description TEXT NOT NULL,
        maintenance_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (vehicle_id) REFERENCES fleet(id) ON DELETE CASCADE
    )
");

createTable($conn, 'password_resets', "
    CREATE TABLE password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (email) REFERENCES users(email) ON DELETE CASCADE
    )
");

echo "Database setup completed.<br>";
?>
