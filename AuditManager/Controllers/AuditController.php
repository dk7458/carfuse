<?php

namespace AuditManager\Controllers;

use AuditManager\Services\AuditService;
use Psr\Log\LoggerInterface;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

class AuditController
{
    private AuditService $auditService;
    private LoggerInterface $logger;

    public function __construct(AuditService $auditService, LoggerInterface $logger)
    {
        $this->auditService = $auditService;
        $this->logger = $logger;
    }

    /**
     * Render the audit log view for the admin.
     */
    public function index()
    {
        try {
            // Default view parameters
            $filters = $_GET ?? [];
            $logs = $this->auditService->getLogs($filters);

            view('admin/audit_logs', ['logs' => $logs]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load audit logs', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo 'Failed to load audit logs.';
        }
    }

    /**
     * API Endpoint: Fetch logs based on filters.
     * 
     * @param array $filters - Filters passed via GET or POST request.
     */
    public function fetchLogs(array $filters = []): void
    {
        try {
            $logs = $this->auditService->getLogs($filters);

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'logs' => $logs]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch logs', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch logs.']);
        }
    }
}
