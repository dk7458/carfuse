<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /views/admin/booking_manager.php
require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'functions/global.php';


// Ensure the user has sufficient privileges
if (!in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    redirect('/public/login.php');
}

// Fetch data using the centralized proxy
$filters = [
    'search' => $_GET['search'] ?? '',
    'startDate' => $_GET['start_date'] ?? '',
    'endDate' => $_GET['end_date'] ?? ''
];
$queryString = http_build_query($filters);
$response = file_get_contents(BASE_URL . "/public/api.php?endpoint=booking&action=fetch_bookings&" . $queryString);
$data = json_decode($response, true);

if ($data['success']) {
    $bookings = $data['bookings'];
} else {
    $bookings = [];
}

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
        <form method="POST" action="/public/api.php?endpoint=bookings&action=add_booking">
            <input type="text" name="customer_name" placeholder="Enter customer name">
            <button type="submit">Add Booking</button>
        </form>
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
                    <?php foreach ($bookings as $booking): ?>
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
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="/assets/js/booking_manager.js"></script>
</body>
</html>
