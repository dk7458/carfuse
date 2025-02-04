<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<h1 class="text-center">Panel użytkownika</h1>

<div class="dashboard-container">
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h4>Moje rezerwacje</h4>
                    <p class="fs-5" id="totalBookings">Ładowanie...</p>
                    <a href="/bookings/view" class="btn btn-primary">Zobacz rezerwacje</a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h4>Historia płatności</h4>
                    <p class="fs-5" id="totalPayments">Ładowanie...</p>
                    <a href="/payments/history" class="btn btn-primary">Zobacz historię</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h4>Powiadomienia</h4>
                    <p class="fs-5" id="totalNotifications">Ładowanie...</p>
                    <a href="/user/notifications" class="btn btn-primary">Zobacz powiadomienia</a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h4>Mój profil</h4>
                    <a href="/user/profile" class="btn btn-primary">Edytuj profil</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/js/user_dashboard.js"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
