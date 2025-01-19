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
    <!-- Use stable Bootstrap 5.3 bundle (includes Popper) -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    >
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
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="list-group">
                    <!-- 
                      Using data-bs-toggle="collapse" and data-bs-target="#summary", etc.
                      We leave href off entirely (or use `href="#"` if you prefer).
                    -->
                    <a 
                       class="list-group-item list-group-item-action active"
                       data-bs-toggle="collapse"
                       data-bs-target="#summary"
                       aria-expanded="true"
                    >
                        Podsumowanie
                    </a>
                    <a 
                       class="list-group-item list-group-item-action"
                       data-bs-toggle="collapse"
                       data-bs-target="#users"
                       aria-expanded="false"
                    >
                        Użytkownicy
                    </a>
                    <a 
                       class="list-group-item list-group-item-action"
                       data-bs-toggle="collapse"
                       data-bs-target="#bookings"
                       aria-expanded="false"
                    >
                        Rezerwacje
                    </a>
                    <a 
                       class="list-group-item list-group-item-action"
                       data-bs-toggle="collapse"
                       data-bs-target="#vehicles"
                       aria-expanded="false"
                    >
                        Pojazdy
                    </a>
                    <a 
                       class="list-group-item list-group-item-action"
                       data-bs-toggle="collapse"
                       data-bs-target="#maintenance"
                       aria-expanded="false"
                    >
                        Konserwacja
                    </a>
                    <a 
                       class="list-group-item list-group-item-action"
                       data-bs-toggle="collapse"
                       data-bs-target="#reports"
                       aria-expanded="false"
                    >
                        Raporty
                    </a>
                    <a 
                       class="list-group-item list-group-item-action"
                       data-bs-toggle="collapse"
                       data-bs-target="#contracts"
                       aria-expanded="false"
                    >
                        Umowy
                    </a>
                    <a 
                       class="list-group-item list-group-item-action"
                       data-bs-toggle="collapse"
                       data-bs-target="#notifications"
                       aria-expanded="false"
                    >
                        Powiadomienia
                    </a>
                    <a 
                       class="list-group-item list-group-item-action"
                       data-bs-toggle="collapse"
                       data-bs-target="#notification-settings"
                       aria-expanded="false"
                    >
                        Ustawienia Powiadomień
                    </a>
                    <a 
                       class="list-group-item list-group-item-action"
                       data-bs-toggle="collapse"
                       data-bs-target="#manage-admins"
                       aria-expanded="false"
                    >
                        Zarządzaj Administratorami
                    </a>
                    <a 
                       class="list-group-item list-group-item-action"
                       data-bs-toggle="collapse"
                       data-bs-target="#signature-management"
                       aria-expanded="false"
                    >
                        Zarządzaj Podpisami
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">

                <!-- Summary (shown by default) -->
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

                <!-- Users -->
                <div id="users" class="collapse">
                    <h2 class="mt-5">Użytkownicy</h2>
                    <!-- Add user management functionality here -->
                </div>

                <!-- Bookings -->
                <div id="bookings" class="collapse">
                    <h2 class="mt-5">Rezerwacje</h2>
                    <?php include '../../views/admin/bookings.php'; ?>
                </div>

                <!-- Vehicles -->
                <div id="vehicles" class="collapse">
                    <h2 class="mt-5">Pojazdy</h2>
                    <?php include '../../views/admin/fleet.php'; ?>
                </div>

                <!-- Maintenance -->
                <div id="maintenance" class="collapse">
                    <h2 class="mt-5">Konserwacja</h2>
                    <?php include '../../views/admin/maintenance_logs.php'; ?>
                </div>

                <!-- Reports -->
                <div id="reports" class="collapse">
                    <h2 class="mt-5">Raporty</h2>
                    <?php include '../../views/admin/reports.php'; ?>
                </div>

                <!-- Contracts -->
                <div id="contracts" class="collapse">
                    <h2 class="mt-5">Umowy</h2>
                    <?php include '../../views/admin/contracts.php'; ?>
                </div>

                <!-- Notifications -->
                <div id="notifications" class="collapse">
                    <h2 class="mt-5">Powiadomienia</h2>
                    <?php include '../../views/admin/notifications.php'; ?>
                </div>

                <!-- Notification Settings -->
                <div id="notification-settings" class="collapse">
                    <h2 class="mt-5">Ustawienia Powiadomień</h2>
                    <?php include '../../views/admin/notification_settings.php'; ?>
                </div>

                <!-- Manage Admins -->
                <div id="manage-admins" class="collapse">
                    <h2 class="mt-5">Zarządzaj Administratorami</h2>
                    <!-- Add manage admins functionality here -->
                </div>

                <!-- Signature Management -->
                <div id="signature-management" class="collapse">
                    <h2 class="mt-5">Zarządzaj Podpisami</h2>
                    <?php include '../../views/admin/signature_management.php'; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 Bundle (includes Popper) -->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
    ></script>

</body>
</html>
