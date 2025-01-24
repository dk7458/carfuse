<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /views/admin/maintenance_add.php
require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'functions/global.php';


enforceRole(['admin', 'super_admin']); 

// Fetch data using the centralized proxy
$filters = [
    'search' => $_GET['search'] ?? '',
    'startDate' => $_GET['start_date'] ?? '',
    'endDate' => $_GET['end_date'] ?? ''
];
$queryString = http_build_query($filters);
$response = file_get_contents(BASE_URL . "/public/api.php?endpoint=maintenance&action=fetch_maintenance&" . $queryString);
$data = json_decode($response, true);

if ($data['success']) {
    $maintenance = $data['maintenance'];
    foreach ($maintenance as $item) {
        echo "<tr>
            <td>{$item['make']}</td>
            <td>{$item['model']}</td>
            <td>{$item['description']}</td>
            <td>{$item['maintenance_date']}</td>
        </tr>";
    }
}
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
        <form method="POST" action="/public/api.php?endpoint=maintenance&action=add_maintenance">
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
