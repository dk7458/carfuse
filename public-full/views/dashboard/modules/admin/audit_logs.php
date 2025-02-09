<?php
require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!isUserLoggedIn() || getUserRole() !== 'admin') {
    header("Location: /admin/login");
    exit();
}
?>

<div class="audit-logs-container">
    <?php include __DIR__ . '/../../admin/audit_logs.php'; ?>
</div>
