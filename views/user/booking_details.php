<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';


// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/public/login.php');
}

$bookingId = $_GET['id'] ?? null;

// Fetch booking details
$stmt = $conn->prepare("
    SELECT b.id, f.make, f.model, f.registration_number, b.pickup_date, b.dropoff_date, b.total_price, b.rental_contract_pdf
    FROM bookings b
    JOIN fleet f ON b.vehicle_id = f.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $bookingId, $_SESSION['user_id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    $_SESSION['error_message'] = "Rezerwacja nie została znaleziona.";
    redirect('/public/profile.php');
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szczegóły Rezerwacji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../../views/shared/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Szczegóły Rezerwacji</h1>

        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Pojazd: <?php echo "{$booking['make']} {$booking['model']}"; ?></h5>
                <p class="card-text"><strong>Numer Rejestracyjny:</strong> <?php echo htmlspecialchars($booking['registration_number']); ?></p>
                <p class="card-text"><strong>Data Odbioru:</strong> <?php echo date('d-m-Y', strtotime($booking['pickup_date'])); ?></p>
                <p class="card-text"><strong>Data Zwrotu:</strong> <?php echo date('d-m-Y', strtotime($booking['dropoff_date'])); ?></p>
                <p class="card-text"><strong>Cena Całkowita:</strong> <?php echo number_format($booking['total_price'], 2); ?> PLN</p>

                <?php if ($booking['rental_contract_pdf'] && file_exists("../../documents/contracts/{$booking['rental_contract_pdf']}")): ?>
                    <a href="/documents/contracts/<?php echo $booking['rental_contract_pdf']; ?>" class="btn btn-primary" download>Pobierz Umowę</a>
                <?php else: ?>
                    <p class="text-danger">Plik umowy nie jest dostępny.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
