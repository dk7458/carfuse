<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarFuse - Dashboard</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php include __DIR__ . '/layouts/navbar.php'; ?>

<div class="dashboard-container">
    <div id="dashboard-view">
        <!-- Dashboard content will be loaded here -->
    </div>
</div>

<?php include __DIR__ . '/layouts/footer.php'; ?>
<script src="/js/shared.js"></script>
<script src="/js/main.js"></script>
</body>
</html>
<?php
// ...existing code...
$page = 'dashboard';
// ...existing code...
?>