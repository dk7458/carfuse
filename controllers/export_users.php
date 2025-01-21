<?php
// File Path: /controllers/export_users.php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/session_middleware.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit("Brak dostępu.");
}

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
