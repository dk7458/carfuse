<?php
// Start session if not already started
require '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}

// Handle actions sent to the dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Process actions based on the 'action' parameter
    switch ($action) {
        case 'updateFleet':
            include __DIR__ . '/fleet_management.php';
            processFleetUpdate($_POST); // Example function in fleet_management.php
            break;

        case 'deleteUser':
            include __DIR__ . '/views/admin/manage_users.php';
            deleteUser($_POST['user_id']); // Example function
            break;

        case 'saveSettings':
            include __DIR__ . '/settings_management.php';
            saveSettings($_POST); // Example function
            break;

        default:
            // Handle unknown actions
            echo "<div class='alert alert-danger'>Nieznana akcja: <code>$action</code></div>";
    }

    // Redirect back to the relevant section
    $hash = $_POST['hash'] ?? 'podsumowanie';
    header("Location: /public/admin/dashboard.php?page=$hash");
    exit();
}

// Get the requested page from the URL (default to 'podsumowanie')
$page = $_GET['page'] ?? 'podsumowanie';

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
  <!-- Bootstrap 5.3.0 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    /* Basic styles for layout */
    body {
      overflow-x: hidden;
    }
    .sidebar {
      min-height: 100vh;
    }
    @media (min-width: 992px) {
      .sidebar {
        position: sticky;
        top: 0;
      }
    }
    .navbar .nav-link {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100%;
    }
    .navbar .nav-link:hover {
      background-color: #d0d0d0 !important;
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <?php include '../../views/shared/navbar_admin.php'; ?>

  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <nav class="col-12 col-md-3 col-xl-2 bg-dark sidebar p-0">
        <ul class="nav flex-column text-white">
          <?php foreach ($validPages as $key => $file): ?>
            <li class="nav-item">
              <a
                href="?page=<?php echo $key; ?>"
                class="nav-link text-white <?php echo ($page === $key) ? 'bg-secondary' : ''; ?>"
              >
                <?php echo ucfirst(str_replace('_', ' ', $key)); ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <!-- Main Content -->
      <main class="col-12 col-md-9 col-xl-10 py-3">
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

  <!-- Bootstrap 5.3.0 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
