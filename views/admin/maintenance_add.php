<?php
// File Path: /views/admin/maintenance_add.php
require_once __DIR__ . '/../../includes/session_middleware.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../includes/functions.php';

enforceRole(['admin', 'super_admin']); 
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Dodaj Przegląd</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container">
        <h1 class="mt-5">Dodaj Przegląd</h1>
        <form method="POST" action="/controllers/maintenance_ctrl.php">
            <input type="hidden" name="action" value="add_maintenance">
            <div class="mb-3">
                <label for="vehicle_id" class="form-label">Pojazd</label>
                <select name="vehicle_id" id="vehicle_id" class="form-select" required>
                    <?php
                    $vehicles = $conn->query("SELECT id, make, model, registration_number FROM fleet");
                    while ($vehicle = $vehicles->fetch_assoc()):
                    ?>
                        <option value="<?= $vehicle['id'] ?>"><?= $vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['registration_number'] . ')' ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="maintenance_date" class="form-label">Data Przeglądu</label>
                <input type="date" id="maintenance_date" name="maintenance_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Opis</label>
                <textarea id="description" name="description" class="form-control" rows="3" required></textarea>
            </div>
            <div class="mb-3">
                <label for="cost" class="form-label">Koszt (PLN)</label>
                <input type="number" id="cost" name="cost" class="form-control" step="0.01" required>
            </div>
            <button type="submit" class="btn btn-primary">Dodaj Przegląd</button>
        </form>
    </div>
</body>
</html>
