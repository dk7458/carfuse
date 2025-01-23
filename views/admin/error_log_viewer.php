$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/functions.php';


enforceRole(['admin', 'super_admin']); 

if ($_SESSION['user_role'] !== 'super_admin' && $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$itemsPerPage = 20;
$offset = ($page - 1) * $itemsPerPage;

// Fetch logs with optional search and pagination
$query = "SELECT id, timestamp, user_id, action, details FROM logs WHERE 1";
$params = [];
$types = "";

// Add search filter
if (!empty($search)) {
    $query .= " AND (action LIKE ? OR details LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

// Add pagination
$query .= " ORDER BY timestamp DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $itemsPerPage;
$types .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$logs = $stmt->get_result();
$stmt->close();

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
                <?php if ($logs->num_rows > 0): ?>
                    <?php while ($log = $logs->fetch_assoc()): ?>
                        <tr>
                            <td><?= $log['id'] ?></td>
                            <td><?= htmlspecialchars($log['timestamp']) ?></td>
                            <td><?= $log['user_id'] ?: 'System' ?></td>
                            <td><?= htmlspecialchars($log['action']) ?></td>
                            <td><?= htmlspecialchars($log['details']) ?></td>
                        </tr>
                    <?php endwhile; ?>
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
