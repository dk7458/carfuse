<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <title><?= $title ?? 'CarFuse - Wypożyczalnia samochodów' ?></title>
    
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="/images/favicon/site.webmanifest">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link href="/css/tailwind.css" rel="stylesheet">
    <link href="/css/app.css" rel="stylesheet">
    <link href="/css/htmx.css" rel="stylesheet"> <!-- Standardized HTMX styles -->
    
    <?= isset($head) ? $head : '' ?>
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-100">
    <?php include BASE_PATH . '/public/views/partials/header.php'; ?>
    
    <div id="app" class="min-h-screen">
        <?php include BASE_PATH . '/public/views/partials/toast.php'; ?>
        
        <main>
            <?= $content ?? '' ?>
        </main>
    </div>
    
    <?php include BASE_PATH . '/public/views/partials/footer.php'; ?>
    
    <!-- Scripts - Load in correct order for dependencies -->
    <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
        <!-- Development version with source maps -->
        <script src="/js/htmx.js"></script>
    <?php else: ?>
        <!-- Production minified version -->
        <script src="/js/htmx.min.js"></script>
    <?php endif; ?>
    
    <!-- Load Alpine.js after HTMX to ensure proper integration -->
    <script src="/js/alpine.min.js" defer></script>
    
    <!-- Load auth helper after Alpine.js but before other components -->
    <script src="/js/auth-helper.js" defer></script>
    
    <!-- Load Alpine.js components initializer -->
    <script src="/js/main.js" defer></script>
    
    <!-- Custom page scripts -->
    <?= isset($scripts) ? $scripts : '' ?>
    
    <script>
        // Initialize debug mode for development environments
        document.addEventListener('DOMContentLoaded', function() {
            if (window.AuthHelper) {
                window.AuthHelper.setDebug(<?= defined('DEBUG_MODE') && DEBUG_MODE ? 'true' : 'false' ?>);
                window.AuthHelper.setRedirectPaths({
                    admin: '/admin/dashboard',
                    user: '/dashboard',
                    default: '/login'
                });
            }
        });
    </script>
</body>
</html>
