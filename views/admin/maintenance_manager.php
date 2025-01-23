<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzanie Przeglądami</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../../views/shared/navbar_admin.php'; 
    require_once BASE_PATH . 'includes/functions.php';


    enforceRole(['admin', 'super_admin']); 
    ?>

    <div class="container">
        <h1 class="mt-5">Zarządzanie Przeglądami</h1>

        <!-- Filters -->
        <form method="GET" class="row g-3 mt-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Szukaj pojazdu lub opisu przeglądu" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="date_range" class="form-select">
                    <option value="">Wybierz okres</option>
                    <option value="last_week">Ostatni tydzień</option>
                    <option value="last_month">Ostatni miesiąc</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
            </div>
            <div class="col-md-3">
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filtruj</button>
            </div>
        </form>

        <!-- Add Maintenance Log Button -->
        <div class="mt-4 d-flex justify-content-between align-items-center">
            <a href="maintenance_add.php" class="btn btn-success">Dodaj Przegląd</a>
            <div class="d-flex gap-2">
                <button id="exportCsv" class="btn btn-outline-secondary">Eksportuj CSV</button>
                <button id="exportPdf" class="btn btn-outline-secondary">Eksportuj PDF</button>
            </div>
        </div>

        <!-- Summary and Visualization -->
        <div class="mt-5">
            <h3>Podsumowanie Przeglądów</h3>
            <div class="row">
                <div class="col-md-6">
                    <canvas id="costChart"></canvas>
                </div>
                <div class="col-md-6">
                    <canvas id="frequencyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Maintenance Logs Table -->
        <div class="mt-4 table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAll">
                        </th>
                        <th>Pojazd</th>
                        <th>Numer Rejestracyjny</th>
                        <th>Data Przeglądu</th>
                        <th>Opis</th>
                        <th>Koszt (PLN)</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs->num_rows > 0): ?>
                        <?php while ($log = $logs->fetch_assoc()): ?>
                            <tr class="<?= (strtotime($log['maintenance_date']) < time()) ? 'table-danger' : '' ?>">
                                <td>
                                    <input type="checkbox" class="selectLog" value="<?= $log['id'] ?>">
                                </td>
                                <td><?= htmlspecialchars($log['make'] . ' ' . $log['model']) ?></td>
                                <td><?= htmlspecialchars($log['registration_number']) ?></td>
                                <td><?= htmlspecialchars($log['maintenance_date']) ?></td>
                                <td><?= htmlspecialchars($log['description']) ?></td>
                                <td><?= number_format($log['cost'], 2, ',', ' ') ?></td>
                                <td>
                                    <a href="maintenance_edit.php?id=<?= $log['id'] ?>" class="btn btn-sm btn-warning">Edytuj</a>
                                    <button class="btn btn-sm btn-danger delete-log" data-id="<?= $log['id'] ?>">Usuń</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">Brak logów przeglądów w bazie danych.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page === 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?search=<?= htmlspecialchars($search) ?>&start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>&page=<?= $page - 1 ?>">Poprzednia</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?search=<?= htmlspecialchars($search) ?>&start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page === $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?search=<?= htmlspecialchars($search) ?>&start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>&page=<?= $page + 1 ?>">Następna</a>
                </li>
            </ul>
        </nav>
    </div>

    <script src="/assets/js/maintenance.js"></script>
</body>
</html>
