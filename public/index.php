<?php
declare(strict_types=1);
header("Content-Type: text/html; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php';
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfuse - Wynajmij auto szybko i łatwo</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="/public/css/home.css">
    <script src="/public/js/shared.js" defer></script>
</head>
<body>

<?php include __DIR__ . '/layouts/header.php'; ?>

<!-- Home view always loads -->
<div id="home-view">
    <?php include __DIR__ . '/home.php'; ?>
</div>

<?php include __DIR__ . '/layouts/footer.php'; ?>

</body>
</html>
