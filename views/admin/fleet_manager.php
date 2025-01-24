<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /views/admin/fleet_manager.php
require_once BASE_PATH . 'includes/session_middleware.php';
require_once BASE_PATH . 'includes/db_connect.php';
require_once BASE_PATH . 'functions/global.php';
require_once BASE_PATH . 'functions/vehicle.php';

enforceRole(['admin', 'super_admin']); 

$search = $_GET['search'] ?? '';
$availability = $_GET['availability'] ?? '';
$maintenance = $_GET['maintenance'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Fetch data using the centralized proxy
$filters = [
    'search' => $_GET['search'] ?? '',
    'startDate' => $_GET['start_date'] ?? '',
    'endDate' => $_GET['end_date'] ?? ''
];
$queryString = http_build_query($filters);
$response = file_get_contents(BASE_URL . "/public/api.php?endpoint=fleet&action=fetch_vehicles&" . $queryString);
$data = json_decode($response, true);

if ($data['success']) {
    $vehicles = $data['vehicles'];
    foreach ($vehicles as $vehicle) {
        echo "<tr>
            <td>{$vehicle['make']}</td>
            <td>{$vehicle['model']}</td>
            <td>{$vehicle['year']}</td>
            <td>{$vehicle['status']}</td>
        </tr>";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzanie Flotą</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container">
        <h1 class="mt-5">Zarządzanie Flotą</h1>

        <!-- Filters -->
        <form method="GET" class="row g-3 mt-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Szukaj po marce, modelu lub numerze rejestracyjnym" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <select name="availability" class="form-select">
                    <option value="">Wszystkie</option>
                    <option value="1" <?= $availability === '1' ? 'selected' : '' ?>>Dostępne</option>
                    <option value="0" <?= $availability === '0' ? 'selected' : '' ?>>Niedostępne</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="maintenance" class="form-select">
                    <option value="">Wszystkie</option>
                    <option value="overdue" <?= $maintenance === 'overdue' ? 'selected' : '' ?>>Przegląd Wymagany</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="col-md-2">
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100">Filtruj</button>
            </div>
        </form>

        <!-- Add Vehicle Button -->
        <div class="mt-4 d-flex justify-content-between align-items-center">
            <a href="fleet_add.php" class="btn btn-success">Dodaj Nowy Pojazd</a>
            <button id="bulkDelete" class="btn btn-danger">Usuń Wybrane</button>
        </div>

        <!-- Summary and Visualization -->
        <div class="mt-5">
            <h3>Podsumowanie Floty</h3>
            <div class="row">
                <div class="col-md-6">
                    <canvas id="availabilityChart"></canvas>
                </div>
                <div class="col-md-6">
                    <canvas id="maintenanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Vehicle Table -->
        <div class="mt-4 table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Marka</th>
                        <th>Model</th>
                        <th>Numer Rejestracyjny</th>
                        <th>Dostępność</th>
                        <th>Data Ostatniego Przeglądu</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($vehicles->num_rows > 0): ?>
                        <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                            <tr>
                                <td><input type="checkbox" class="vehicle-checkbox" value="<?= $vehicle['id'] ?>"></td>
                                <td><?= htmlspecialchars($vehicle['make']) ?></td>
                                <td><?= htmlspecialchars($vehicle['model']) ?></td>
                                <td><?= htmlspecialchars($vehicle['registration_number']) ?></td>
                                <td><?= $vehicle['availability'] ? 'Dostępny' : 'Niedostępny' ?></td>
                                <td><?= htmlspecialchars($vehicle['last_maintenance_date'] ?? 'Brak danych') ?></td>
                                <td>
                                    <a href="fleet_edit.php?id=<?= $vehicle['id'] ?>" class="btn btn-sm btn-warning">Edytuj</a>
                                    <button class="btn btn-sm btn-danger delete-vehicle" data-id="<?= $vehicle['id'] ?>">Usuń</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Brak pojazdów w bazie danych.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page === 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?search=<?= htmlspecialchars($search) ?>&availability=<?= htmlspecialchars($availability) ?>&maintenance=<?= htmlspecialchars($maintenance) ?>&page=<?= $page - 1 ?>">Poprzednia</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?search=<?= htmlspecialchars($search) ?>&availability=<?= htmlspecialchars($availability) ?>&maintenance=<?= htmlspecialchars($maintenance) ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page === $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?search=<?= htmlspecialchars($search) ?>&availability=<?= htmlspecialchars($availability) ?>&maintenance=<?= htmlspecialchars($maintenance) ?>&page=<?= $page + 1 ?>">Następna</a>
                </li>
            </ul>
        </nav>
    </div>

    <script src="/assets/js/fleet.js"></script>
</body>
</html>
