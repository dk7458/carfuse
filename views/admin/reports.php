<?php
require '../../includes/db_connect.php';
require '../../includes/functions.php';

session_start();

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Default report period (last 30 days)
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch report data
$revenueData = $conn->query("
    SELECT DATE(pickup_date) AS date, SUM(total_price) AS revenue 
    FROM bookings 
    WHERE status = 'active' AND pickup_date BETWEEN '$startDate' AND '$endDate'
    GROUP BY DATE(pickup_date)
")->fetch_all(MYSQLI_ASSOC);

$fleetUsageData = $conn->query("
    SELECT f.make, f.model, COUNT(b.id) AS usage_count 
    FROM bookings b
    JOIN fleet f ON b.vehicle_id = f.id
    WHERE b.status = 'active' AND b.pickup_date BETWEEN '$startDate' AND '$endDate'
    GROUP BY b.vehicle_id
    ORDER BY usage_count DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raporty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../../views/shared/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Raporty</h1>
        <form method="GET" class="row g-3 mt-4">
            <div class="col-md-5">
                <label for="start_date" class="form-label">Data Początkowa</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
            </div>
            <div class="col-md-5">
                <label for="end_date" class="form-label">Data Końcowa</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Generuj</button>
            </div>
        </form>

        <div class="mt-5">
            <h3>Dochód</h3>
            <?php if (!empty($revenueData)): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Dochód (PLN)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($revenueData as $row): ?>
                            <tr>
                                <td><?php echo $row['date']; ?></td>
                                <td><?php echo number_format($row['revenue'], 2, ',', ' '); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Brak danych do wyświetlenia.</p>
            <?php endif; ?>
        </div>

        <div class="mt-5">
            <h3>Użycie Floty</h3>
            <?php if (!empty($fleetUsageData)): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Pojazd</th>
                            <th>Ilość Wynajmów</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fleetUsageData as $row): ?>
                            <tr>
                                <td><?php echo "{$row['make']} {$row['model']}"; ?></td>
                                <td><?php echo $row['usage_count']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Brak danych do wyświetlenia.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
