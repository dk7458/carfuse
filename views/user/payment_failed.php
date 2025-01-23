$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /views/user/payment_failed.php
require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/functions.php';


$bookingId = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if (!$bookingId) {
    header("Location: /views/user/booking_view.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Płatność nie powiodła się</title>
    <link rel="stylesheet" href="/assets/css/theme.css">
    <style>
        .failed-container {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .failed-container h1 {
            color: #dc3545;
            font-size: 2rem;
        }

        .failed-container p {
            font-size: 1.2rem;
            margin-top: 10px;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 5px;
            color: #fff;
            text-decoration: none;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="failed-container">
        <h1>Płatność nie powiodła się</h1>
        <p>Niestety, płatność za rezerwację (ID: <?= htmlspecialchars($bookingId) ?>) nie została ukończona.</p>

        <a href="/views/user/booking_checkout.php?booking_id=<?= $bookingId ?>" class="btn btn-primary">Spróbuj ponownie</a>
    </div>
</body>
</html>
