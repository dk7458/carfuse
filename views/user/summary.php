<?php

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';


// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    redirect('/public/login.php');
}

$userId = $_SESSION['user_id'];

try {
    // Fetch user-specific summary data
    $stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM bookings WHERE user_id = ?) AS bookingCount,
            (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND pickup_date >= CURDATE()) AS upcomingBookings,
            (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'pending_payment') AS pendingPayments,
            (SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE user_id = ? AND status = 'paid') AS totalSpent
    ");
    $stmt->bind_param('iiii', $userId, $userId, $userId, $userId);
    $stmt->execute();
    $stmt->bind_result($bookingCount, $upcomingBookings, $pendingPayments, $totalSpent);
    $stmt->fetch();
    $stmt->close();

    // Fetch recent bookings
    $recentStmt = $conn->prepare("
        SELECT id, pickup_date, dropoff_date, total_price, status 
        FROM bookings 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentStmt->bind_param('i', $userId);
    $recentStmt->execute();
    $recentBookings = $recentStmt->get_result();
    $recentStmt->close();
} catch (Exception $e) {
    logError($e->getMessage());
    $bookingCount = $upcomingBookings = $pendingPayments = $totalSpent = 0;
    $recentBookings = [];
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Podsumowanie Użytkownika</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include '../shared/navbar_user.php'; ?>

    <div class="container mt-5">
        <h1>Podsumowanie</h1>

        <div class="row g-4 mt-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Łączna liczba rezerwacji</h5>
                        <p class="card-text fs-3"><?= htmlspecialchars($bookingCount) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Nadchodzące rezerwacje</h5>
                        <p class="card-text fs-3"><?= htmlspecialchars($upcomingBookings) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Oczekujące płatności</h5>
                        <p class="card-text fs-3"><?= htmlspecialchars($pendingPayments) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Łączna kwota wydana</h5>
                        <p class="card-text fs-3"><?= number_format($totalSpent, 2, ',', ' ') ?> PLN</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="mt-5">
            <h3>Ostatnie Rezerwacje</h3>
            <table class="table table-bordered mt-3">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data Odbioru</th>
                        <th>Data Zwrotu</th>
                        <th>Kwota</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentBookings && $recentBookings->num_rows > 0): ?>
                        <?php while ($booking = $recentBookings->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($booking['id']) ?></td>
                                <td><?= htmlspecialchars($booking['pickup_date']) ?></td>
                                <td><?= htmlspecialchars($booking['dropoff_date']) ?></td>
                                <td><?= number_format($booking['total_price'], 2, ',', ' ') ?> PLN</td>
                                <td>
                                    <span class="badge <?= $booking['status'] === 'paid' ? 'bg-success' : ($booking['status'] === 'pending_payment' ? 'bg-warning' : 'bg-secondary') ?>">
                                        <?= ucfirst(htmlspecialchars($booking['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Brak ostatnich rezerwacji.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
