<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /controllers/export_users.php
require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/session_middleware.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

enforceRole(['admin', 'super_admin'],'/public/login.php'); 

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=uzytkownicy.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Write header row
fputcsv($output, ['ID', 'Imię i Nazwisko', 'E-mail', 'Telefon', 'Rola', 'Status', 'Data utworzenia']);

// Fetch all users
$query = "SELECT id, CONCAT(name, ' ', surname) AS full_name, email, phone, role, status, created_at FROM users ORDER BY id";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['full_name'],
        $row['email'],
        $row['phone'],
        $row['role'],
        $row['status'] ? 'Aktywny' : 'Nieaktywny',
        $row['created_at'],
    ]);
}

fclose($output);
exit();
?>
