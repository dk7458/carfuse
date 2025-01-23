<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/functions.php';


// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /public/login.php");
    exit();
}

$csrfToken = generateCsrfToken();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Cars</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <style>
        .car-tile {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 10px;
            width: 300px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            display: inline-block;
        }

        .car-tile img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .promo-badge {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            font-size: 0.8rem;
            border-radius: 5px;
            position: absolute;
            top: 10px;
            right: 10px;
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

    <div class="container">
        <h1 class="text-center mt-4">Wybierz Samochód</h1>
        <div class="text-center mb-4">
            <form method="POST" id="availability-form">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <label for="pickup_date">Data odbioru:</label>
                <input type="date" id="pickup_date" name="pickup_date" required>

                <label for="dropoff_date">Data zwrotu:</label>
                <input type="date" id="dropoff_date" name="dropoff_date" required>

                <button type="button" id="check-availability" class="btn btn-primary">Sprawdź dostępność</button>
            </form>
        </div>

        <div id="car-list" class="text-center">
            <!-- Car tiles will be dynamically loaded here -->
        </div>
    </div>

    <script>
        document.getElementById('check-availability').addEventListener('click', function () {
            const form = document.getElementById('availability-form');
            const formData = new FormData(form);

            fetch('/controllers/booking_controller.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const carList = document.getElementById('car-list');
                        carList.innerHTML = '';

                        data.cars.forEach(car => {
                            const carTile = document.createElement('div');
                            carTile.className = 'car-tile';
                            carTile.innerHTML = `
                                <div style="position: relative;">
                                    ${car.has_promo ? '<div class="promo-badge">Promocja</div>' : ''}
                                    <img src="${car.image_path}" alt="${car.make} ${car.model}">
                                </div>
                                <h3>${car.make} ${car.model}</h3>
                                <p>Rok: ${car.year}</p>
                                <p>Cena za dzień: ${car.price_per_day} PLN</p>
                                <button class="btn btn-success" onclick="bookCar(${car.id})">Wybierz</button>
                            `;
                            carList.appendChild(carTile);
                        });
                    } else {
                        alert(data.error || 'Wystąpił błąd podczas ładowania samochodów.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Nie udało się sprawdzić dostępności samochodów. Spróbuj ponownie.');
                });
        });

        function bookCar(carId) {
            window.location.href = `/views/user/booking_summary.php?car_id=${carId}`;
        }
    </script>
</body>
</html>
