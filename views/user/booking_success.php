<?php
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';


// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/public/login.php');
}

// Get booking details
$bookingId = intval($_GET['booking_id']);
$booking = $conn->query("SELECT * FROM bookings WHERE id = $bookingId AND user_id = {$_SESSION['user_id']}")->fetch_assoc();

if (!$booking) {
    die("Nie znaleziono rezerwacji.");
}

$contractLink = "/documents/contracts/contract_{$bookingId}.pdf";
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potwierdzenie Rezerwacji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../../views/shared/navbar.php'; ?>

    <div class="container mt-5">
        <div class="alert alert-success">
            <h1 class="text-center">Rezerwacja Potwierdzona!</h1>
            <p class="text-center">Dziękujemy za rezerwację. Twoje ID rezerwacji to: <strong><?php echo $bookingId; ?></strong>.</p>
        </div>

        <div class="text-center">
            <a href="<?php echo $contractLink; ?>" class="btn btn-primary" target="_blank">Pobierz Umowę</a>
            <a href="/public/profile.php" class="btn btn-secondary">Powrót do Profilu</a>
        </div>
    </div>
</body>
</html>
