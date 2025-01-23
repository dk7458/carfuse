$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';


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

    $make = htmlspecialchars($_POST['make']);
    $model = htmlspecialchars($_POST['model']);
    $registrationNumber = htmlspecialchars($_POST['registration_number']);
    $availability = isset($_POST['availability']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE fleet SET make = ?, model = ?, registration_number = ?, availability = ? WHERE id = ?");
    $stmt->bind_param("sssii", $make, $model, $registrationNumber, $availability, $vehicleId);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Pojazd został pomyślnie zaktualizowany.";
        redirect('/views/admin/fleet.php');
    } else {
        $_SESSION['error_message'] = "Nie udało się zaktualizować pojazdu. Spróbuj ponownie później.";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edytuj Pojazd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/theme.css">
</head>
<body>
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Edytuj Pojazd</h1>

        <form method="POST" action="" class="standard-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="mb-3">
                <label for="make" class="form-label">Marka</label>
                <input type="text" id="make" name="make" class="form-control" value="<?php echo htmlspecialchars($vehicle['make']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="model" class="form-label">Model</label>
                <input type="text" id="model" name="model" class="form-control" value="<?php echo htmlspecialchars($vehicle['model']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="registration_number" class="form-label">Numer Rejestracyjny</label>
                <input type="text" id="registration_number" name="registration_number" class="form-control" value="<?php echo $vehicle['registration_number']; ?>" required>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" id="availability" name="availability" class="form-check-input" <?php echo $vehicle['availability'] ? 'checked' : ''; ?>>
                <label for="availability" class="form-check-label">Dostępny</label>
            </div>

            <button type="submit" class="btn btn-primary">Zaktualizuj Pojazd</button>
        </form>
    </div>
</body>
</html>
