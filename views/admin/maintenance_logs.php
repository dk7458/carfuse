<?php
require '../../includes/db_connect.php';
require '../../includes/functions.php';

session_start();

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

$vehicleId = intval($_GET['id']);

// Fetch vehicle details
$vehicle = $conn->query("SELECT * FROM fleet WHERE id = $vehicleId")->fetch_assoc();
if (!$vehicle) {
    die("Nie znaleziono pojazdu.");
}

// Fetch maintenance logs
$logs = $conn->query("SELECT * FROM maintenance_logs WHERE vehicle_id = $vehicleId ORDER BY maintenance_date DESC");
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Konserwacji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../../views/shared/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Historia Konserwacji: <?php echo "{$vehicle['make']} {$vehicle['model']}"; ?></h1>

        <div class="text-end mb-4">
            <a href="/views/admin/add_maintenance.php?id=<?php echo $vehicleId; ?>" class="btn btn-primary">Dodaj Konserwację</a>
        </div>

        <?php if ($logs->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Data Konserwacji</th>
                        <th>Opis</th>
                        <th>Dodano</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($log = $logs->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $log['maintenance_date']; ?></td>
                            <td><?php echo htmlspecialchars($log['description']); ?></td>
                            <td><?php echo date('d-m-Y H:i:s', strtotime($log['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info text-center mt-4">
                Brak historii konserwacji dla tego pojazdu.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
