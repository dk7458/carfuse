<?php
/*
|--------------------------------------------------------------------------
| Sidebar - Nawigacja w Dashboardzie
|--------------------------------------------------------------------------
| Plik odpowiada za wyświetlanie bocznego menu nawigacyjnego w dashboardzie.
| Umożliwia użytkownikom i administratorom przełączanie się między podstronami.
|
| Ścieżka: App/Views/layouts/sidebar.php
*/
?>

<aside class="sidebar">
    <nav class="sidebar-menu">
        <ul>
            <li><a href="/user/dashboard" class="dashboard-link">📊 Panel</a></li>
            <li><a href="/bookings/view" class="dashboard-link">📅 Moje rezerwacje</a></li>
            <li><a href="/payments/history" class="dashboard-link">💳 Historia płatności</a></li>
            <li><a href="/documents/user_documents" class="dashboard-link">📄 Moje dokumenty</a></li>
            <li><a href="/user/notifications" class="dashboard-link">🔔 Powiadomienia</a></li>
            <li><a href="/user/profile" class="dashboard-link">👤 Profil</a></li>
            <li><a href="/admin/users" class="dashboard-link">👥 Zarządzanie użytkownikami</a></li>
            <li><a href="/admin/audit_logs" class="dashboard-link">📜 Logi audytowe</a></li>
            <li><a href="/admin/logs" class="dashboard-link">📂 Logi systemowe</a></li>
            <li><a href="/admin/reports" class="dashboard-link">📑 Raporty</a></li>
            <li><a href="/admin/settings" class="dashboard-link">⚙️ Ustawienia</a></li>
            <li><a href="/logout">🚪 Wyloguj</a></li>
        </ul>
    </nav>
</aside>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".dashboard-link").forEach(link => {
            link.addEventListener("click", function(e) {
                e.preventDefault();
                let targetView = this.getAttribute("href");
                fetch(targetView).then(response => response.text()).then(data => {
                    document.getElementById("dashboard-view").innerHTML = data;
                });
            });
        });
    });
</script>
