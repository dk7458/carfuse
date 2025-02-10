<?php
/*
|--------------------------------------------------------------------------
| Dashboard - Centralny Panel dla Użytkownika i Administratora
|--------------------------------------------------------------------------
| Ten plik obsługuje zarówno użytkowników, jak i administratorów.
| Na podstawie roli zmienia dostępne opcje w nawigacji bocznej.
|
| Ścieżka: App/Views/dashboard.php
*/
require_once __DIR__ . '/../layouts/header.php';

// Sprawdzenie roli użytkownika (przykładowa implementacja)
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
?>

<aside class="sidebar">
    <nav class="sidebar-menu">
        <ul>
            <li><a href="#user/dashboard" class="dashboard-link">📊 Panel</a></li>
            <li><a href="#bookings/view" class="dashboard-link">📅 Moje rezerwacje</a></li>
            <li><a href="#payments/history" class="dashboard-link">💳 Historia płatności</a></li>
            <li><a href="#documents/user_documents" class="dashboard-link">📄 Moje dokumenty</a></li>
            <li><a href="#user/notifications" class="dashboard-link">🔔 Powiadomienia</a></li>
            <li><a href="#user/profile" class="dashboard-link">👤 Profil</a></li>

            <?php if ($isAdmin): ?>
                <li><a href="#admin/users" class="dashboard-link">👥 Zarządzanie użytkownikami</a></li>
                <li><a href="#admin/audit_logs" class="dashboard-link">📜 Logi audytowe</a></li>
                <li><a href="#admin/logs" class="dashboard-link">📂 Logi systemowe</a></li>
                <li><a href="#admin/reports" class="dashboard-link">📑 Raporty</a></li>
                <li><a href="#admin/settings" class="dashboard-link">⚙️ Ustawienia</a></li>
                <li><a href="#admin/payments/dashboard" class="dashboard-link">💳 Płatności</a></li>
                <li><a href="#admin/documents/documents" class="dashboard-link">📄 Dokumenty</a></li>
            <?php endif; ?>

            <li><a href="/logout">🚪 Wyloguj</a></li>
        </ul>
    </nav>
</aside>

<div id="dashboard-content">
    <h1 class="text-center">Panel <?= $isAdmin ? 'Administratora' : 'Użytkownika' ?></h1>
    <div id="dashboard-view">
        <?php require_once __DIR__ . '/' . ($isAdmin ? 'admin/dashboard-home.php' : 'user/dashboard-home.php'); ?>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".dashboard-link").forEach(link => {
            link.addEventListener("click", function(e) {
                e.preventDefault();
                let targetView = this.getAttribute("href").substring(1);
                fetch(`/App/Views/${targetView}.php`).then(response => response.text()).then(data => {
                    document.getElementById("dashboard-view").innerHTML = data;
                });
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
