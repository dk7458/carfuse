<?php
require '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

// Get contracts that expire tomorrow
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$result = $conn->query("
    SELECT b.id, f.make, f.model, u.name 
    FROM bookings b 
    JOIN fleet f ON b.vehicle_id = f.id 
    JOIN users u ON b.user_id = u.id 
    WHERE b.dropoff_date = '$tomorrow' AND b.status = 'active'
");

while ($contract = $result->fetch_assoc()) {
    $message = "Kontrakt ID: {$contract['id']} (Pojazd: {$contract['make']} {$contract['model']}) wygasa jutro.";

    // Send MQTT notification
    sendMQTTNotification("admin/contracts", $message);

    // Optionally, send email
    $emailMessage = "
        <h1>Powiadomienie o Wygaśnięciu Kontraktu</h1>
        <p>{$message}</p>
    ";
    sendEmail("admin@carfuse.com", "Kontrakt Wygasa", $emailMessage);
}
?>
