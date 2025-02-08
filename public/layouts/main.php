<?php
/*
|--------------------------------------------------------------------------
| Global Layout Wrapper - main.php
|--------------------------------------------------------------------------
| This wrapper ensures that every view is displayed inside a structured
| layout with a header and footer.
|
| Path: public/layouts/main.php
*/

require_once __DIR__ . '/../layouts/header.php';
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/public/css/dashboard.css">
    <script src="/public/js/dashboard.js" defer></script>
</head>
<body>

<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="dashboard-container">
    <aside class="sidebar">
        <?php
        if ($userRole === 'admin') {
            include __DIR__ . '/../layouts/sidebars/admin_sidebar.php';
        } else {
            include __DIR__ . '/../layouts/sidebars/user_sidebar.php';
        }
        ?>
    </aside>
    <main class="main-content">
        <?php include $view; ?>
    </main>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

</body>
</html>
