<?php
require_once __DIR__ . '/../../../helpers/SecurityHelper.php';

if (!isUserLoggedIn() || getUserRole() !== 'admin') {
    header("Location: /admin/login");
    exit();
}
?>

<div class="transactions-container">
    <?php include __DIR__ . '/../../../admin/transactions.php'; ?>
</div>
