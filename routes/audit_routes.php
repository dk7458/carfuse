<?php

$router->get('admin/audit-logs', [AuditManager\Controllers\AuditController::class, 'index']);
$router->post('admin/audit-logs/fetch', [AuditManager\Controllers\AuditController::class, 'fetchLogs']);
$router->post('admin/audit-logs/log', [AuditManager\Controllers\AuditController::class, 'logAction']); // Corrected method name

return $router;
