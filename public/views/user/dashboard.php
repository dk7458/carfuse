<?php
require_once __DIR__ . '/../../../helpers/SecurityHelper.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}
?>

/*
|--------------------------------------------------------------------------
| Panel Użytkownika
|--------------------------------------------------------------------------
| Ten plik jest centralnym miejscem zarządzania kontem użytkownika. Wyświetla
| podsumowanie aktywności oraz umożliwia przełączanie między sekcjami dashboardu.
|
| Ścieżka: App/Views/user/dashboard.php
|
| Zależy od:
| - JavaScript: /js/dashboard.js (obsługa AJAX, dynamiczne ładowanie sekcji)
| - CSS: /css/dashboard.css (stylizacja interfejsu użytkownika)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane użytkownika)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do dynamicznego ładowania sekcji)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Panel Użytkownika</h1>

<div class="user-dashboard-container">
    <div class="row">
        <!-- Podsumowanie aktywności -->
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h4>Twoje Rezerwacje</h4>
                    <p id="totalBookings" class="display-6">0</p>
                    <a href="#bookings/view" class="dashboard-link">Zarządzaj rezerwacjami</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h4>Historia Płatności</h4>
                    <p id="totalPayments" class="display-6">0 PLN</p>
                    <a href="#payments/history" class="dashboard-link">Zobacz płatności</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h4>Twoje Dokumenty</h4>
                    <p id="totalDocuments" class="display-6">0</p>
                    <a href="#documents/user_documents" class="dashboard-link">Przeglądaj dokumenty</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Ostatnie powiadomienia -->
    <h3 class="mt-4">Ostatnie Powiadomienia</h3>
    <ul id="notificationList" class="list-group">
        <!-- Powiadomienia ładowane dynamicznie -->
    </ul>
    <div id="noNotificationsMessage" class="alert alert-warning mt-3" style="display:none;">Brak nowych powiadomień.</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    function loadDashboardData() {
        fetch("/api/user/dashboard_summary.php")
            .then(response => response.json())
            .then(data => {
                document.getElementById("totalBookings").textContent = data.totalBookings || 0;
                document.getElementById("totalPayments").textContent = data.totalPayments + " PLN" || "0 PLN";
                document.getElementById("totalDocuments").textContent = data.totalDocuments || 0;

                const notificationList = document.getElementById("notificationList");
                const noNotificationsMessage = document.getElementById("noNotificationsMessage");
                notificationList.innerHTML = "";
                if (data.notifications.length > 0) {
                    data.notifications.forEach(notification => {
                        notificationList.innerHTML += `<li class="list-group-item">${notification.message}</li>`;
                    });
                    noNotificationsMessage.style.display = "none";
                } else {
                    noNotificationsMessage.style.display = "block";
                }
            })
            .catch(error => console.error("Błąd ładowania danych użytkownika:", error));
    }

    loadDashboardData();
});
</script>
