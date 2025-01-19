<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userRole = $_SESSION['user_role'] ?? null;
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background-color: #e8e8e8; color: black;">
    <div class="container">
        <a class="navbar-brand" href="/public/index.php">
            <img src="/public/logo.png" alt="Carfuse Logo" style="height: 35px;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" style="height: 70px;">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="/public/index.php#features" style="height: 70px;">Usługi</a></li>
                <li class="nav-item"><a class="nav-link" href="/public/index.php#book-now" style="height: 70px;">Rezerwacja</a></li>
                <li class="nav-item"><a class="nav-link" href="/public/index.php#about" style="height: 70px;">O nas</a></li>
                <li class="nav-item"><a class="nav-link" href="/public/index.php#contact" style="height: 70px;">Kontakt</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="/public/profile.php" style="height: 70px;">Profil</a></li>
                    <?php if ($userRole === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="/public/admin/dashboard.php" style="height: 70px;">Panel Administratora</a></li>
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
