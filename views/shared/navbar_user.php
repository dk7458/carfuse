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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="public/user/dashboard.php#profile" style="height: 70px;">Profil</a></li>
                    <li class="nav-item"><a class="nav-link" href="/public/logout.php" style="height: 70px;">Wyloguj</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/public/login.php" style="height: 70px;">Zaloguj</a></li>
                    <li class="nav-item"><a class="nav-link" href="/public/register.php" style="height: 70px;">Zarejestruj</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
