<?php
require '/home/u122931475/domains/carfuse.pl/public_html/../includes/db_connect.php';
require '/home/u122931475/domains/carfuse.pl/public_html/../includes/functions.php';

session_start();

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Fetch summary data
$totalUsers = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
$totalBookings = $conn->query("SELECT COUNT(*) AS count FROM bookings")->fetch_assoc()['count'];
$totalVehicles = $conn->query("SELECT COUNT(*) AS count FROM fleet")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/theme.css">
</head>
<body>
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="#summary" class="list-group-item list-group-item-action active" data-bs-toggle="collapse" aria-expanded="true">Podsumowanie</a>
                    <a href="#reports" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Raporty</a>
                    <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Ustawienia Powiadomień</a>
                    <a href="#maintenance" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Zarządzanie Konserwacją</a>
                    <a href="#fleet" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Zarządzanie Flotą</a>
                    <a href="#contracts" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Zarządzanie Umowami</a>
                    <a href="#bookings" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Zarządzanie Rezerwacjami</a>
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

                <div id="reports" class="collapse">
                    <h2 class="mt-5">Raporty</h2>
                    <?php include 'reports_management.php'; ?>
                </div>

                <div id="notifications" class="collapse">
                    <h2 class="mt-5">Ustawienia Powiadomień</h2>
                    <?php include 'notifications_management.php'; ?>
                </div>

                <div id="maintenance" class="collapse">
                    <h2 class="mt-5">Zarządzanie Konserwacją</h2>
                    <?php include 'maintenance_management.php'; ?>
                </div>

                <div id="fleet" class="collapse">
                    <h2 class="mt-5">Zarządzanie Flotą</h2>
                    <?php include 'fleet_management.php'; ?>
                </div>

                <div id="contracts" class="collapse">
                    <h2 class="mt-5">Zarządzanie Umowami</h2>
                    <?php include 'contract_management.php'; ?>
                </div>

                <div id="bookings" class="collapse">
                    <h2 class="mt-5">Zarządzanie Rezerwacjami</h2>
                    <?php include 'booking_management.php'; ?>
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
