

<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';
require_once BASE_PATH . 'functions/global.php';

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Fetch all bookings
$bookings = $conn->query("
    SELECT b.id, u.name AS customer_name, f.make, f.model, f.registration_number, b.pickup_date, b.dropoff_date, 
           b.total_price, b.status, b.canceled_at 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN fleet f ON b.vehicle_id = f.id 
    ORDER BY b.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie Rezerwacjami</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Theme -->
    <link rel="stylesheet" href="/theme.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Zarządzanie Rezerwacjami</h1>

        <?php if ($bookings->num_rows > 0): ?>
            <table class="table table-bordered mt-4">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Klient</th>
                        <th>Pojazd</th>
                        <th>Numer Rejestracyjny</th>
                        <th>Data Odbioru</th>
                        <th>Data Zwrotu</th>
                        <th>Cena</th>
                        <th>Status</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $booking['id']; ?></td>
                            <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                            <td><?php echo "{$booking['make']} {$booking['model']}"; ?></td>
                            <td><?php echo $booking['registration_number']; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($booking['pickup_date'])); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($booking['dropoff_date'])); ?></td>
                            <td><?php echo number_format($booking['total_price'], 2, ',', ' '); ?> PLN</td>
                            <td><?php echo $booking['status'] === 'active' ? 'Aktywna' : 'Anulowana'; ?></td>
                            <td>
                                <?php if ($booking['status'] === 'active'): ?>
                                    <a href="/controllers/admin_booking_controller.php?action=cancel&id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Czy na pewno chcesz anulować tę rezerwację?');">Anuluj</a>
                                <?php else: ?>
                                    <a href="/controllers/admin_booking_controller.php?action=reactivate&id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-success btn-sm"
                                       onclick="return confirm('Czy na pewno chcesz przywrócić tę rezerwację?');">Przywróć</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info text-center mt-4">
                Brak rezerwacji do wyświetlenia.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
