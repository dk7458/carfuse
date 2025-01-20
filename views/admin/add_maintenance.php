<?php
require '/home/u122931475/domains/carfuse.pl/public_html/../includes/db_connect.php';
require '/home/u122931475/domains/carfuse.pl/public_html/../includes/functions.php';

session_start();

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

$vehicleId = intval($_GET['id']);

// Fetch vehicle details
$vehicle = $conn->query("SELECT * FROM fleet WHERE id = $vehicleId")->fetch_assoc();
if (!$vehicle) {
    die("Nie znaleziono pojazdu.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("Nieprawidłowy token CSRF.");
    }

    $description = htmlspecialchars($_POST['description']);
    $maintenanceDate = $_POST['maintenance_date'];

    $stmt = $conn->prepare("INSERT INTO maintenance_logs (vehicle_id, description, maintenance_date) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $vehicleId, $description, $maintenanceDate);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Historia konserwacji została pomyślnie dodana.";
        redirect("/views/admin/maintenance_logs.php?id=$vehicleId");
    } else {
        $_SESSION['error_message'] = "Nie udało się dodać historii konserwacji. Spróbuj ponownie później.";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj Konserwację</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/theme.css">

</head>
<body>
    <?php include '../../views/shared/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Dodaj Konserwację: <?php echo "{$vehicle['make']} {$vehicle['model']}"; ?></h1>

        <form method="POST" action="" class="standard-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="mb-3">
                <label for="maintenance_date" class="form-label">Data Konserwacji</label>
                <input type="date" id="maintenance_date" name="maintenance_date" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Opis Konserwacji</label>
                <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Dodaj Konserwację</button>
        </form>
    </div>
</body>
</html>
