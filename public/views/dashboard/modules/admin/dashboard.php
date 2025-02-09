<?php
require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!isUserLoggedIn() || getUserRole() !== 'admin') {
    header("Location: /admin/login");
    exit();
}
?>

<div class="dashboard-overview">
    <?php include __DIR__ . '/../../admin/dashboard.php'; ?>
</div>
