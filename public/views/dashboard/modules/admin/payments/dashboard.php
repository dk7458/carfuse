<?php
require_once __DIR__ . '/../../../helpers/SecurityHelper.php';

if (!isUserLoggedIn() || getUserRole() !== 'admin') {
    header("Location: /admin/login");
    exit();
}
?>

<div class="payments-dashboard">
    <?php include __DIR__ . '/../../../admin/payments/dashboard.php'; ?>
</div>
