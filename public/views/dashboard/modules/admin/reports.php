<?php
require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!isUserLoggedIn() || getUserRole() !== 'admin') {
    header("Location: /admin/login");
    exit();
}
?>

<div class="reports-container">
    <?php include __DIR__ . '/../../admin/reports.php'; ?>
</div>
