<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once BASE_PATH . 'functions/global.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';


// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Fetch maintenance logs
$logs = $conn->query("
    SELECT ml.id, f.make, f.model, ml.description, ml.maintenance_date 
    FROM maintenance_logs ml
    JOIN fleet f ON ml.vehicle_id = f.id
    ORDER BY ml.maintenance_date DESC
");
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logi Konserwacji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../../views/shared/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Logi Konserwacji</h1>

        <?php if ($logs->num_rows > 0): ?>
            <table class="table table-bordered mt-4">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pojazd</th>
                        <th>Opis</th>
                        <th>Data Konserwacji</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($log = $logs->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td><?php echo "{$log['make']} {$log['model']}"; ?></td>
                            <td><?php echo htmlspecialchars($log['description']); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($log['maintenance_date'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info text-center mt-4">
                Brak logów konserwacji do wyświetlenia.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
