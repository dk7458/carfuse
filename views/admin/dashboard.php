<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';


// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Fetch metrics
$totalBookings = $conn->query("SELECT COUNT(*) AS count FROM bookings")->fetch_assoc()['count'];
$activeBookings = $conn->query("SELECT COUNT(*) AS count FROM bookings WHERE status = 'active'")->fetch_assoc()['count'];
$totalRevenue = $conn->query("SELECT SUM(total_price) AS revenue FROM bookings WHERE status = 'active'")->fetch_assoc()['revenue'];
$totalFleet = $conn->query("SELECT COUNT(*) AS count FROM fleet")->fetch_assoc()['count'];
$availableFleet = $conn->query("SELECT COUNT(*) AS count FROM fleet WHERE availability = 1")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administratora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../../views/shared/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Panel Administratora</h1>
        <p class="text-center">Podsumowanie najważniejszych statystyk systemu.</p>

        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-center bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Łączna Liczba Rezerwacji</h5>
                        <p class="card-text display-6"><?php echo $totalBookings; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Aktywne Rezerwacje</h5>
                        <p class="card-text display-6"><?php echo $activeBookings; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Całkowity Dochód</h5>
                        <p class="card-text display-6"><?php echo number_format($totalRevenue, 2, ',', ' '); ?> PLN</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Dostępna Flota</h5>
                        <p class="card-text display-6"><?php echo $availableFleet; ?> / <?php echo $totalFleet; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="/views/admin/bookings.php" class="btn btn-primary">Zarządzaj Rezerwacjami</a>
            <a href="/views/admin/reports.php" class="btn btn-secondary">Generuj Raporty</a>
        </div>
    </div>
</body>
</html>
<script src="https://unpkg.com/mqtt/dist/mqtt.min.js"></script>
<script>
    // Connect to the MQTT WebSocket Broker
    const client = mqtt.connect('ws://your-server-address:9001');

    // Subscribe to admin topics
    client.on('connect', () => {
        console.log('Connected to MQTT broker');
        client.subscribe('admin/contracts');
        client.subscribe('admin/maintenance');
    });

    // Handle incoming messages
    client.on('message', (topic, message) => {
        const alertContainer = document.getElementById('mqtt-alerts');
        const alertMessage = document.createElement('div');
        alertMessage.classList.add('alert', 'alert-info', 'mt-2');
        alertMessage.textContent = `${topic}: ${message}`;
        alertContainer.prepend(alertMessage);
    });
</script>

<!-- Real-Time Alerts Section -->
<div class="container mt-4">
    <h3>Powiadomienia w Czasie Rzeczywistym</h3>
    <div id="mqtt-alerts"></div>
</div>
<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';


// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Fetch data for widgets
$totalUsers = $conn->query("SELECT COUNT(*) AS count FROM users")->fetch_assoc()['count'];
$totalBookings = $conn->query("SELECT COUNT(*) AS count FROM bookings")->fetch_assoc()['count'];
$totalVehicles = $conn->query("SELECT COUNT(*) AS count FROM fleet")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <?php include '../../views/shared/header.php'; ?>
</head>
<body>
    <?php include '../../views/shared/navbar.php'; ?>

    <div class="container mt-5">
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
</body>
</html>


