<?php
$host = 'localhost';
$username = 'u122931475_user';
$password = 'Japierdole!876';
$database = 'u122931475_carfuse';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
