<?php
require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!isUserLoggedIn()) {
    header("Location: /login");
    exit();
}
?>

<div class="payments-container">
    <?php include __DIR__ . '/../../user/payments.php'; ?>
</div>
