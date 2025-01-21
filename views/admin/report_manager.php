<?php
// File Path: /views/admin/report_manager.php
require_once __DIR__ . '/../../includes/session_middleware.php';
require_once __DIR__ . '/../../controllers/report_ctrl.php';

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'super_admin' && $_SESSION['user_role'] !== 'admin')) {
    redirect('/public/login.php');
}

$category = $_GET['category'] ?? 'bookings';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Fetch data from backend
$data = fetchReportData($dateFrom, $dateTo, $category);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Menadżer Raportów</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/report_manager.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include '../shared/navbar_admin.php'; ?>

    <div class="container">
        <h1 class="mt-5">Menadżer Raportów</h1>

        <!-- Filters -->
        <form method="GET" class="row g-3 mt-3">
            <div class="col-md-4">
                <label for="category" class="form-label">Kategoria:</label>
                <select name="category" id="category" class="form-select">
                    <option value="bookings" <?= $category === 'bookings' ? 'selected' : '' ?>>Rezerwacje</option>
                    <option value="revenue" <?= $category === 'revenue' ? 'selected' : '' ?>>Przychody</option>
                    <option value="users" <?= $category === 'users' ? 'selected' : '' ?>>Użytkownicy</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date_from" class="form-label">Data od:</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
            </div>
            <div class="col-md-3">
                <label for="date_to" class="form-label">Data do:</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Generuj</button>
            </div>
        </form>

        <!-- Report Table -->
        <div class="mt-4">
            <h3>Raport: <?= ucfirst($category) ?></h3>
            <p>Okres: <?= htmlspecialchars($dateFrom) ?> - <?= htmlspecialchars($dateTo) ?></p>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <?php foreach (array_keys($data[0] ?? []) as $header): ?>
                            <th><?= htmlspecialchars(ucfirst($header)) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?= htmlspecialchars($cell) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Chart -->
        <div class="mt-5">
            <canvas id="reportChart" width="400" height="200"></canvas>
        </div>

        <!-- Export Options -->
        <div class="mt-3">
            <a href="/controllers/report_ctrl.php?action=export_csv&category=<?= htmlspecialchars($category) ?>&date_from=<?= htmlspecialchars($dateFrom) ?>&date_to=<?= htmlspecialchars($dateTo) ?>" class="btn btn-success">Eksportuj jako CSV</a>
            <a href="/controllers/report_ctrl.php?action=export_pdf&category=<?= htmlspecialchars($category) ?>&date_from=<?= htmlspecialchars($dateFrom) ?>&date_to=<?= htmlspecialchars($dateTo) ?>" class="btn btn-secondary">Eksportuj jako PDF</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/assets/js/report_manager.js"></script>
</body>
</html>
