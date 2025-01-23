<?php
// File Path: /views/admin/report_manager.php
// Description: Allows admins to view and generate reports dynamically with export options.
// Changelog:
// - Integrated dynamic chart rendering with Chart.js.
// - Improved error handling for empty data.
// - Enhanced export functionality with dynamic parameters.
// - Added support for weekly and comparative reporting.

require_once __DIR__ . '/../../includes/session_middleware.php';
require_once __DIR__ . '/../../controllers/report_ctrl.php';
require_once __DIR__ . '/../../includes/functions.php';

enforceRole(['admin', 'super_admin']); 

// Default filter values
$category = $_GET['category'] ?? 'bookings';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$reportType = $_GET['report_type'] ?? 'daily';

// Fetch data for the report
$data = [];
try {
    if ($reportType === 'comparative_weekly') {
        $data = fetchComparativeWeeklyReportData($conn, $category);
    } else {
        $data = fetchReportData($conn, $dateFrom, $dateTo, $category);
    }
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
}
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
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container">
        <h1 class="mt-5">Menadżer Raportów</h1>

        <!-- Filters -->
        <form method="GET" class="row g-3 mt-3">
            <div class="col-md-3">
                <label for="category" class="form-label">Kategoria:</label>
                <select name="category" id="category" class="form-select">
                    <option value="bookings" <?= $category === 'bookings' ? 'selected' : '' ?>>Rezerwacje</option>
                    <option value="revenue" <?= $category === 'revenue' ? 'selected' : '' ?>>Przychody</option>
                    <option value="users" <?= $category === 'users' ? 'selected' : '' ?>>Użytkownicy</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="report_type" class="form-label">Typ Raportu:</label>
                <select name="report_type" id="report_type" class="form-select">
                    <option value="daily" <?= $reportType === 'daily' ? 'selected' : '' ?>>Dzienny</option>
                    <option value="comparative_weekly" <?= $reportType === 'comparative_weekly' ? 'selected' : '' ?>>Porównawczy Tygodniowy</option>
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

        <!-- Error Handling -->
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger mt-4"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <!-- Report Table -->
        <?php if (!empty($data)): ?>
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
        <?php else: ?>
            <div class="alert alert-info mt-4">Brak danych do wyświetlenia dla wybranych filtrów.</div>
        <?php endif; ?>
    </div>

    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/assets/js/report_manager.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const chartData = <?= json_encode($data) ?>;
            const ctx = document.getElementById('reportChart').getContext('2d');
            if (chartData.length > 0) {
                const labels = chartData.map(item => item.date || item.week);
                const values = chartData.map(item => Object.values(item)[1]);

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: '<?= ucfirst($category) ?>',
                            data: values,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1,
                        }],
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false,
                            },
                        },
                    },
                });
            }
        });
    </script>
</body>
</html>
