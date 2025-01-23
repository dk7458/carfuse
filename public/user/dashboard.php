$1
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/db_connect.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/functions.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';

$viewPath = __DIR__ . '/../../../views/user/' . $page . '.php';
if (file_exists($viewPath)) {
    include $viewPath;
} else {
    include __DIR__ . '/../error.php';
}

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

// Sanitize and validate the `page` parameter
$page = $_GET['page'] ?? 'bookings';
$allowedPages = [
    'bookings',
    'profile',
    'personal-data',
    'reset-password',
    'documents',
    'notification-settings'
];
if (!in_array($page, $allowedPages)) {
    $page = 'bookings';
}

// Determine the path to the module file
$moduleFile = __DIR__ . "/views/user/{$page}.php";
if (!file_exists($moduleFile)) {
    die("<div class='alert alert-danger'>Module not found: {$page}</div>");
}

?><!DOCTYPE html>
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
            <!-- Sidebar: Navigation Menu -->
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

            <!-- Main Content: Load Module File -->
            <main class="col-12 col-md-9 col-xl-10 py-3">
                <?php include $moduleFile; ?>
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
                <div class="modal-body" id="responseMessage"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

