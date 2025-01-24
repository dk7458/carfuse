

<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';
require_once BASE_PATH . 'functions/global.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['vehicle_id'], $_POST['description'], $_POST['maintenance_date'])) {
        $vehicleId = intval($_POST['vehicle_id']);
        $description = htmlspecialchars(trim($_POST['description']));
        $maintenanceDate = $_POST['maintenance_date'];

        $stmt = $conn->prepare("INSERT INTO maintenance_logs (vehicle_id, description, maintenance_date) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $vehicleId, $description, $maintenanceDate);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Historia konserwacji została pomyślnie dodana.";
            redirect('/public/admin/dashboard.php?page=konserwacja');
        } else {
            $_SESSION['error_message'] = "Nie udało się dodać historii konserwacji.";
        }
    }
}

// Fetch vehicles for the dropdown
$vehicles = $conn->query("SELECT id, make, model FROM fleet ORDER BY make, model");
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie Konserwacją</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Theme -->
    <link rel="stylesheet" href="/theme.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Zarządzanie Konserwacją</h1>

        <?php include '../../views/shared/messages.php'; ?>

        <form method="POST" class="standard-form row g-3 mt-4">
            <div class="col-md-4">
                <label for="vehicle_id" class="form-label">Pojazd</label>
                <select id="vehicle_id" name="vehicle_id" class="form-select" required>
                    <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                        <option value="<?php echo $vehicle['id']; ?>"><?php echo "{$vehicle['make']} {$vehicle['model']}"; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="maintenance_date" class="form-label">Data Konserwacji</label>
                <input type="date" id="maintenance_date" name="maintenance_date" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="description" class="form-label">Opis Konserwacji</label>
                <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
            </div>
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-primary">Dodaj Konserwację</button>
            </div>
        </form>

        <h2 class="mt-5">Logi Konserwacji</h2>
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
