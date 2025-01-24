<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'functions/global.php';


enforceRole(['admin', 'super_admin']); 

if ($_SESSION['user_role'] !== 'super_admin' && $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$itemsPerPage = 20;
$offset = ($page - 1) * $itemsPerPage;

// Fetch data using the centralized proxy
$filters = [
    'search' => $_GET['search'] ?? '',
    'startDate' => $_GET['start_date'] ?? '',
    'endDate' => $_GET['end_date'] ?? ''
];
$queryString = http_build_query($filters);
$response = file_get_contents(BASE_URL . "/public/api.php?endpoint=logs&action=fetch_errors&" . $queryString);
$data = json_decode($response, true);

if ($data['success']) {
    $logs = $data['logs'];
    foreach ($logs as $log) {
        echo "<tr>
            <td>{$log['timestamp']}</td>
            <td>{$log['message']}</td>
            <td>{$log['file']}</td>
            <td>{$log['line']}</td>
        </tr>";
    }
}

// Count total logs for pagination
$countQuery = "SELECT COUNT(*) FROM logs";
$totalLogs = $conn->query($countQuery)->fetch_row()[0];
$totalPages = ceil($totalLogs / $itemsPerPage);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Podgląd Błędów</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
    <script src="/assets/js/error_log_viewer.js"></script>
</head>
<body>
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container">
        <h1 class="mt-5">Podgląd Błędów</h1>

        <!-- Filters and Export -->
        <form method="GET" class="row g-3 mt-3">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="Szukaj po akcji lub szczegółach"
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filtruj</button>
            </div>
            <div class="col-md-2">
                <button id="exportCsv" class="btn btn-outline-success w-100">Eksportuj CSV</button>
            </div>
        </form>

        <!-- Logs Table -->
        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data i Czas</th>
                    <th>ID Użytkownika</th>
                    <th>Akcja</th>
                    <th>Szczegóły</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data['success'] && count($logs) > 0): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= $log['id'] ?></td>
                            <td><?= htmlspecialchars($log['timestamp']) ?></td>
                            <td><?= $log['user_id'] ?: 'System' ?></td>
                            <td><?= htmlspecialchars($log['action']) ?></td>
                            <td><?= htmlspecialchars($log['details']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Brak logów w systemie.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page === 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?search=<?= htmlspecialchars($search) ?>&page=<?= $page - 1 ?>">Poprzednia</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?search=<?= htmlspecialchars($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page === $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?search=<?= htmlspecialchars($search) ?>&page=<?= $page + 1 ?>">Następna</a>
                </li>
            </ul>
        </nav>
    </div>
</body>
</html>
