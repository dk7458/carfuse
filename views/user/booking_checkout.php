$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /views/user/booking_checkout.php
require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/functions.php';


// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /public/login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$bookingId = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if (!$bookingId) {
    header("Location: /views/user/booking_summary.php");
    exit();
}

// Fetch booking details
$stmt = $conn->prepare("
    SELECT b.total_price, f.make, f.model, f.year 
    FROM bookings b 
    JOIN fleet f ON b.vehicle_id = f.id 
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $bookingId, $userId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    header("Location: /views/user/booking_summary.php");
    exit();
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Podsumowanie Płatności</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <style>
        .checkout-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .checkout-container h2 {
            margin-top: 20px;
            font-size: 1.5rem;
            color: #333;
        }

        .payment-method {
            margin: 15px 0;
        }

        .payment-method label {
            font-size: 1.1rem;
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

        .navbar {
            margin-bottom: 20px;
        }

        .error-message {
            color: red;
            font-size: 0.9rem;
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
        <div class="checkout-container">
            <h1 class="text-center">Podsumowanie Płatności</h1>

            <h2>Rezerwacja: <?= htmlspecialchars($booking['make'] . ' ' . $booking['model'] . ' (' . $booking['year'] . ')') ?></h2>
            <p>Łączna kwota do zapłaty: <strong><?= htmlspecialchars(number_format($booking['total_price'], 2, ',', ' ')) ?> PLN</strong></p>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_payment_method'): ?>
                <p class="error-message">Nieprawidłowa metoda płatności. Spróbuj ponownie.</p>
            <?php endif; ?>

            <form method="POST" action="/controllers/payment_controller.php">
                <input type="hidden" name="action" value="process_payment">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="booking_id" value="<?= intval($bookingId) ?>">

                <div class="payment-method">
                    <label>
                        <input type="radio" name="payment_method" value="blik" required> BLIK
                    </label>
                </div>

                <div class="payment-method">
                    <label>
                        <input type="radio" name="payment_method" value="card" required> Karta płatnicza
                    </label>
                </div>

                <div class="payment-method">
                    <label>
                        <input type="radio" name="payment_method" value="transfer" required> Przelew bankowy
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Przejdź do płatności</button>
            </form>
        </div>
    </div>
</body>
</html>
