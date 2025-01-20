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

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Użytkownika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/public/theme.css">
</head>
<body>
    <?php include '../../views/shared/navbar_user.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 bg-dark sidebar">
                <ul class="nav flex-column" id="userTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#bookings" role="tab">Rezerwacje</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#profile" role="tab">Profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#notification-settings" role="tab">Powiadomienia</a>
                    </li>
                </ul>
            </nav>

            <main class="col-md-9 col-lg-10 tab-content" id="userTabsContent">
                <div class="tab-pane fade show active" id="bookings" role="tabpanel">
                    <h2>Twoje Rezerwacje</h2>
                    <!-- Booking details table -->
                </div>
                <div class="tab-pane fade" id="profile" role="tabpanel">
                    <h2>Profil Użytkownika</h2>
                    <!-- Profile details -->
                </div>
                <div class="tab-pane fade" id="notification-settings" role="tabpanel">
                    <h2>Ustawienia Powiadomień</h2>
                    <!-- Notification preferences form -->
                </div>
            </main>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="responseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Wiadomość</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="responseMessage"></div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.standard-form').forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                fetch(this.action, {
                    method: this.method,
                    body: new FormData(this)
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('responseMessage').textContent = data.success || data.error;
                    new bootstrap.Modal(document.getElementById('responseModal')).show();
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
