<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userRole = $_SESSION['user_role'] ?? null;
?>
<nav class="navbar navbar-expand-lg" style="background-color: #e8e8e8;">
    <div class="container">
        <a class="navbar-brand" href="/public/index.php">
            <img src="/path/to/logo.png" alt="Carfuse Logo" style="height: 40px;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="/public/profile.php">Profil</a></li>
                    <li class="nav-item"><a class="nav-link" href="/public/logout.php">Wyloguj</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/public/login.php">Zaloguj</a></li>
                    <li class="nav-item"><a class="nav-link" href="/public/register.php">Zarejestruj</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
