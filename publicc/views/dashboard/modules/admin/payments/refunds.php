<?php
require_once __DIR__ . '/../../../helpers/SecurityHelper.php';

if (!isUserLoggedIn() || getUserRole() !== 'admin') {
    header("Location: /admin/login");
    exit();
}
?>

<div class="refunds-container">
    <?php include __DIR__ . '/../../../admin/refunds.php'; ?>
</div>
