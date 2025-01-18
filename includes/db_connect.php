<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = 'your_password';
$database = 'car_rental';

try {
    $conn = new mysqli($host, $username, $password, $database);

    // Check for connection errors
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Set character encoding
    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    error_log($e->getMessage()); // Log the error
    die("Błąd połączenia z bazą danych. Spróbuj ponownie później.");
}
?>
