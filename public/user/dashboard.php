<?php
require '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';
require '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/login.php');
}

// Set session timeout
$timeout = 1800; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_unset();
    session_destroy();
    redirect('/login.php');
}
$_SESSION['last_activity'] = time();

$userId = $_SESSION['user_id'];

// Ensure user document directory exists
$userDocumentDir = "../../uploads/users/$userId";
if (!is_dir($userDocumentDir)) {
    mkdir($userDocumentDir, 0777, true);
}

// Fetch user bookings
$bookings = $conn->query("
    SELECT b.id, f.make, f.model, f.registration_number, b.pickup_date, b.dropoff_date, b.total_price, b.status, b.rental_contract_pdf 
    FROM bookings b 
    JOIN fleet f ON b.vehicle_id = f.id 
    WHERE b.user_id = $userId
    ORDER BY b.created_at DESC
");

// Fetch user details
$userDetails = $conn->query("SELECT * FROM users WHERE id = $userId")->fetch_assoc();

// Fetch notification preferences
$preferences = $conn->query("SELECT email_notifications, sms_notifications FROM users WHERE id = $userId")->fetch_assoc();

// Fetch user documents
$userDocuments = glob("$userDocumentDir/*.{pdf}", GLOB_BRACE);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
    $smsNotifications = isset($_POST['sms_notifications']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE users SET email_notifications = ?, sms_notifications = ? WHERE id = ?");
    $stmt->bind_param("iii", $emailNotifications, $smsNotifications, $userId);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Preferencje powiadomień zostały zaktualizowane.";
    } else {
        $_SESSION['error_message'] = "Wystąpił błąd podczas zapisywania preferencji.";
    }
    header("Location: /public/user/dashboard.php#notification-settings");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Użytkownika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            overflow-x: hidden;
        }
        .sidebar {
            min-height: 100vh;
            padding-top: 1rem; /* Add space at the top */
        }
        .list-group-item {
            display: flex;
            align-items: center;
            justify-content: center;
            height: calc(2.5rem + 2px); /* Increased height */
        }
        .list-group-item:hover {
            background-color: #d0d0d0 !important;
        }
    </style>
</head>
<body>
    <?php include '../../views/shared/navbar_user.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <nav class="col-12 col-md-3 col-xl-2 bg-dark sidebar">
                <div class="list-group">
                    <a href="#bookings" class="list-group-item list-group-item-action active" data-bs-toggle="collapse">Rezerwacje</a>
                    <a href="#profile" class="list-group-item list-group-item-action" data-bs-toggle="collapse">Profil</a>
                    <a href="#personal-data" class="list-group-item list-group-item-action" data-bs-toggle="collapse">Zmień Dane Osobowe</a>
                    <a href="#reset-password" class="list-group-item list-group-item-action" data-bs-toggle="collapse">Zresetuj Hasło</a>
                    <a href="#documents" class="list-group-item list-group-item-action" data-bs-toggle="collapse">Twoje Dokumenty</a>
                    <a href="#notification-settings" class="list-group-item list-group-item-action" data-bs-toggle="collapse">Ustawienia Powiadomień</a>
                </div>
            </nav>

            <main class="col-12 col-md-9 col-xl-10 py-3">
                <div id="bookings" class="collapse show">
                    <h1 class="text-center">Twoje Rezerwacje</h1>
                    <p class="text-center">Zarządzaj swoimi rezerwacjami poniżej.</p>
                    <!-- Booking Table and Content Here -->
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
