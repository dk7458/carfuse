<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php'; // Bootstrap application
require_once __DIR__ . '/../vendor/autoload.php'; // Load dependencies
require_once __DIR__ . '/../App/Helpers/SecurityHelper.php'; // Load security functions globally

header("Content-Type: text/html; charset=UTF-8");
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carfuse - Wynajmij auto szybko i łatwo</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="stylesheet" href="/public/css/home.css"> <!-- Link to home.css -->
    <script src="/public/js/main.js" defer></script>
</head>
<body>

<?php include __DIR__ . '/layouts/header.php'; ?>

<?php include __DIR__ . '/home.php'; ?>

<?php include __DIR__ . '/layouts/footer.php'; ?>

<!-- Place shared.js before closing body tag -->
<script src="js/shared.js" defer></script>

</body>
</html>
