<?php
require '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';

function createTable($conn, $tableName, $createQuery) {
    $checkTable = $conn->query("SHOW TABLES LIKE '$tableName'");
    if ($checkTable === false) {
        echo "Error checking table '$tableName': " . $conn->error . "<br>";
        return;
    }

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
    if ($result === false) {
        echo "Error checking column '$columnName' in table '$tableName': " . $conn->error . "<br>";
        return;
    }

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

function insertDefaultRecord($conn, $tableName, $conditions, $insertQuery, $params, $types) {
    $query = "SELECT COUNT(*) FROM $tableName WHERE $conditions";
    $result = $conn->query($query);
    if ($result && $result->fetch_row()[0] === 0) {
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            echo "Default record inserted into '$tableName'.<br>";
        } else {
            echo "Error inserting record into '$tableName': " . $conn->error . "<br>";
        }
    } else {
        echo "Default record already exists in '$tableName'.<br>";
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
                role ENUM('user', 'admin', 'super_admin') DEFAULT 'user',
                email_notifications BOOLEAN DEFAULT 0,
                sms_notifications BOOLEAN DEFAULT 0,
                active BOOLEAN DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ",
        'columns' => [
            'role' => "ENUM('user', 'admin', 'super_admin') DEFAULT 'user'",
            'active' => 'BOOLEAN DEFAULT 1',
        ],
        'default_records' => [
            [
                'conditions' => "email = 'admin@example.com'",
                'query' => "INSERT INTO users (name, surname, email, password_hash, role, active) VALUES (?, ?, ?, ?, 'super_admin', 1)",
                'params' => ['Admin', 'User', 'admin@example.com', password_hash('admin123', PASSWORD_BCRYPT)],
                'types' => 'ssss',
            ]
        ]
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
                next_maintenance_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ",
        'columns' => [
            'next_maintenance_date' => 'DATE',
        ],
        'default_records' => []
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
                status ENUM('active', 'canceled', 'paid', 'completed') DEFAULT 'active',
                refund_status ENUM('none', 'requested', 'processed') DEFAULT 'none',
                rental_contract_pdf VARCHAR(255),
                canceled_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (vehicle_id) REFERENCES fleet(id) ON DELETE CASCADE
            )
        ",
        'columns' => [
            'refund_status' => "ENUM('none', 'requested', 'processed') DEFAULT 'none'",
        ],
        'default_records' => []
    ],
    // Add other tables with schemas and default records as needed...
];

// Wrap operations in a transaction
$conn->begin_transaction();

try {
    foreach ($schemas as $tableName => $schema) {
        createTable($conn, $tableName, $schema['create']);
        foreach ($schema['columns'] as $columnName => $columnDefinition) {
            checkAndAddColumn($conn, $tableName, $columnName, $columnDefinition);
        }
        if (!empty($schema['default_records'])) {
            foreach ($schema['default_records'] as $record) {
                insertDefaultRecord(
                    $conn,
                    $tableName,
                    $record['conditions'],
                    $record['query'],
                    $record['params'],
                    $record['types']
                );
            }
        }
    }

    $conn->commit();
    echo "Database setup completed successfully.<br>";
} catch (Exception $e) {
    $conn->rollback();
    echo "An error occurred: " . $e->getMessage() . "<br>";
}
?>
