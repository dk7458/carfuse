<?php
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';


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
    
    <!-- Same Bootstrap alpha version as in user dashboard for consistency -->
    <link 
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" 
        rel="stylesheet"
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
            <div class="col-md-3">
                <div class="list-group">
                    <!-- Use href="#..." + data-bs-toggle="collapse" 
                         exactly like the user dashboard does -->
                    <a 
                        href="#summary" 
                        class="list-group-item list-group-item-action active" 
                        data-bs-toggle="collapse" 
                        aria-expanded="true"
                    >
                        Podsumowanie
                    </a>
                    <a 
                        href="#users" 
                        class="list-group-item list-group-item-action" 
                        data-bs-toggle="collapse" 
                        aria-expanded="false"
                    >
                        Użytkownicy
                    </a>
                    <a 
                        href="#bookings" 
                        class="list-group-item list-group-item-action" 
                        data-bs-toggle="collapse" 
                        aria-expanded="false"
                    >
                        Rezerwacje
                    </a>
                    <a 
                        href="#vehicles" 
                        class="list-group-item list-group-item-action" 
                        data-bs-toggle="collapse" 
                        aria-expanded="false"
                    >
                        Pojazdy
                    </a>
                    <a 
                        href="#maintenance" 
                        class="list-group-item list-group-item-action" 
                        data-bs-toggle="collapse" 
                        aria-expanded="false"
                    >
                        Konserwacja
                    </a>
                    <a 
                        href="#reports" 
                        class="list-group-item list-group-item-action" 
                        data-bs-toggle="collapse" 
                        aria-expanded="false"
                    >
                        Raporty
                    </a>
                    <a 
                        href="#contracts" 
                        class="list-group-item list-group-item-action" 
                        data-bs-toggle="collapse" 
                        aria-expanded="false"
                    >
                        Umowy
                    </a>
                    <a 
                        href="#notifications" 
                        class="list-group-item list-group-item-action" 
                        data-bs-toggle="collapse" 
                        aria-expanded="false"
                    >
                        Powiadomienia
                    </a>
                    <a 
                        href="#notification-settings" 
                        class="list-group-item list-group-item-action" 
                        data-bs-toggle="collapse" 
                        aria-expanded="false"
                    >
                        Ustawienia Powiadomień
                    </a>
                    <a 
                        href="#manage-admins" 
                        class="list-group-item list-group-item-action" 
                        data-bs-toggle="collapse" 
                        aria-expanded="false"
                    >
                        Zarządzaj Administratorami
                    </a>
                    <a 
                        href="#signature-management" 
                        class="list-group-item list-group-item-action" 
                        data-bs-toggle="collapse" 
                        aria-expanded="false"
                    >
                        Zarządzaj Podpisami
                    </a>
                </div>
            </div>
            <div class="col-md-9">
                <!-- SUMMARY: shown by default -->
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

                <!-- USERS -->
                <div id="users" class="collapse">
                    <h2 class="mt-5">Użytkownicy</h2>
                    <!-- Add user management functionality here -->
                </div>

                <!-- BOOKINGS -->
                <div id="bookings" class="collapse">
                    <h2 class="mt-5">Rezerwacje</h2>
                    <?php include '../../views/admin/bookings.php'; ?>
                </div>

                <!-- VEHICLES -->
                <div id="vehicles" class="collapse">
                    <h2 class="mt-5">Pojazdy</h2>
                    <?php include '../../views/admin/fleet.php'; ?>
                </div>

                <!-- MAINTENANCE -->
                <div id="maintenance" class="collapse">
                    <h2 class="mt-5">Konserwacja</h2>
                    <?php include '../../views/admin/maintenance_logs.php'; ?>
                </div>

                <!-- REPORTS -->
                <div id="reports" class="collapse">
                    <h2 class="mt-5">Raporty</h2>
                    <?php include '../../views/admin/reports.php'; ?>
                </div>

                <!-- CONTRACTS -->
                <div id="contracts" class="collapse">
                    <h2 class="mt-5">Umowy</h2>
                    <?php include '../../views/admin/contracts.php'; ?>
                </div>

                <!-- NOTIFICATIONS -->
                <div id="notifications" class="collapse">
                    <h2 class="mt-5">Powiadomienia</h2>
                    <?php include '../../views/admin/notifications.php'; ?>
                </div>

                <!-- NOTIFICATION SETTINGS -->
                <div id="notification-settings" class="collapse">
                    <h2 class="mt-5">Ustawienia Powiadomień</h2>
                    <?php include '../../views/admin/notification_settings.php'; ?>
                </div>

                <!-- MANAGE ADMINS -->
                <div id="manage-admins" class="collapse">
                    <h2 class="mt-5">Zarządzaj Administratorami</h2>
                    <?php include '../../views/admin/manage_admins.php'; ?>
                </div>

                <!-- SIGNATURE MANAGEMENT -->
                <div id="signature-management" class="collapse">
                    <h2 class="mt-5">Zarządzaj Podpisami</h2>
                    <?php include '../../views/admin/signature_management.php'; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- We still include jQuery in case you need it for other purposes, 
         but we're NOT using it for collapse toggling anymore. -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Popper & Bootstrap JS (alpha version, matching the user dashboard) -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

    <!-- Manual approach: same logic as the user dashboard 
         to close other sections upon opening one. -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.list-group-item-action').forEach(link => {
                link.addEventListener('click', function() {
                    // Grab the #someSection part from href
                    const targetId = this.getAttribute('href').substring(1);

                    // Close all other collapses
                    document.querySelectorAll('.collapse').forEach(section => {
                        if (section.id !== targetId) {
                            section.classList.remove('show');
                        }
                    });
                    // We let Bootstrap's "data-bs-toggle='collapse'" handle 
                    // opening the clicked one automatically.
                });
            });
        });
    </script>
</body>
</html>
