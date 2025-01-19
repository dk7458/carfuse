<?php
require '../../includes/db_connect.php';
require '../../includes/functions.php';

session_start();

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}
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
        <h1 class="text-center">Admin Management Hub</h1>

        <div class="list-group mt-4">
            <a href="#reports" class="list-group-item list-group-item-action" data-bs-toggle="collapse">Raporty</a>
            <div id="reports" class="collapse">
                <?php include 'reports_manager.php'; ?>
            </div>

            <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="collapse">Ustawienia Powiadomień</a>
            <div id="notifications" class="collapse">
                <?php include 'notifications_manager.php'; ?>
            </div>

            <a href="#maintenance" class="list-group-item list-group-item-action" data-bs-toggle="collapse">Zarządzanie Konserwacją</a>
            <div id="maintenance" class="collapse">
                <?php include 'maintenance_manager.php'; ?>
            </div>

            <a href="#fleet" class="list-group-item list-group-item-action" data-bs-toggle="collapse">Zarządzanie Flotą</a>
            <div id="fleet" class="collapse">
                <?php include 'fleet_manager.php'; ?>
            </div>

            <a href="#contracts" class="list-group-item list-group-item-action" data-bs-toggle="collapse">Zarządzanie Umowami</a>
            <div id="contracts" class="collapse">
                <?php include 'contract_manager.php'; ?>
            </div>

            <a href="#bookings" class="list-group-item list-group-item-action" data-bs-toggle="collapse">Zarządzanie Rezerwacjami</a>
            <div id="bookings" class="collapse">
                <?php include 'booking_manager.php'; ?>
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
