<?php
require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!isUserLoggedIn() || getUserRole() !== 'admin') {
    header("Location: /admin/login");
    exit();
}
?>

<div class="logs-container">
    <?php include __DIR__ . '/../../admin/logs.php'; ?>
</div>
