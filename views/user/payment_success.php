<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /views/user/payment_success.php
require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';


header('Content-Type: text/html; charset=UTF-8');

$bookingId = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if (!$bookingId) {
    die("<h1>Błąd</h1><p>Nieprawidłowy identyfikator rezerwacji.</p>");
}

// Fetch booking details
$stmt = $conn->prepare("SELECT vehicle_id, pickup_date, dropoff_date, total_price FROM bookings WHERE id = ?");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("<h1>Błąd</h1><p>Nie znaleziono rezerwacji.</p>");
}

$contractPath = "/users/user{$_SESSION['user_id']}/documents/contract_$bookingId.pdf";
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Płatność Zakończona Sukcesem</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
</head>
<body>
    <div class="container">
        <h1>Dziękujemy za rezerwację!</h1>
        <p>Twoja płatność została pomyślnie zakończona.</p>
        <p><strong>Szczegóły rezerwacji:</strong></p>
        <ul>
            <li>Samochód: <?= htmlspecialchars(getVehicleDetails($conn, $booking['vehicle_id'])['model']) ?></li>
            <li>Data odbioru: <?= htmlspecialchars($booking['pickup_date']) ?></li>
            <li>Data zwrotu: <?= htmlspecialchars($booking['dropoff_date']) ?></li>
            <li>Łączna cena: <?= htmlspecialchars($booking['total_price']) ?> PLN</li>
        </ul>
        <a href="<?= htmlspecialchars($contractPath) ?>" class="btn btn-primary">Pobierz umowę</a>
        <a href="/views/user/dashboard.php" class="btn btn-secondary">Wróć do panelu</a>
    </div>
</body>
</html>
