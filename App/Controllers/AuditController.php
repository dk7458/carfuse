<?php

namespace App\Controllers;

use App\Services\AuditService;
use Psr\Log\LoggerInterface;

/**
 * AuditController - Handles viewing and retrieving audit logs.
 */
class AuditController extends Controller
{
    protected LoggerInterface $logger;
    private AuditService $auditService;
    
    /**
     * Constructor with dependency injection
     */
    public function __construct(LoggerInterface $logger, AuditService $auditService)
    {
        parent::__construct($logger);
        $this->auditService = $auditService;
    }
    
    /**
     * ✅ Get audit logs data for admin dashboard
     */
    public function index()
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
            
            return $this->jsonResponse('success', ['logs' => $logs], 200);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * ✅ API Endpoint: Fetch logs based on filters.
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
            
            return $this->jsonResponse('success', ['logs' => $logs], 200);
        } catch (\Exception $e) {
            return $this->handleException($e);
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
            
            return $this->jsonResponse('success', ['log' => $log], 200);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    
    /**
     * Process and validate incoming filters
     */
    private function processFilters(array $rawFilters): array
    {
        $filters = [];
        
        // Category filter (unified log type)
        if (!empty($rawFilters['category'])) {
            $filters['category'] = $rawFilters['category'];
        }
        
        // Action filter (for backward compatibility)
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
        
        return $filters;
    }
    
    /**
     * Check if current user has admin access
     */
    private function hasAdminAccess(): bool
    {
        // Replace with your actual authentication logic
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Handle exceptions in a consistent way
     */
    private function handleException(\Exception $e)
    {
        // Log the exception
        error_log($e->getMessage());
        return $this->jsonResponse('error', 'An error occurred: ' . $e->getMessage(), 500);
    }
}
