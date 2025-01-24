<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';

enforceRole(['admin', 'super_admin']);

// Replace direct DB queries with API calls
$logsResponse = file_get_contents(BASE_URL . "/public/api.php?endpoint=maintenance&action=fetch_logs");
$vehiclesResponse = file_get_contents(BASE_URL . "/public/api.php?endpoint=fleet&action=fetch_vehicles");

$logsData = json_decode($logsResponse, true);
$vehiclesData = json_decode($vehiclesResponse, true);

$logs = $logsData['success'] ? $logsData['logs'] : [];
$vehicles = $vehiclesData['success'] ? $vehiclesData['vehicles'] : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = http_build_query([
        'vehicle_id' => $_POST['vehicle_id'],
        'description' => $_POST['description'],
        'maintenance_date' => $_POST['maintenance_date']
    ]);
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postData
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents(BASE_URL . "/public/api.php?endpoint=maintenance&action=add_log", false, $context);
    $result = json_decode($response, true);
    
    if ($result['success']) {
        $_SESSION['success_message'] = "Historia konserwacji została pomyślnie dodana.";
        redirect('/public/admin/dashboard.php?page=konserwacja');
    } else {
        $_SESSION['error_message'] = $result['error'] ?? "Nie udało się dodać historii konserwacji.";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie Konserwacją</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Theme -->
    <link rel="stylesheet" href="/theme.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Zarządzanie Konserwacją</h1>

        <?php include '../../views/shared/messages.php'; ?>

        <form method="POST" class="standard-form row g-3 mt-4">
            <div class="col-md-4">
                <label for="vehicle_id" class="form-label">Pojazd</label>
                <select id="vehicle_id" name="vehicle_id" class="form-select" required>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?php echo $vehicle['id']; ?>"><?php echo "{$vehicle['make']} {$vehicle['model']}"; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="maintenance_date" class="form-label">Data Konserwacji</label>
                <input type="date" id="maintenance_date" name="maintenance_date" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="description" class="form-label">Opis Konserwacji</label>
                <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
            </div>
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-primary">Dodaj Konserwację</button>
            </div>
        </form>

        <h2 class="mt-5">Logi Konserwacji</h2>
        <?php if (count($logs) > 0): ?>
            <table class="table table-bordered mt-4">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pojazd</th>
                        <th>Opis</th>
                        <th>Data Konserwacji</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td><?php echo "{$log['make']} {$log['model']}"; ?></td>
                            <td><?php echo htmlspecialchars($log['description']); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($log['maintenance_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info text-center mt-4">
                Brak logów konserwacji do wyświetlenia.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
