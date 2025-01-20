
<?php
// Start session if not already started
require '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Get the requested page from the URL (default to 'podsumowanie')
$page = $_GET['page'] ?? 'podsumowanie';

// Handle actions sent to the dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirectPage = $_POST['hash'] ?? $page;

    // Process actions based on the 'action' parameter
    switch ($action) {
        case 'updateFleet':
            include __DIR__ . '/fleet_management.php';
            processFleetUpdate($_POST);
            break;

        case 'deleteUser':
            include __DIR__ . '/views/admin/manage_users.php';
            deleteUser($_POST['user_id']);
            break;

        case 'saveSettings':
            include __DIR__ . '/settings_management.php';
            saveSettings($_POST);
            break;

        default:
            // Handle unknown actions gracefully
            $_SESSION['error_message'] = "Nieznana akcja: $action";
    }

    // Redirect back to the relevant section
    header("Location: /public/admin/dashboard.php?page=$redirectPage");
    exit();
}

// Define valid pages and their corresponding files
$validPages = [
    'podsumowanie' => '../../pages/summary.php',
    'uzytkownicy'   => '../../views/admin/manage_users.php',
    'rezerwacje'    => 'booking_management.php',
    'konserwacja'   => 'maintenance_management.php',
    'raporty'       => 'reports_management.php',
    'umowy'         => 'contract_management.php',
    'flota'         => 'fleet_management.php',
    'powiadomienia' => 'notifications_management.php',
    'zarzadzaj_adminami' => '../../views/admin/manage_admins.php',
];

// If the requested page is invalid, default to 'podsumowanie'
if (!array_key_exists($page, $validPages)) {
    $page = 'podsumowanie';
}

$contentFile = $validPages[$page];
?>

<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Administratora</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
  <?php include '../../views/shared/navbar_admin.php'; ?>

  <div class="container-fluid">
    <div class="row">
      <nav class="col-md-3 bg-dark sidebar">
        <ul class="nav flex-column">
          <?php foreach ($validPages as $key => $file): ?>
            <li class="nav-item">
              <a href="?page=<?php echo $key; ?>" class="nav-link text-white <?php echo ($page === $key) ? 'bg-secondary' : ''; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $key)); ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <main class="col-md-9 py-3">
        <?php
        if (file_exists(__DIR__ . '/' . $contentFile)) {
            include __DIR__ . '/' . $contentFile;
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