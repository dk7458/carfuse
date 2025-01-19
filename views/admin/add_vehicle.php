<?php
require '../../includes/db_connect.php';
require '../../includes/functions.php';

session_start();

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("Nieprawidłowy token CSRF.");
    }

    $make = htmlspecialchars($_POST['make']);
    $model = htmlspecialchars($_POST['model']);
    $registrationNumber = htmlspecialchars($_POST['registration_number']);
    $availability = isset($_POST['availability']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO fleet (make, model, registration_number, availability, created_at) 
                            VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssi", $make, $model, $registrationNumber, $availability);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Pojazd został pomyślnie dodany.";
        redirect('/views/admin/fleet.php');
    } else {
        $_SESSION['error_message'] = "Nie udało się dodać pojazdu. Spróbuj ponownie później.";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj Pojazd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/theme.css" rel="stylesheet">

</head>
<body>
    <?php include '../../views/shared/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Dodaj Pojazd</h1>

        <form method="POST" action="" class="standard-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="mb-3">
                <label for="make" class="form-label">Marka</label>
                <input type="text" id="make" name="make" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="model" class="form-label">Model</label>
                <input type="text" id="model" name="model" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="registration_number" class="form-label">Numer Rejestracyjny</label>
                <input type="text" id="registration_number" name="registration_number" class="form-control" required>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" id="availability" name="availability" class="form-check-input">
                <label for="availability" class="form-check-label">Dostępny</label>
            </div>

            <button type="submit" class="btn btn-primary">Dodaj Pojazd</button>
        </form>
    </div>
</body>
</html>
