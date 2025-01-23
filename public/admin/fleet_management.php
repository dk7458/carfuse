

$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';

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
        redirect('/public/admin/dashboard.php?page=flota');
    }

    if (isset($_POST['make'], $_POST['model'], $_POST['registration_number'])) {
        $make = htmlspecialchars(trim($_POST['make']));
        $model = htmlspecialchars(trim($_POST['model']));
        $registrationNumber = htmlspecialchars(trim($_POST['registration_number']));

        $stmt = $conn->prepare("INSERT INTO fleet (make, model, registration_number) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $make, $model, $registrationNumber);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Pojazd został dodany.";
            redirect('/public/admin/dashboard.php?page=flota');
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
    <!-- Custom Theme -->
    <link rel="stylesheet" href="/theme.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-9 offset-md-3">
                <div id="fleet" class="collapse show">
                    <h1 class="text-center">Zarządzanie Flotą</h1>

                    <?php include '../../views/shared/messages.php'; ?>

                    <form method="POST" class="standard-form row g-3 mt-4">
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
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.list-group-item-action').on('click', function() {
                var target = $(this).attr('href');
                $('.collapse').not(target).collapse('hide');
                $(target).collapse('show');
            });
        });
    </script>
</body>
</html>
