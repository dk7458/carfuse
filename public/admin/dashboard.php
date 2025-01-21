<?php
// Start session if not already started
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';

// Ensure the user is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('/public/login.php');
}
// 1. Pobranie parametru "?page=" z adresu, np. dashboard.php?page=konserwacja
$page = $_GET['page'] ?? 'podsumowanie';

// 2. Mapa "klucz => plik", czyli nazwy sekcji na linki w menu -> pliki, które mają być wczytane.
$validPages = [
    'podsumowanie' => '../../pages/summary.php',             // np. plik powitalny (stwórz sam)
    'uzytkownicy'   => '../../views/admin/manage_users.php',    // (../../views/admin/user_management.php) dostosuj ścieżkę
    'rezerwacje'    => 'booking_management.php',             // (pages/booking_management.php) dostosuj ścieżkę
    'konserwacja'   => 'maintenance_management.php',
    'raporty'       => 'reports_management.php',
    'umowy'         => 'contract_management.php',
    'flota'         => 'fleet_management.php',
    'powiadomienia' => 'notifications_management.php',
    'zarzadzaj_adminami' => '../../views/admin/manage_admins.php', // Add admin management
];
// 3. Sprawdź, czy klucz istnieje w tablicy $validPages, w przeciwnym razie ładuj "podsumowanie".
if (!array_key_exists($page, $validPages)) {
    $page = 'podsumowanie';
}
$contentFile = $validPages[$page];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <title>Panel Administratora</title>
  <!-- Bootstrap 5.3.0 CSS -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
  >
  <link rel="stylesheet" href="/public/theme.css">
  <style>
    /* Proste style dla layoutu z kolumnami */
    body {
      overflow-x: hidden;
    }
    .sidebar {
      min-height: 100vh; /* Pełna wysokość dla sidebaru */
      padding-top: calc(2.75rem + 2px); /* Add space at the top */
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
      height: calc(2.5rem + 2px); /* Increased height */
    }
    .navbar .nav-link:hover {
      background-color: #d0d0d0 !important;
    }
  </style>
</head>
<body>

  <!-- Górny pasek nawigacji (osobny plik) -->
  <?php include '../../views/shared/navbar_admin.php'; ?>
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar: Menu z lewej -->
      <nav class="col-12 col-md-3 col-xl-2 bg-dark sidebar p-0">
        <ul class="nav flex-column text-white">
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='podsumowanie')?'bg-secondary':''; ?>"
              href="?page=podsumowanie"
            >
              Podsumowanie
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='uzytkownicy')?'bg-secondary':''; ?>"
              href="?page=uzytkownicy"
            >
              Użytkownicy
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='rezerwacje')?'bg-secondary':''; ?>"
              href="?page=rezerwacje"
            >
              Rezerwacje
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='konserwacja')?'bg-secondary':''; ?>"
              href="?page=konserwacja"
            >
              Konserwacja
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='raporty')?'bg-secondary':''; ?>"
              href="?page=raporty"
            >
              Raporty
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='umowy')?'bg-secondary':''; ?>"
              href="?page=umowy"
            >
              Umowy
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='flota')?'bg-secondary':''; ?>"
              href="?page=flota"
            >
              Flota
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='powiadomienia')?'bg-secondary':''; ?>"
              href="?page=powiadomienia"
            >
              Powiadomienia
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='zarzadzaj_adminami')?'bg-secondary':''; ?>"
              href="?page=zarzadzaj_adminami"
            >
              Zarządzaj Administratorami
            </a>
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

  <!-- Bootstrap 5.3.0 bundle -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
  ></script>
</body>
</html>