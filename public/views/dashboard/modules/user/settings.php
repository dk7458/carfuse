<?php
require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!isUserLoggedIn()) {
    header("Location: /login");
    exit();
}
?>

<div class="settings-container">
    <?php include __DIR__ . '/../../user/settings.php'; ?>
</div>
