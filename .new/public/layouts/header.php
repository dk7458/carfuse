<?php
if (!isset($_SESSION)) {
    session_start();
}

// Prevent duplicate header includes
if (!empty($_SESSION['layout_loaded'])) {
    return;
}
$_SESSION['layout_loaded'] = true;

// User session variables
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$username = $isLoggedIn ? $_SESSION['username'] : "Gość";
?>

<header>
    <nav class="navbar">
        <div class="container">
            <a href="/" class="logo">🚗 CarFuse</a>
            <ul class="nav-links">
                <?php if (!$isLoggedIn): ?>
                    <li><a href="/auth/login">🔑 Zaloguj się</a></li>
                    <li><a href="/auth/register">📝 Zarejestruj się</a></li>
                <?php else: ?>
                    <li class="greeting">Witaj, <?= $username ?>!</li>
                    <?php if ($isAdmin): ?>
                        <li><a href="/dashboard">⚙️ Panel Admina</a></li>
                    <?php else: ?>
                        <li><a href="/dashboard">📊 Panel Użytkownika</a></li>
                    <?php endif; ?>
                    <li><a href="/logout">🚪 Wyloguj się</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</header>
