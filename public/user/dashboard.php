<?php
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';
require once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';

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

// 1. Pobranie parametru "?page=" z adresu, np. dashboard.php?page=profile
$page = $_GET['page'] ?? 'bookings';

// 2. Mapa "klucz => plik", czyli nazwy sekcji na linki w menu -> pliki, które mają być wczytane.
$validPages = [
    'bookings' => 'booking_details.php',
    'profile' => 'profile.php',
    'personal-data' => 'user_controller_proxy.php',
    'reset-password' => 'reset_password.php',
    'documents' => 'documents.php',
    'notification-settings' => 'notification_settings_proxy.php',
];

// 3. Sprawdź, czy klucz istnieje w tablicy $validPages, w przeciwnym razie ładuj "bookings".
if (!array_key_exists($page, $validPages)) {
    $page = 'bookings';
}
$contentFile = $validPages[$page];

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
    header("Location: /public/user/dashboard.php?page=notification-settings");
    exit();
}
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
            <!-- Sidebar: Menu z lewej -->
            <nav class="col-12 col-md-3 col-xl-2 bg-dark sidebar p-0">
                <ul class="nav flex-column text-white">
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo ($page === 'bookings') ? 'bg-secondary' : ''; ?>" href="?page=bookings">Rezerwacje</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo ($page === 'profile') ? 'bg-secondary' : ''; ?>" href="?page=profile">Profil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo ($page === 'personal-data') ? 'bg-secondary' : ''; ?>" href="?page=personal-data">Zmień Dane Osobowe</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo ($page === 'reset-password') ? 'bg-secondary' : ''; ?>" href="?page=reset-password">Zresetuj Hasło</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo ($page === 'documents') ? 'bg-secondary' : ''; ?>" href="?page=documents">Twoje Dokumenty</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo ($page === 'notification-settings') ? 'bg-secondary' : ''; ?>" href="?page=notification-settings">Ustawienia Powiadomień</a>
                    </li>
                </ul>
            </nav>

            <!-- Główna treść: ładowanie plików sekcji -->
            <main class="col-12 col-md-9 col-xl-10 py-3">
                <?php
                // Start session if not already started
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                // Wczytaj plik odpowiadający aktualnie wybranej stronie
                if (file_exists(__DIR__ . '/' . $contentFile)) {
                    include __DIR__ . '/' . $contentFile;
                } else {
                    echo "<div class='alert alert-danger'>Nie znaleziono pliku: <code>$contentFile</code></div>";
                }
                ?>
            </main>
        </div>
    </div>

    <!-- Modal for displaying responses -->
    <div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="responseModalLabel">Response</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="responseMessage">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.standard-form').forEach(form => {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    const formData = new FormData(this);
                    fetch(this.action, {
                        method: this.method,
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        const message = data.success || data.error;
                        document.getElementById('responseMessage').textContent = message;
                        const responseModal = new bootstrap.Modal(document.getElementById('responseModal'));
                        responseModal.show();

                        if (data.success) {
                            // Update profile section with the latest data
                            fetch('/user/get_user_details.php')
                                .then(response => response.json())
                                .then(userDetails => {
                                    document.querySelectorAll('#profile .card p').forEach(p => {
                                        const field = p.querySelector('strong').textContent.toLowerCase().replace(':', '');
                                        p.querySelector('span').textContent = userDetails[field];
                                    });
                                });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('responseMessage').textContent = 'An error occurred. Please try again.';
                        const responseModal = new bootstrap.Modal(document.getElementById('responseModal'));
                        responseModal.show();
                    });
                });
            });

            document.querySelectorAll('.pdf-viewer').forEach(viewer => {
                const url = viewer.previousElementSibling.href;
                const loadingTask = pdfjsLib.getDocument(url);
                loadingTask.promise.then(pdf => {
                    pdf.getPage(1).then(page => {
                        const scale = 1.5;
                        const viewport = page.getViewport({ scale: scale });
                        const canvas = document.createElement('canvas');
                        const context = canvas.getContext('2d');
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        viewer.appendChild(canvas);
                        const renderContext = {
                            canvasContext: context,
                            viewport: viewport
                        };
                        page.render(renderContext);
                    });
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>
