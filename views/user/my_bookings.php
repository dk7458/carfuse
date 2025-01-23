$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /views/user/my_bookings.php
require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';


$userId = $_SESSION['user_id'];
$csrfToken = generateCsrfToken();

// Fetch user's bookings
$stmt = $conn->prepare("
    SELECT b.id, f.make, f.model, b.pickup_date, b.dropoff_date, b.total_price, b.status, b.rental_contract_pdf 
    FROM bookings b 
    JOIN fleet f ON b.vehicle_id = f.id 
    WHERE b.user_id = ? 
    ORDER BY b.pickup_date DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moje Rezerwacje</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
    <style>
        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }
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
        .availability-feedback {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include '../shared/navbar_user.php'; ?>

    <div class="container">
        <h1 class="mt-5">Moje Rezerwacje</h1>

        <?php if ($bookings->num_rows > 0): ?>
            <table class="table table-bordered mt-4">
                <thead class="table-dark">
                    <tr>
                        <th>Samochód</th>
                        <th>Data Odbioru</th>
                        <th>Data Zwrotu</th>
                        <th>Cena</th>
                        <th>Status</th>
                        <th>Umowa</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($booking['make'] . ' ' . $booking['model']) ?></td>
                            <td><?= htmlspecialchars($booking['pickup_date']) ?></td>
                            <td><?= htmlspecialchars($booking['dropoff_date']) ?></td>
                            <td><?= number_format($booking['total_price'], 2, ',', ' ') ?> PLN</td>
                            <td>
                                <span class="badge <?= $booking['status'] === 'paid' ? 'bg-success' : 'bg-warning' ?>">
                                    <?= $booking['status'] === 'paid' ? 'Opłacona' : 'Nieopłacona' ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($booking['rental_contract_pdf'])): ?>
                                    <a href="<?= htmlspecialchars($booking['rental_contract_pdf']) ?>" target="_blank" class="btn btn-sm btn-primary">Pobierz</a>
                                <?php else: ?>
                                    Niedostępna
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($booking['status'] === 'paid' && strtotime($booking['pickup_date']) > time()): ?>
                                    <form action="/controllers/booking_controller.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="cancel_booking">
                                        <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['id']) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno chcesz anulować tę rezerwację?')">Anuluj</button>
                                    </form>
                                <?php else: ?>
                                    ---
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info mt-4">Nie masz żadnych rezerwacji.</div>
        <?php endif; ?>

        <h2 class="mt-5">Sprawdź Dostępność Samochodów</h2>
        <form method="POST" id="availability-form" class="text-center mb-4">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <label for="pickup_date">Data odbioru:</label>
            <input type="date" id="pickup_date" name="pickup_date" required>

            <label for="dropoff_date">Data zwrotu:</label>
            <input type="date" id="dropoff_date" name="dropoff_date" required>

            <button type="button" id="check-availability" class="btn btn-primary">Sprawdź dostępność</button>
        </form>

        <div id="car-list" class="text-center"></div>
        <div id="availability-feedback" class="availability-feedback"></div>
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
                const carList = document.getElementById('car-list');
                const feedback = document.getElementById('availability-feedback');
                carList.innerHTML = '';
                feedback.innerHTML = '';

                if (data.success) {
                    data.cars.forEach(car => {
                        carList.innerHTML += `
                            <div class="car-tile">
                                <div style="position: relative;">
                                    ${car.has_promo ? '<div class="promo-badge">Promocja</div>' : ''}
                                    <img src="${car.image_path}" alt="${car.make} ${car.model}">
                                </div>
                                <h3>${car.make} ${car.model}</h3>
                                <p>Rok: ${car.year}</p>
                                <p>Cena za dzień: ${car.price_per_day} PLN</p>
                                <button class="btn btn-success" onclick="bookCar(${car.id})">Wybierz</button>
                            </div>
                        `;
                    });
                } else {
                    feedback.innerHTML = `<div class="alert alert-danger">${data.error || 'Nie znaleziono samochodów.'}</div>`;
                }
            })
            .catch(() => {
                alert('Nie udało się sprawdzić dostępności samochodów. Spróbuj ponownie.');
            });
        });

        function bookCar(carId) {
            window.location.href = `/views/user/booking_summary.php?car_id=${carId}`;
        }
    </script>
</body>
</html>
