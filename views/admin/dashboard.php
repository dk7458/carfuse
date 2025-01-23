<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel Administratora</title>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container mt-5">
        <h1>Panel Administratora</h1>

        <!-- Quick Links Section -->
        <div class="row mt-4 text-center">
            <div class="col-md-4">
                <a href="/views/admin/user_manager.php" class="btn btn-primary w-100" title="Zarządzaj użytkownikami">
                    <i class="bi bi-person-plus"></i> Użytkownicy
                </a>
            </div>
            <div class="col-md-4">
                <a href="/views/admin/fleet_manager.php" class="btn btn-primary w-100" title="Zarządzaj flotą">
                    <i class="bi bi-car-front"></i> Flota
                </a>
            </div>
            <div class="col-md-4">
                <a href="/views/admin/report_manager.php" class="btn btn-primary w-100" title="Generuj raporty">
                    <i class="bi bi-file-bar-graph"></i> Raporty
                </a>
            </div>
        </div>

        <?php if ($userRole === 'super_admin'): ?>
            <!-- Super Admin Exclusive Links -->
            <div class="row mt-3 text-center">
                <div class="col-md-4">
                    <a href="/views/admin/settings.php" class="btn btn-secondary w-100" title="Zarządzaj ustawieniami">
                        <i class="bi bi-gear"></i> Ustawienia
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="/views/admin/logs_manager.php" class="btn btn-warning w-100" title="Przegląd logów">
                        <i class="bi bi-journal-text"></i> Logi
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Summary Metrics -->
        <div class="row mt-5">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Użytkownicy</h5>
                        <p class="card-text">
                            <?= $totalUsers ?> 
                            <span class="text-success">(↑5%)</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Pojazdy</h5>
                        <p class="card-text"><?= $totalVehicles ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Rezerwacje</h5>
                        <p class="card-text"><?= $totalBookings ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Przychody</h5>
                        <p class="card-text"><?= number_format($totalRevenue, 2, ',', ' ') ?> PLN</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="mt-5">
            <h3>Statystyki Wizualne</h3>
            <select id="chartTypeSelector" class="form-select mb-3">
                <option value="bookings">Rezerwacje</option>
                <option value="revenue">Przychody</option>
            </select>
            <canvas id="dashboardChart"></canvas>
        </div>
    </div>

    <script src="/assets/js/dashboard.js"></script>
</body>
</html>
