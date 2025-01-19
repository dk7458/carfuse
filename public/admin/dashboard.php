<?php
require '../../includes/db_connect.php';
require '../../includes/functions.php';

session_start();

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Fetch summary data
$totalUsers = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
$totalBookings = $conn->query("SELECT COUNT(*) AS count FROM bookings")->fetch_assoc()['count'];
$totalVehicles = $conn->query("SELECT COUNT(*) AS count FROM fleet")->fetch_assoc()['count'];

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
    <title>Panel Administratora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/theme.css">
    <style>
        body {
            overflow-y: scroll;
        }
    </style>
</head>
<body>
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="#summary" class="list-group-item list-group-item-action active" data-bs-toggle="collapse" aria-expanded="true">Podsumowanie</a>
                    <a href="#users" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Użytkownicy</a>
                    <a href="#bookings" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Rezerwacje</a>
                    <a href="#vehicles" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Pojazdy</a>
                    <a href="#maintenance" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Konserwacja</a>
                    <a href="#reports" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Raporty</a>
                    <a href="#contracts" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Umowy</a>
                    <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Powiadomienia</a>
                    <a href="#notification-settings" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Ustawienia Powiadomień</a>
                    <a href="#manage-admins" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Zarządzaj Administratorami</a>
                    <a href="#signature-management" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Zarządzaj Podpisami</a>
                </div>
            </div>
            <div class="col-md-9">
                <div id="summary" class="collapse show">
                    <h1 class="text-center">Panel Administratora</h1>
                    <div class="row mt-5">
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title">Użytkownicy</h5>
                                    <p class="card-text"><?php echo $totalUsers; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title">Rezerwacje</h5>
                                    <p class="card-text"><?php echo $totalBookings; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title">Pojazdy</h5>
                                    <p class="card-text"><?php echo $totalVehicles; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="users" class="collapse">
                    <h2 class="mt-5">Użytkownicy</h2>
                    <?php include '../../views/admin/manage_users.php'; ?>
                </div>

                <div id="bookings" class="collapse">
                    <h2 class="mt-5">Rezerwacje</h2>
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

                <div id="vehicles" class="collapse">
                    <h2 class="mt-5">Pojazdy</h2>
                    <?php include '../../views/admin/fleet.php'; ?>
                </div>

                <div id="maintenance" class="collapse">
                    <h2 class="mt-5">Konserwacja</h2>
                    <?php include '../../views/admin/maintenance_logs.php'; ?>
                </div>

                <div id="reports" class="collapse">
                    <h2 class="mt-5">Raporty</h2>
                    <?php include '../../views/admin/reports.php'; ?>
                </div>

                <div id="contracts" class="collapse">
                    <h2 class="mt-5">Umowy</h2>
                    <?php include '../../views/admin/contracts.php'; ?>
                </div>

                <div id="notifications" class="collapse">
                    <h2 class="mt-5">Powiadomienia</h2>
                    <?php include '../../views/admin/notifications.php'; ?>
                </div>

                <div id="notification-settings" class="collapse">
                    <h2 class="mt-5">Ustawienia Powiadomień</h2>
                    <?php include '../../views/admin/notification_settings.php'; ?>
                </div>

                <div id="manage-admins" class="collapse">
                    <h2 class="mt-5">Zarządzaj Administratorami</h2>
                    <?php include '../../views/admin/manage_admins.php'; ?>
                </div>

                <div id="signature-management" class="collapse">
                    <h2 class="mt-5">Zarządzaj Podpisami</h2>
                    <?php include '../../views/admin/signature_management.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.list-group-item-action').on('click', function() {
                var target = $(this).attr('href');
                $('.collapse').not(target).collapse('hide');
                $(target).collapse('show');
            });
        });
    </script>
</body>
</html>
