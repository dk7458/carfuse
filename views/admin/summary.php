$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /views/admin/summary.php
require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'controllers/summary_ctrl.php';

require_once BASE_PATH . 'includes/functions.php';


enforceRole(['admin', 'super_admin']); 

// Fetch summary data
$summary = getSummaryData();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Podsumowanie - Panel Administratora</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/summary.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container mt-5">
        <h1>Podsumowanie</h1>

        <!-- Quick Statistics -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h2><?= $summary['total_users'] ?></h2>
                        <p>Łączna liczba użytkowników</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h2><?= $summary['total_bookings'] ?></h2>
                        <p>Łączna liczba rezerwacji</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h2><?= $summary['available_fleet'] ?> / <?= $summary['total_fleet'] ?></h2>
                        <p>Dostępne pojazdy</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <h2 class="mt-5">Ostatnie Aktywności</h2>
        <div class="row">
            <div class="col-md-6">
                <h3>Ostatnio Dodani Użytkownicy</h3>
                <ul class="list-group">
                    <?php foreach ($summary['recent_users'] as $user): ?>
                        <li class="list-group-item">
                            <?= htmlspecialchars($user['name']) ?> - <?= htmlspecialchars($user['email']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-md-6">
                <h3>Ostatnie Rezerwacje</h3>
                <ul class="list-group">
                    <?php foreach ($summary['recent_bookings'] as $booking): ?>
                        <li class="list-group-item">
                            <?= htmlspecialchars($booking['vehicle']) ?> - <?= htmlspecialchars($booking['user']) ?> (<?= $booking['pickup_date'] ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Visualizations -->
        <h2 class="mt-5">Wizualizacje</h2>
        <div class="row">
            <div class="col-md-6">
                <canvas id="bookingChart"></canvas>
            </div>
            <div class="col-md-6">
                <canvas id="fleetChart"></canvas>
            </div>
        </div>
    </div>

    <script src="/assets/js/summary.js"></script>
</body>
</html>
