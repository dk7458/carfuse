$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';


// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/public/login.php');
}

$userId = $_SESSION['user_id'];

// Fetch user bookings
$bookings = $conn->query("
    SELECT b.id, f.make, f.model, f.registration_number, b.pickup_date, b.dropoff_date, b.total_price, b.rental_contract_pdf 
    FROM bookings b 
    JOIN fleet f ON b.vehicle_id = f.id 
    WHERE b.user_id = $userId
    ORDER BY b.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Użytkownika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../views/shared/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Twój Profil</h1>
        <p class="text-center">Zarządzaj swoimi rezerwacjami poniżej.</p>

        <?php if ($bookings->num_rows > 0): ?>
            <table class="table table-bordered mt-4">
                <thead>
                    <tr>
                        <th>ID Rezerwacji</th>
                        <th>Pojazd</th>
                        <th>Numer Rejestracyjny</th>
                        <th>Data Odbioru</th>
                        <th>Data Zwrotu</th>
                        <th>Cena Całkowita</th>
                        <th>Umowa</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $booking['id']; ?></td>
                            <td><?php echo "{$booking['make']} {$booking['model']}"; ?></td>
                            <td><?php echo $booking['registration_number']; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($booking['pickup_date'])); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($booking['dropoff_date'])); ?></td>
                            <td><?php echo number_format($booking['total_price'], 2, ',', ' '); ?> PLN</td>
                            <td>
                                <?php if ($booking['rental_contract_pdf']): ?>
                                    <a href="<?php echo $booking['rental_contract_pdf']; ?>" target="_blank">Pobierz</a>
                                <?php else: ?>
                                    Niedostępna
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/controllers/booking_controller.php?action=cancel&id=<?php echo $booking['id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Czy na pewno chcesz anulować tę rezerwację?');">Anuluj</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info text-center mt-4">
                Nie masz żadnych aktywnych rezerwacji.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
