<?php
/*
|--------------------------------------------------------------------------
| Navbar - Górna Nawigacja Dashboardu
|--------------------------------------------------------------------------
| Plik odpowiada za wyświetlanie górnej belki nawigacyjnej w dashboardzie.
| Pokazuje podstawowe opcje nawigacyjne i dostęp do ustawień konta.
|
| Ścieżka: App/Views/layouts/navbar.php
*/
?>
<nav class="navbar">
    <div class="container">
        <a href="/dashboard" class="logo">🚗 CarFuse</a>
        <ul class="nav-links">
            <li><a href="/user/profile" class="dashboard-link">👤 Mój Profil</a></li>
            <li><a href="/user/notifications" class="dashboard-link">🔔 Powiadomienia</a></li>
            <li><a href="/logout">🚪 Wyloguj</a></li>
        </ul>
    </div>
</nav>


