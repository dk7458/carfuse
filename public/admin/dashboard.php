<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /views/admin/dashboard.php

require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Get the requested page or default to "podsumowanie"
$page = $_GET['page'] ?? 'podsumowanie';

// Map of valid pages to corresponding files
$validPages = [
    'podsumowanie' => '../../views/admin/summary.php',
    'uzytkownicy' => '../../views/admin/user_manager.php',
    'rezerwacje' => '../../views/admin/booking_manager.php',
    'konserwacja' => '../../views/admin/maintenance_manager.php',
    'raporty' => '../../views/admin/report_manager.php',
    'umowy' => '../../views/admin/contract_manager.php',
    'flota' => '../../views/admin/fleet_manager.php',
    'powiadomienia' => '../../views/admin/notification_manager.php',
    'zarzadzaj_adminami' => '../../views/admin/admin_manager.php',
];

// Ensure the page exists in the map, default to "podsumowanie" if not
if (!array_key_exists($page, $validPages)) {
    $page = 'podsumowanie';
}
$contentFile = $validPages[$page];

// Start output buffering to prevent header issues
ob_start();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel Administratora</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <!-- Admin Navbar -->
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 bg-dark sidebar p-0">
                <ul class="nav flex-column text-white">
                    <?php foreach ($validPages as $key => $value): ?>
                        <li class="nav-item">
                            <a
                                class="nav-link text-white <?= ($page === $key) ? 'bg-secondary' : ''; ?>"
                                href="?page=<?= htmlspecialchars($key); ?>"
                            >
                                <?= ucfirst(str_replace('_', ' ', $key)); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 py-4">
                <?php
                if (file_exists($contentFile)) {
                    include $contentFile;
                } else {
                    echo "<div class='alert alert-danger'>Nie znaleziono pliku: <code>$contentFile</code></div>";
                }
                ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// End output buffering and flush output
ob_end_flush();
?>
