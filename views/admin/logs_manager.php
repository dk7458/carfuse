<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Logi Systemowe</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../shared/navbar_admin.php';
    require_once __DIR__ . '/../includes/functions.php';

    enforceRole(['admin', 'super_admin']); 
     ?>

    <div class="container mt-5">
        <h1>Logi Systemowe</h1>

        <!-- Filters -->
        <form class="row g-3 my-3">
            <div class="col-md-3">
                <select name="log_type" class="form-select">
                    <option value="">Wszystkie typy</option>
                    <option value="info">Info</option>
                    <option value="error">Błąd</option>
                    <option value="warning">Ostrzeżenie</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="start_date" class="form-control">
            </div>
            <div class="col-md-3">
                <input type="date" name="end_date" class="form-control">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Filtruj</button>
            </div>
        </form>

        <!-- Clear Logs Button -->
        <div class="my-3">
            <button id="clearLogs" class="btn btn-danger">Wyczyść Stare Logi</button>
        </div>

        <!-- Logs Table -->
        <div id="logsTableContainer" class="table-responsive mt-4">
            <table class="table table-bordered" id="logsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Użytkownik</th>
                        <th>Akcja</th>
                        <th>Typ</th>
                        <th>Szczegóły</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <!-- Chart -->
        <div class="mt-5">
            <h3>Wizualizacja Logów</h3>
            <canvas id="logsChart" width="400" height="200"></canvas>
        </div>
    </div>

    <script src="/assets/js/logs_manager.js"></script>
</body>
</html>
