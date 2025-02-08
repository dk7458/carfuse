<?php
require_once __DIR__ . '/../../../helpers/SecurityHelper.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}
?>

/*
|--------------------------------------------------------------------------
| Powiadomienia Użytkownika
|--------------------------------------------------------------------------
| Ten plik umożliwia użytkownikowi przeglądanie powiadomień, oznaczanie ich
| jako przeczytane oraz usuwanie.
|
| Ścieżka: App/Views/user/notifications.php
|
| Zależy od:
| - JavaScript: /js/dashboard.js (obsługa AJAX)
| - CSS: /css/dashboard.css (stylizacja interfejsu użytkownika)
| - PHP: csrf_field() (zabezpieczenie formularzy)
| - MySQL (dane powiadomień)
|
| Technologie:
| - PHP 8+ (backend)
| - MySQL (baza danych)
| - JavaScript (AJAX do dynamicznego ładowania i aktualizacji powiadomień)
| - HTML, CSS (interfejs)
*/

<h1 class="text-center">Powiadomienia</h1>

<div class="user-notifications-container">
    <ul id="notificationList" class="list-group">
        <!-- Powiadomienia ładowane dynamicznie -->
    </ul>
    <div id="noNotificationsMessage" class="alert alert-warning mt-3" style="display:none;">Brak nowych powiadomień.</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    function loadNotifications() {
        fetch("/api/user/get_notifications.php")
            .then(response => response.json())
            .then(data => {
                const notificationList = document.getElementById("notificationList");
                const noNotificationsMessage = document.getElementById("noNotificationsMessage");
                notificationList.innerHTML = "";
                
                if (data.length > 0) {
                    data.forEach(notification => {
                        notificationList.innerHTML += `
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>${notification.message}</span>
                                <div>
                                    ${notification.is_read ? "" : `<button class="btn btn-sm btn-success" onclick="markAsRead(${notification.id})">Oznacz jako przeczytane</button>`}
                                    <button class="btn btn-sm btn-danger" onclick="deleteNotification(${notification.id})">Usuń</button>
                                </div>
                            </li>
                        `;
                    });
                    noNotificationsMessage.style.display = "none";
                } else {
                    noNotificationsMessage.style.display = "block";
                }
            })
            .catch(error => console.error("Błąd ładowania powiadomień:", error));
    }

    function markAsRead(notificationId) {
        fetch(`/api/user/mark_notification_read.php?id=${notificationId}`, { method: "POST" })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                } else {
                    alert("Błąd: " + data.error);
                }
            })
            .catch(error => console.error("Błąd oznaczania powiadomienia:", error));
    }

    function deleteNotification(notificationId) {
        if (!confirm("Czy na pewno chcesz usunąć to powiadomienie?")) return;

        fetch(`/api/user/delete_notification.php?id=${notificationId}`, { method: "POST" })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                } else {
                    alert("Błąd: " + data.error);
                }
            })
            .catch(error => console.error("Błąd usuwania powiadomienia:", error));
    }

    loadNotifications();
});
</script>
