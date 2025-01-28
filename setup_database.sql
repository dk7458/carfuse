-- Create admin_notification_settings table
CREATE TABLE IF NOT EXISTS admin_notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    contract_alerts TINYINT(1) DEFAULT 0,
    maintenance_alerts TINYINT(1) DEFAULT 0,
    booking_reminders TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create availability table
CREATE TABLE IF NOT EXISTS availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (vehicle_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_reference INT NOT NULL, -- Replace `user_id` with secure reference
    vehicle_id INT NOT NULL,
    pickup_date DATE NOT NULL,
    dropoff_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'canceled') DEFAULT 'active',
    rental_contract_pdf VARCHAR(255) DEFAULT NULL,
    canceled_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    refund_status ENUM('none', 'requested', 'processed') DEFAULT 'none'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create fleet table
CREATE TABLE IF NOT EXISTS fleet (
    id INT AUTO_INCREMENT PRIMARY KEY,
    make VARCHAR(255) NOT NULL,
    model VARCHAR(255) NOT NULL,
    registration_number VARCHAR(20) NOT NULL,
    availability TINYINT(1) DEFAULT 1,
    last_maintenance_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    next_maintenance_date DATE DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create maintenance_logs table
CREATE TABLE IF NOT EXISTS maintenance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    description TEXT NOT NULL,
    maintenance_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create refund_logs table
CREATE TABLE IF NOT EXISTS refund_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    refunded_amount DECIMAL(10,2) NOT NULL,
    refund_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create payment_methods table
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_reference INT NOT NULL, -- Replace `user_id` with secure reference
    method_name VARCHAR(255) NOT NULL,
    details VARCHAR(255) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
