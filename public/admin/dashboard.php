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
                    <a href="#manage-admins" class="list-group-item list-group-item-action" data-bs-toggle="collapse" aria-expanded="false">Zarządzaj Administratorami</a>
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
                    <!-- Add user management functionality here -->
                </div>

                <div id="bookings" class="collapse">
                    <h2 class="mt-5">Rezerwacje</h2>
                    <!-- Add booking management functionality here -->
                </div>

                <div id="vehicles" class="collapse">
                    <h2 class="mt-5">Pojazdy</h2>
                    <!-- Add vehicle management functionality here -->
                </div>

                <div id="maintenance" class="collapse">
                    <h2 class="mt-5">Konserwacja</h2>
                    <!-- Add maintenance management functionality here -->
                </div>

                <div id="reports" class="collapse">
                    <h2 class="mt-5">Raporty</h2>
                    <!-- Add reports management functionality here -->
                </div>

                <div id="contracts" class="collapse">
                    <h2 class="mt-5">Umowy</h2>
                    <!-- Add contracts management functionality here -->
                </div>

                <div id="manage-admins" class="collapse">
                    <h2 class="mt-5">Zarządzaj Administratorami</h2>
                    <!-- Add manage admins functionality here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>
