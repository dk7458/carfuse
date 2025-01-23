<?php
// File Path: /views/admin/booking_manager.php
require_once BASE_PATH . '../includes/session_middleware.php';

require_once BASE_PATH . '../includes/db_connect.php';

require_once BASE_PATH . '../includes/functions.php';


// Ensure the user has sufficient privileges
if (!in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    redirect('/public/login.php');
}

// Fetch bookings
$bookings = $conn->query("
    SELECT 
        b.id, b.user_id, u.name AS user_name, u.email AS user_email, 
        f.make AS vehicle_make, f.model AS vehicle_model, 
        b.pickup_date, b.dropoff_date, b.total_price, b.status, b.refund_status 
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
    <title>Manager Rezerwacji</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container mt-5">
        <h1>Manager Rezerwacji</h1>
        <div class="table-responsive">
            <table class="table table-bordered mt-4">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Użytkownik</th>
                        <th>Pojazd</th>
                        <th>Data Odbioru</th>
                        <th>Data Zwrotu</th>
                        <th>Kwota</th>
                        <th>Status</th>
                        <th>Zwrot</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($booking['id']) ?></td>
                            <td><?= htmlspecialchars($booking['user_name']) ?> (<?= htmlspecialchars($booking['user_email']) ?>)</td>
                            <td><?= htmlspecialchars($booking['vehicle_make'] . ' ' . $booking['vehicle_model']) ?></td>
                            <td><?= htmlspecialchars($booking['pickup_date']) ?></td>
                            <td><?= htmlspecialchars($booking['dropoff_date']) ?></td>
                            <td><?= number_format($booking['total_price'], 2, ',', ' ') ?> PLN</td>
                            <td>
                                <span class="badge <?= $booking['status'] === 'paid' ? 'bg-success' : 'bg-warning' ?>">
                                    <?= ucfirst(htmlspecialchars($booking['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($booking['refund_status'] === 'none'): ?>
                                    <button 
                                        class="btn btn-danger btn-sm refund-button" 
                                        data-id="<?= $booking['id'] ?>" 
                                        data-amount="<?= htmlspecialchars($booking['total_price']) ?>">
                                        Zwrot
                                    </button>
                                <?php elseif ($booking['refund_status'] === 'requested'): ?>
                                    <span class="badge bg-warning">Oczekuje</span>
                                <?php elseif ($booking['refund_status'] === 'processed'): ?>
                                    <span class="badge bg-success">Zwrócono</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/users/user<?= $booking['user_id'] ?>/documents/contract_<?= $booking['id'] ?>.pdf" 
                                   class="btn btn-primary btn-sm" target="_blank">Pobierz Umowę</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="/assets/js/booking_manager.js"></script>
</body>
</html>
