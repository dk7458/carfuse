<?php
// Start session if not already started
require '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';

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
  <style>
    /* Proste style dla layoutu z kolumnami */
    body {
      overflow-x: hidden;
    }
    .sidebar {
      min-height: 100vh; /* Pełna wysokość dla sidebaru */
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

  <!-- Górny pasek nawigacji (osobny plik) -->
  <?php include '../../views/shared/navbar_admin.php'; ?>
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar: Menu z lewej -->
      <nav class="col-12 col-md-3 col-xl-2 bg-dark sidebar p-0">
        <ul class="nav flex-column text-white" id="adminTabs" role="tablist">
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='podsumowanie')?'bg-secondary':''; ?>"
              id="podsumowanie-tab"
              data-bs-toggle="tab"
              href="#podsumowanie"
              role="tab"
              aria-controls="podsumowanie"
              aria-selected="<?php echo ($page==='podsumowanie')?'true':'false'; ?>"
            >
              Podsumowanie
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='uzytkownicy')?'bg-secondary':''; ?>"
              id="uzytkownicy-tab"
              data-bs-toggle="tab"
              href="#uzytkownicy"
              role="tab"
              aria-controls="uzytkownicy"
              aria-selected="<?php echo ($page==='uzytkownicy')?'true':'false'; ?>"
            >
              Użytkownicy
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='rezerwacje')?'bg-secondary':''; ?>"
              id="rezerwacje-tab"
              data-bs-toggle="tab"
              href="#rezerwacje"
              role="tab"
              aria-controls="rezerwacje"
              aria-selected="<?php echo ($page==='rezerwacje')?'true':'false'; ?>"
            >
              Rezerwacje
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='konserwacja')?'bg-secondary':''; ?>"
              id="konserwacja-tab"
              data-bs-toggle="tab"
              href="#konserwacja"
              role="tab"
              aria-controls="konserwacja"
              aria-selected="<?php echo ($page==='konserwacja')?'true':'false'; ?>"
            >
              Konserwacja
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='raporty')?'bg-secondary':''; ?>"
              id="raporty-tab"
              data-bs-toggle="tab"
              href="#raporty"
              role="tab"
              aria-controls="raporty"
              aria-selected="<?php echo ($page==='raporty')?'true':'false'; ?>"
            >
              Raporty
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='umowy')?'bg-secondary':''; ?>"
              id="umowy-tab"
              data-bs-toggle="tab"
              href="#umowy"
              role="tab"
              aria-controls="umowy"
              aria-selected="<?php echo ($page==='umowy')?'true':'false'; ?>"
            >
              Umowy
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='flota')?'bg-secondary':''; ?>"
              id="flota-tab"
              data-bs-toggle="tab"
              href="#flota"
              role="tab"
              aria-controls="flota"
              aria-selected="<?php echo ($page==='flota')?'true':'false'; ?>"
            >
              Flota
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='powiadomienia')?'bg-secondary':''; ?>"
              id="powiadomienia-tab"
              data-bs-toggle="tab"
              href="#powiadomienia"
              role="tab"
              aria-controls="powiadomienia"
              aria-selected="<?php echo ($page==='powiadomienia')?'true':'false'; ?>"
            >
              Powiadomienia
            </a>
          </li>
          <li class="nav-item">
            <a
              class="nav-link text-white <?php echo ($page==='zarzadzaj_adminami')?'bg-secondary':''; ?>"
              id="zarzadzaj_adminami-tab"
              data-bs-toggle="tab"
              href="#zarzadzaj_adminami"
              role="tab"
              aria-controls="zarzadzaj_adminami"
              aria-selected="<?php echo ($page==='zarzadzaj_adminami')?'true':'false'; ?>"
            >
              Zarządzaj Administratorami
            </a>
          </li>
        </ul>
      </nav>

      <!-- Główna treść: ładowanie plików sekcji -->
      <main class="col-12 col-md-9 col-xl-10 py-3">
        <div class="tab-content" id="adminTabsContent">
          <div class="tab-pane fade <?php echo ($page==='podsumowanie')?'show active':''; ?>" id="podsumowanie" role="tabpanel" aria-labelledby="podsumowanie-tab">
            <?php include '../../pages/summary.php'; ?>
          </div>
          <div class="tab-pane fade <?php echo ($page==='uzytkownicy')?'show active':''; ?>" id="uzytkownicy" role="tabpanel" aria-labelledby="uzytkownicy-tab">
            <?php include '../../views/admin/manage_users.php'; ?>
          </div>
          <div class="tab-pane fade <?php echo ($page==='rezerwacje')?'show active':''; ?>" id="rezerwacje" role="tabpanel" aria-labelledby="rezerwacje-tab">
            <?php include 'booking_management.php'; ?>
          </div>
          <div class="tab-pane fade <?php echo ($page==='konserwacja')?'show active':''; ?>" id="konserwacja" role="tabpanel" aria-labelledby="konserwacja-tab">
            <?php include 'maintenance_management.php'; ?>
          </div>
          <div class="tab-pane fade <?php echo ($page==='raporty')?'show active':''; ?>" id="raporty" role="tabpanel" aria-labelledby="raporty-tab">
            <?php include 'reports_management.php'; ?>
          </div>
          <div class="tab-pane fade <?php echo ($page==='umowy')?'show active':''; ?>" id="umowy" role="tabpanel" aria-labelledby="umowy-tab">
            <?php include 'contract_management.php'; ?>
          </div>
          <div class="tab-pane fade <?php echo ($page==='flota')?'show active':''; ?>" id="flota" role="tabpanel" aria-labelledby="flota-tab">
            <?php include 'fleet_management.php'; ?>
          </div>
          <div class="tab-pane fade <?php echo ($page==='powiadomienia')?'show active':''; ?>" id="powiadomienia" role="tabpanel" aria-labelledby="powiadomienia-tab">
            <?php include 'notifications_management.php'; ?>
          </div>
          <div class="tab-pane fade <?php echo ($page==='zarzadzaj_adminami')?'show active':''; ?>" id="zarzadzaj_adminami" role="tabpanel" aria-labelledby="zarzadzaj_adminami-tab">
            <?php include '../../views/admin/manage_admins.php'; ?>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Bootstrap 5.3.0 bundle -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
  ></script>
</body>
</html>
