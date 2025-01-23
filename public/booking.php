<?php

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';


// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/public/login.php');
}

// Fetch available vehicles
$vehicles = $conn->query("SELECT id, make, model, registration_number, price_per_day, availability 
                          FROM fleet WHERE availability = 1");

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezerwacja Pojazdu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles/settings.css" rel="stylesheet">
    <link rel="stylesheet" href="/theme.css">
</head>
<body>
    <?php include '../views/shared/navbar.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Rezerwacja Pojazdu</h1>
        <form action="../controllers/booking_controller.php" method="POST" class="standard-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="create_booking">

            <div class="mb-3">
                <label for="vehicle" class="form-label">Wybierz Pojazd</label>
                <select id="vehicle" name="vehicle_id" class="form-select" required>
                    <option value="" disabled selected>Wybierz...</option>
                    <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                        <option value="<?php echo $vehicle['id']; ?>">
                            <?php echo "{$vehicle['make']} {$vehicle['model']} ({$vehicle['registration_number']}) - {$vehicle['price_per_day']} PLN/dzień"; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="pickup_date" class="form-label">Data Odbioru</label>
                    <input type="date" id="pickup_date" name="pickup_date" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="dropoff_date" class="form-label">Data Zwrotu</label>
                    <input type="date" id="dropoff_date" name="dropoff_date" class="form-control" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="total_price" class="form-label">Całkowity Koszt (Automatycznie Obliczany)</label>
                <input type="text" id="total_price" name="total_price" class="form-control" readonly>
            </div>

            <button type="submit" class="btn btn-primary w-100">Zarezerwuj</button>
        </form>
    </div>

    <script>
        const vehicleSelect = document.getElementById('vehicle');
        const pickupDateInput = document.getElementById('pickup_date');
        const dropoffDateInput = document.getElementById('dropoff_date');
        const totalPriceInput = document.getElementById('total_price');

        function calculateTotalPrice() {
            const vehicleOption = vehicleSelect.options[vehicleSelect.selectedIndex];
            const pricePerDay = parseFloat(vehicleOption.text.split('-')[1].split(' ')[1]);
            const pickupDate = new Date(pickupDateInput.value);
            const dropoffDate = new Date(dropoffDateInput.value);

            if (pickupDate && dropoffDate && dropoffDate > pickupDate) {
                const days = Math.ceil((dropoffDate - pickupDate) / (1000 * 60 * 60 * 24));
                totalPriceInput.value = `${(days * pricePerDay).toFixed(2)} PLN`;
            } else {
                totalPriceInput.value = '';
            }
        }

        vehicleSelect.addEventListener('change', calculateTotalPrice);
        pickupDateInput.addEventListener('input', calculateTotalPrice);
        dropoffDateInput.addEventListener('input', calculateTotalPrice);
    </script>
</body>
</html>
