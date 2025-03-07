<?php

namespace App\Controllers;

use App\Services\AuditService;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;

/**
 * AuditController - Handles viewing and retrieving audit logs.
 * Follows the clean controller pattern by delegating all DB operations
 * to the AuditService.
 */
class AuditController extends Controller
{
    protected LoggerInterface $logger;
    private AuditService $auditService;
    protected ExceptionHandler $exceptionHandler;
    
    /**
     * Constructor with dependency injection
     */
    public function __construct(
        LoggerInterface $logger, 
        AuditService $auditService,
        ExceptionHandler $exceptionHandler
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->auditService = $auditService;
        $this->exceptionHandler = $exceptionHandler;
    }
    
    /**
     * Admin dashboard view for audit logs
     */
    public function index()
    {
        try {
            // Check if user has admin role
            if (!$this->hasAdminAccess()) {
                return $this->jsonResponse('error', 'Admin access required', 403);
            }
            
            // Process filters from request
            $filters = $this->processFilters($_GET);
            
            // Get logs using the audit service
            $logs = $this->auditService->getLogs($filters);
            
            return $this->jsonResponse('success', $logs, 200);
        } catch (\Exception $e) {
            $this->logger->error('Error in audit log retrieval: ' . $e->getMessage(), [
                'controller' => 'AuditController',
                'method' => 'index'
            ]);
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', 'Failed to retrieve audit logs', 500);
        }
    }

    /**
     * API Endpoint: Fetch logs based on filters
     */
    public function fetchLogs()
    {
        try {
            // Check if user has admin role
            if (!$this->hasAdminAccess()) {
                return $this->jsonResponse('error', 'Admin access required', 403);
            }
            
            // Process filters from request
            $filters = $this->processFilters($_POST);
            
            // Get logs using the audit service
            $logs = $this->auditService->getLogs($filters);
            
            return $this->jsonResponse('success', $logs, 200);
        } catch (\Exception $e) {
            $this->logger->error('Error in API log retrieval: ' . $e->getMessage(), [
                'controller' => 'AuditController',
                'method' => 'fetchLogs'
            ]);
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', 'Failed to fetch logs', 500);
        }
    }
    
    /**
     * API Endpoint: Get log details by ID
     */
    public function getLog($id)
    {
        try {
            // Check if user has admin role
            if (!$this->hasAdminAccess()) {
                return $this->jsonResponse('error', 'Admin access required', 403);
            }
            
            $log = $this->auditService->getLogById((int)$id);
            
            if (!$log) {
                return $this->jsonResponse('error', 'Log not found', 404);
            }
            
            return $this->jsonResponse('success', ['log' => $log], 200);
        } catch (\Exception $e) {
            $this->logger->error('Error retrieving log details: ' . $e->getMessage(), [
                'controller' => 'AuditController',
                'method' => 'getLog',
                'id' => $id
            ]);
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', 'Failed to retrieve log', 500);
        }
    }
    
    /**
     * API Endpoint: Export logs based on filters
     */
    public function exportLogs()
    {
        try {
            // Check if user has admin role
            if (!$this->hasAdminAccess()) {
                return $this->jsonResponse('error', 'Admin access required', 403);
            }
            
            // Process filters from request
            $filters = $this->processFilters($_POST);
            
            // Get export info from audit service
            $exportInfo = $this->auditService->exportLogs($filters);
            
            return $this->jsonResponse('success', [
                'export' => $exportInfo
            ], 200);
        } catch (\Exception $e) {
            $this->logger->error('Error exporting logs: ' . $e->getMessage(), [
                'controller' => 'AuditController',
                'method' => 'exportLogs'
            ]);
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse('error', 'Failed to export logs', 500);
        }
    }
    
    /**
     * Process and validate incoming filters
     */
    private function processFilters(array $rawFilters): array
    {
        $filters = [];
        
        // Category filter
        if (!empty($rawFilters['category'])) {
            $filters['category'] = $rawFilters['category'];
        }
        
        // Action filter
        if (!empty($rawFilters['action'])) {
            $filters['action'] = $rawFilters['action'];
        }
        
        // User ID filter
        if (!empty($rawFilters['user_id'])) {
            $filters['user_id'] = (int)$rawFilters['user_id'];
        }
        
        // Booking ID filter
        if (!empty($rawFilters['booking_id'])) {
            $filters['booking_id'] = (int)$rawFilters['booking_id'];
        }
        
        // Date range filters
        if (!empty($rawFilters['start_date'])) {
            $filters['start_date'] = $rawFilters['start_date'];
        }
        
        if (!empty($rawFilters['end_date'])) {
            $filters['end_date'] = $rawFilters['end_date'];
        }
        
        // Pagination
        if (isset($rawFilters['page'])) {
            $filters['page'] = max(1, (int)$rawFilters['page']);
        }
        
        if (isset($rawFilters['per_page'])) {
            $filters['per_page'] = min(100, max(10, (int)$rawFilters['per_page']));
        }
        
        // Log level
        if (!empty($rawFilters['log_level'])) {
            $filters['log_level'] = $rawFilters['log_level'];
        }
        
        return $filters;
    }
    
    /**
     * Check if current user has admin access
     */
    private function hasAdminAccess(): bool
    {
        // Get configuration for allowed roles
        $config = require __DIR__ . '/../../config/audit.php';
        $allowedRoles = $config['access']['allowed_roles'] ?? ['admin'];
        
        // Check if user role is in allowed roles
        $userRole = $_SESSION['user_role'] ?? '';
        return in_array($userRole, $allowedRoles, true);
    }
}
