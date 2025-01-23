<?php
// File Path: /views/admin/maintenance_edit.php
require_once BASE_PATH . '../includes/session_middleware.php';

require_once BASE_PATH . '../includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';


enforceRole(['admin', 'super_admin']); 

$logId = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM maintenance_logs WHERE id = ?");
$stmt->bind_param("i", $logId);
$stmt->execute();
$log = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$log) {
    die("Nie znaleziono logu przeglądu.");
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edytuj Przegląd</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container">
        <h1 class="mt-5">Edytuj Przegląd</h1>
        <form method="POST" action="/controllers/maintenance_ctrl.php">
            <input type="hidden" name="action" value="update_maintenance">
            <input type="hidden" name="id" value="<?= $log['id'] ?>">
            <div class="mb-3">
                <label for="vehicle_id" class="form-label">Pojazd</label>
                <select name="vehicle_id" id="vehicle_id" class="form-select" disabled>
                    <?php
                    $vehicles = $conn->query("SELECT id, make, model, registration_number FROM fleet");
                    while ($vehicle = $vehicles->fetch_assoc()):
                    ?>
                        <option value="<?= $vehicle['id'] ?>" <?= $vehicle['id'] === $log['vehicle_id'] ? 'selected' : '' ?>>
                            <?= $vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['registration_number'] . ')' ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="maintenance_date" class="form-label">Data Przeglądu</label>
                <input type="date" id="maintenance_date" name="maintenance_date" class="form-control" value="<?= htmlspecialchars($log['maintenance_date']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Opis</label>
                <textarea id="description" name="description" class="form-control" rows="3" required><?= htmlspecialchars($log['description']) ?></textarea>
            </div>
            <div class="mb-3">
                <label for="cost" class="form-label">Koszt (PLN)</label>
                <input type="number" id="cost" name="cost" class="form-control" step="0.01" value="<?= htmlspecialchars($log['cost']) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Zapisz Zmiany</button>
        </form>
    </div>
</body>
</html>
