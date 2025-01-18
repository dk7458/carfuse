<?php
require __DIR__ . '../../includes/db_connect.php';
require __DIR__ . '../../includes/functions.php';

session_start();

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Fetch vehicles
$vehicles = $conn->query("SELECT * FROM fleet ORDER BY make, model");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $deleteId = intval($_POST['delete_id']);
        $conn->query("DELETE FROM fleet WHERE id = $deleteId");
        $_SESSION['success_message'] = "Pojazd został usunięty.";
        redirect('/public/admin/fleet.php');
    }

    if (isset($_POST['make'], $_POST['model'], $_POST['registration_number'])) {
        $make = htmlspecialchars(trim($_POST['make']));
        $model = htmlspecialchars(trim($_POST['model']));
        $registrationNumber = htmlspecialchars(trim($_POST['registration_number']));

        $stmt = $conn->prepare("INSERT INTO fleet (make, model, registration_number) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $make, $model, $registrationNumber);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Pojazd został dodany.";
            redirect('/public/admin/fleet.php');
        } else {
            $_SESSION['error_message'] = "Nie udało się dodać pojazdu.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie Flotą</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../../views/shared/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Zarządzanie Flotą</h1>

        <?php include '../../views/shared/messages.php'; ?>

        <form method="POST" class="row g-3 mt-4">
            <div class="col-md-4">
                <label for="make" class="form-label">Marka</label>
                <input type="text" id="make" name="make" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="model" class="form-label">Model</label>
                <input type="text" id="model" name="model" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="registration_number" class="form-label">Numer Rejestracyjny</label>
                <input type="text" id="registration_number" name="registration_number" class="form-control" required>
            </div>
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-primary">Dodaj Pojazd</button>
            </div>
        </form>

        <h2 class="mt-5">Lista Pojazdów</h2>
        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Marka</th>
                    <th>Model</th>
                    <th>Numer Rejestracyjny</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $vehicle['id']; ?></td>
                        <td><?php echo htmlspecialchars($vehicle['make']); ?></td>
                        <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                        <td><?php echo htmlspecialchars($vehicle['registration_number']); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="delete_id" value="<?php echo $vehicle['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Czy na pewno chcesz usunąć ten pojazd?');">Usuń</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
