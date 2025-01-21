<?php
// File Path: /views/user/my_bookings.php
require_once __DIR__ . '/../../includes/session_middleware.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/functions.php';

$userId = $_SESSION['user_id'];

// Fetch user's bookings
$stmt = $conn->prepare("
    SELECT b.id, f.make, f.model, b.pickup_date, b.dropoff_date, b.total_price, b.status, b.rental_contract_pdf 
    FROM bookings b 
    JOIN fleet f ON b.vehicle_id = f.id 
    WHERE b.user_id = ? 
    ORDER BY b.pickup_date DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moje Rezerwacje</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
</head>
<body>
    <?php include '../shared/navbar_user.php'; ?>

    <div class="container">
        <h1 class="mt-5">Moje Rezerwacje</h1>

        <?php if ($bookings->num_rows > 0): ?>
            <table class="table mt-4">
                <thead>
                    <tr>
                        <th>Samochód</th>
                        <th>Data Odbioru</th>
                        <th>Data Zwrotu</th>
                        <th>Cena</th>
                        <th>Status</th>
                        <th>Umowa</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($booking['make'] . ' ' . $booking['model']) ?></td>
                            <td><?= htmlspecialchars($booking['pickup_date']) ?></td>
                            <td><?= htmlspecialchars($booking['dropoff_date']) ?></td>
                            <td><?= number_format($booking['total_price'], 2, ',', ' ') ?> PLN</td>
                            <td><?= $booking['status'] === 'paid' ? 'Opłacona' : 'Nieopłacona' ?></td>
                            <td>
                                <?php if (!empty($booking['rental_contract_pdf'])): ?>
                                    <a href="<?= htmlspecialchars($booking['rental_contract_pdf']) ?>" target="_blank" class="btn btn-sm btn-primary">Pobierz</a>
                                <?php else: ?>
                                    Niedostępna
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($booking['status'] === 'paid' && strtotime($booking['pickup_date']) > time()): ?>
                                    <form action="/controllers/booking_controller.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="cancel_booking">
                                        <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['id']) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno chcesz anulować tę rezerwację?')">Anuluj</button>
                                    </form>
                                <?php else: ?>
                                    ---
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info mt-4">Nie masz żadnych rezerwacji.</div>
        <?php endif; ?>
    </div>
</body>
</html>
