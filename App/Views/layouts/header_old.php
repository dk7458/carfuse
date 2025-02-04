<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/public/css/admin.css">
    <link rel="stylesheet" href="/path/to/your/css/styles.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="navbar-brand">
                <a href="/"><img src="/path/to/branding/logo.png" alt="Brand Logo"></a>
            </div>
            <ul class="navbar-menu">
                <li class="navbar-item <?php echo ($activePage == 'home') ? 'active' : ''; ?>">
                    <a href="/">Home</a>
                </li>
                <li class="navbar-item <?php echo ($activePage == 'documents') ? 'active' : ''; ?>">
                    <a href="/documents">Documents</a>
                </li>
                <li class="navbar-item <?php echo ($activePage == 'about') ? 'active' : ''; ?>">
                    <a href="/about">About</a>
                </li>
                <li><a href="/dashboard">Dashboard</a></li>
                <li><a href="/logs">Logs</a></li>
                <li><a href="/reports">Reports</a></li>
            </ul>
            <div class="navbar-profile">
                <?php if (isset($user)): ?>
                    <div class="profile-dropdown">
                        <button class="profile-button"><?php echo htmlspecialchars($user['name']); ?></button>
                        <div class="profile-menu">
                            <a href="/profile">Profile</a>
                            <a href="/logout">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/login" class="login-button">Login</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <!-- ...existing code... -->
</body>
</html>
