<?php
// File Path: /views/admin/user_manager.php
require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/user_helpers.php';


if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Filters
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Fetch users with filters
$users = fetchUsers($conn, $search, $role, $status, $offset, $itemsPerPage);
$totalUsers = countUsers($conn, $search, $role, $status);
$totalPages = ceil($totalUsers / $itemsPerPage);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Menadżer Użytkowników</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/user_manager.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container">
        <h1 class="mt-5">Menadżer Użytkowników</h1>

        <!-- Filters -->
        <form method="GET" class="row g-3 mt-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Szukaj po imieniu, nazwisku lub e-mailu" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">Wszystkie role</option>
                    <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>Użytkownik</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrator</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Wszystkie statusy</option>
                    <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Aktywny</option>
                    <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Nieaktywny</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Filtruj</button>
            </div>
        </form>

        <!-- Bulk Actions -->
        <form id="bulk-actions-form" class="mt-3">
            <div class="row">
                <div class="col-md-3">
                    <select id="bulk-action" class="form-select">
                        <option value="">Akcja zbiorowa</option>
                        <option value="delete">Usuń</option>
                        <option value="change_role_user">Zmień na Użytkownik</option>
                        <option value="change_role_admin">Zmień na Administrator</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-secondary w-100" id="apply-bulk-action">Zastosuj</button>
                </div>
            </div>
        </form>

        <!-- Add User Button -->
        <div class="mt-4">
            <a href="user_add.php" class="btn btn-success">Dodaj Nowego Użytkownika</a>
            <a href="/controllers/export_users.php" class="btn btn-secondary">Eksportuj dane użytkowników</a>
        </div>

        <!-- User Table -->
        <table class="table mt-4 table-bordered">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all"></th>
                    <th>ID</th>
                    <th>Imię i Nazwisko</th>
                    <th>E-mail</th>
                    <th>Rola</th>
                    <th>Status</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users->num_rows > 0): ?>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><input type="checkbox" class="user-checkbox" value="<?= $user['id'] ?>"></td>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['name'] . ' ' . $user['surname']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <select class="form-select user-role" data-id="<?= $user['id'] ?>">
                                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Użytkownik</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                                </select>
                            </td>
                            <td>
                                <button class="btn btn-sm toggle-status" data-id="<?= $user['id'] ?>" data-status="<?= $user['status'] ?>">
                                    <?= $user['status'] ? 'Aktywny' : 'Nieaktywny' ?>
                                </button>
                            </td>
                            <td>
                                <a href="user_edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-warning">Edytuj</a>
                                <button class="btn btn-sm btn-danger delete-user" data-id="<?= $user['id'] ?>">Usuń</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">Brak użytkowników w bazie danych.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page === 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?search=<?= htmlspecialchars($search) ?>&role=<?= htmlspecialchars($role) ?>&status=<?= htmlspecialchars($status) ?>&page=<?= $page - 1 ?>">Poprzednia</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?search=<?= htmlspecialchars($search) ?>&role=<?= htmlspecialchars($role) ?>&status=<?= htmlspecialchars($status) ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page === $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?search=<?= htmlspecialchars($search) ?>&role=<?= htmlspecialchars($role) ?>&status=<?= htmlspecialchars($status) ?>&page=<?= $page + 1 ?>">Następna</a>
                </li>
            </ul>
        </nav>
    </div>

    <script src="/assets/js/user_manager.js"></script>
</body>
</html>
