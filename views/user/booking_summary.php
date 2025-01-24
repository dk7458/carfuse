<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/session_middleware.php';
require_once BASE_PATH . 'functions/email.php';
require_once BASE_PATH . 'functions/global.php';


// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /public/login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$carId = isset($_GET['car_id']) ? intval($_GET['car_id']) : 0;

if (!$carId) {
    header("Location: /views/user/booking_view.php");
    exit();
}

// Fetch car details
$stmt = $conn->prepare("SELECT make, model, year, price_per_day, image_path FROM fleet WHERE id = ?");
$stmt->bind_param("i", $carId);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$car) {
    header("Location: /views/user/booking_view.php");
    exit();
}

$csrfToken = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Podsumowanie Rezerwacji</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <style>
        .summary-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .summary-container img {
            max-width: 100%;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .form-check {
            margin-bottom: 15px;
        }

        .form-check-label a {
            text-decoration: underline;
            color: #007bff;
        }

        .form-check-label a:hover {
            color: #0056b3;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            padding: 10px;
            font-size: 1rem;
            width: 100%;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="/public/index.php">
                <img src="/assets/images/logo.png" alt="Carfuse Logo" style="height: 40px;">
            </a>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="summary-container">
            <h1 class="text-center">Podsumowanie Rezerwacji</h1>

            <div class="text-center">
                <img src="<?= htmlspecialchars($car['image_path']) ?>" alt="<?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?>">
            </div>

            <h2><?= htmlspecialchars($car['make'] . ' ' . $car['model']) ?> (<?= htmlspecialchars($car['year']) ?>)</h2>
            <p>Cena za dzień: <strong><?= htmlspecialchars($car['price_per_day']) ?> PLN</strong></p>

            <form method="POST" action="/controllers/booking_controller.php">
                <input type="hidden" name="action" value="create_booking">
                <input type="hidden" name="vehicle_id" value="<?= $carId ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="form-check">
                    <input type="checkbox" id="agree_tnc" name="agree_tnc" class="form-check-input" value="yes" required>
                    <label for="agree_tnc" class="form-check-label">
                        Akceptuję <a href="/public/terms.php" target="_blank">Regulamin</a>
                    </label>
                </div>

                <div class="form-check">
                    <input type="checkbox" id="agree_contract" name="agree_contract" class="form-check-input" value="yes" required>
                    <label for="agree_contract" class="form-check-label">
                        Podpisuję umowę wynajmu swoim imieniem i nazwiskiem (<a href="/public/contract.php" target="_blank">Przejrzyj umowę</a>)
                    </label>
                </div>

                <div class="mb-3">
                    <label for="pickup_date" class="form-label">Data odbioru:</label>
                    <input type="date" id="pickup_date" name="pickup_date" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="dropoff_date" class="form-label">Data zwrotu:</label>
                    <input type="date" id="dropoff_date" name="dropoff_date" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary">Zatwierdź Rezerwację</button>
            </form>
        </div>
    </div>
</body>
</html>
