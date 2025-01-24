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
    <?php include '../../views/shared/navbar_admin.php';
    require_once BASE_PATH . 'functions/global.php';


    enforceRole(['admin', 'super_admin']); 
     ?>

    <div class="container mt-5">
        <h1>Logi Systemowe</h1>

        <!-- Filters -->
        <form class="row g-3 my-3" method="POST" action="/public/api.php?endpoint=logs&action=add_log">
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
                <tbody>
                    <?php
                    // Fetch data using the centralized proxy
                    $filters = [
                        'search' => $_GET['search'] ?? '',
                        'startDate' => $_GET['start_date'] ?? '',
                        'endDate' => $_GET['end_date'] ?? ''
                    ];
                    $queryString = http_build_query($filters);
                    $response = file_get_contents(BASE_URL . "/public/api.php?endpoint=logs&action=fetch_logs&" . $queryString);
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
                    ?>
                </tbody>
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
