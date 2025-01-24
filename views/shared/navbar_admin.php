<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once '/home/u122931475/domains/carfuse.pl/public_html/includes/session_middleware.php';
$userRole = $_SESSION['user_role'] ?? null;
?>

<nav class="navbar navbar-expand-lg" style="background-color: #e8e8e8; height: 70px; color: black;">
    <div class="container">
        <a class="navbar-brand" href="/public/index.php">
            <img src="/public/logo.png" alt="Carfuse Logo" style="height: 35px;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" style="height: 70px;">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if ($_SESSION['user_role'] === 'super_admin'): ?>
                    <li class="nav-item"><a class="nav-link" href="/views/super_admin/panel.php">Super Admin Panel</a></li>
                    <?php endif; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="/views/admin/dashboard.php" style="height: 70px;">Panel Administratora</a></li>
                    <li class="nav-item"><a class="nav-link" href="/views/admin/admin_calendar.php" style="height: 70px;">Kalendarz</a></li>
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-bell"></i>
        <span id="unreadCount" class="badge bg-danger"></span>
    </a>
    <ul class="dropdown-menu" aria-labelledby="notificationsDropdown">
        <div id="notificationsList" class="p-2"></div>
    </ul>
</li>

<script>
    function fetchNotifications() {
        fetch('/home/u122931475/domains/carfuse.pl/public_html/controllers/notification_ctrl.php?action=fetch_unread')
            .then(response => response.json())
            .then(data => {
                const unreadCount = document.getElementById('unreadCount');
                const notificationsList = document.getElementById('notificationsList');

                unreadCount.textContent = data.unread_count || '';
                notificationsList.innerHTML = '';

                if (data.notifications && data.notifications.length) {
                    data.notifications.forEach(notif => {
                        const notifItem = document.createElement('li');
                        notifItem.classList.add('dropdown-item');
                        notifItem.textContent = notif.message;
                        notificationsList.appendChild(notifItem);
                    });
                } else {
                    notificationsList.innerHTML = '<p class="text-center">No notifications</p>';
                }
            });
    }

    // Fetch notifications on load
    fetchNotifications();

    // Poll every 30 seconds
    setInterval(fetchNotifications, 30000);
</script>

                    <!-- Show additional options for super_admin -->
                    <?php if ($userRole === 'super_admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="/views/admin/settings.php" style="height: 70px;">Ustawienia</a></li>
                        <li class="nav-item"><a class="nav-link" href="/views/admin/logs_manager.php" style="height: 70px;">Logi</a></li>
                    <?php endif; ?>

                    <li class="nav-item"><a class="nav-link" href="/public/logout.php" style="height: 70px;">Wyloguj</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/public/login.php" style="height: 70px;">Zaloguj</a></li>
                    <li class="nav-item"><a class="nav-link" href="/public/register.php" style="height: 70px;">Zarejestruj</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
