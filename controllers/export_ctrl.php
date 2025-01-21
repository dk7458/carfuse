<?php
// File Path: /controllers/export_ctrl.php
require_once __DIR__ . '/../includes/session_middleware.php';
require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die("Brak dostępu.");
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=maintenance_logs.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Pojazd', 'Numer Rejestracyjny', 'Data Przeglądu', 'Opis', 'Koszt']);

$query = "
    SELECT f.make, f.model, f.registration_number, ml.maintenance_date, ml.description, ml.cost 
    FROM maintenance_logs ml 
    JOIN fleet f ON ml.vehicle_id = f.id
";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['make'] . ' ' . $row['model'],
        $row['registration_number'],
        $row['maintenance_date'],
        $row['description'],
        number_format($row['cost'], 2, ',', ' ')
    ]);
}

fclose($output);
exit();
?>
