<?php
// File Path: /views/admin/notification_manager.php
require_once __DIR__ . '/../../includes/session_middleware.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/notification_helpers.php';
require_once __DIR__ . '/../includes/functions.php';

enforceRole(['admin', 'super_admin']); 

// Fetch filters
$type = $_GET['type'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Fetch notifications
$notifications = fetchNotifications($conn, $type, $startDate, $endDate, $search, $offset, $itemsPerPage);
$totalNotifications = countNotifications($conn, $type, $startDate, $endDate, $search);
$totalPages = ceil($totalNotifications / $itemsPerPage);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Menadżer Powiadomień</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Link the notifications.css file -->
    <link rel="stylesheet" href="/assets/css/notifications.css">

</head>
<body>
    <?php include '../shared/navbar_admin.php'; ?>

    <div class="container">
        <h1 class="mt-5">Menadżer Powiadomień</h1>

        <!-- Filters -->
        <form method="GET" class="row g-3 mt-3">
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">Wszystkie</option>
                    <option value="email" <?= $type === 'email' ? 'selected' : '' ?>>E-mail</option>
                    <option value="sms" <?= $type === 'sms' ? 'selected' : '' ?>>SMS</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Szukaj po odbiorcy" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Filtruj</button>
            </div>
        </form>

        <!-- Notifications Table -->
        <table class="table mt-4 table-bordered">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Typ</th>
                    <th>Odbiorca</th>
                    <th>Treść</th>
                    <th>Status</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($notifications->num_rows > 0): ?>
                    <?php while ($notification = $notifications->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($notification['sent_at']) ?></td>
                            <td><?= htmlspecialchars($notification['type']) ?></td>
                            <td><?= htmlspecialchars($notification['recipient']) ?></td>
                            <td><?= htmlspecialchars($notification['message']) ?></td>
                            <td><?= htmlspecialchars($notification['status']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-info resend-notification" data-id="<?= $notification['id'] ?>">Wyślij Ponownie</button>
                                <button class="btn btn-sm btn-danger delete-notification" data-id="<?= $notification['id'] ?>">Usuń</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Brak powiadomień.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page === 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?type=<?= htmlspecialchars($type) ?>&start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>&search=<?= htmlspecialchars($search) ?>&page=<?= $page - 1 ?>">Poprzednia</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?type=<?= htmlspecialchars($type) ?>&start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>&search=<?= htmlspecialchars($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page === $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?type=<?= htmlspecialchars($type) ?>&start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>&search=<?= htmlspecialchars($search) ?>&page=<?= $page + 1 ?>">Następna</a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Include the notification_manager.js file -->
    <script src="/assets/js/notification_manager.js"></script>
</body>
</html>
