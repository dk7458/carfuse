<?php

namespace App\Controllers;

use App\Services\ReportService;
use App\Services\NotificationService;
use App\Services\AuditService;
use App\Helpers\ExceptionHandler;
use Psr\Log\LoggerInterface;

class ReportController extends Controller
{
    private ReportService $reportService;
    private NotificationService $notificationService;
    protected LoggerInterface $logger;
    protected ExceptionHandler $exceptionHandler;
    private AuditService $auditService;

    public function __construct(
        LoggerInterface $logger,
        ReportService $reportService,
        NotificationService $notificationService,
        ExceptionHandler $exceptionHandler,
        AuditService $auditService
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->reportService = $reportService;
        $this->notificationService = $notificationService;
        $this->exceptionHandler = $exceptionHandler;
        $this->auditService = $auditService;
    }

    /**
     * Admin Report Dashboard View
     */
    public function index()
    {
        try {
            // Log the dashboard access in audit logs
            $this->auditService->logEvent(
                'report_dashboard_accessed',
                'Admin report dashboard accessed',
                ['user_id' => $_SESSION['user_id'] ?? 'unknown'],
                $_SESSION['user_id'] ?? null,
                null,
                'report'
            );
            
            $data = ['view' => 'admin/reports'];
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Report dashboard loaded', 'data' => $data]);
            exit;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
        }
    }

    /**
     * Generate Report for Admin
     */
    public function generateReport()
    {
        try {
            // Parse request data
            $validated = $_POST;
            
            $dateRange = [
                'start' => $validated['date_range']['start'] ?? null,
                'end' => $validated['date_range']['end'] ?? null
            ];
            $format = $validated['format'] ?? null;
            $reportType = $validated['report_type'] ?? null;
            $filters = $validated['filters'] ?? [];
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$dateRange['start'] || !$dateRange['end'] || !$format || !$reportType) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
                exit;
            }
            
            // Log report generation in audit logs
            $this->auditService->logEvent(
                'report_generated',
                "Admin generated {$reportType} report",
                [
                    'report_type' => $reportType,
                    'format' => $format,
                    'date_range' => $dateRange
                ],
                $userId,
                null,
                'report'
            );
            
            // Generate report using service
            $reportPath = $this->reportService->generateReport($reportType, $dateRange, $format, $filters);
            
            // Return the report
            return $this->downloadReport($reportPath);
            
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * User Report Dashboard View
     */
    public function userReports()
    {
        try {
            $data = ['view' => 'user/reports'];
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'User report dashboard loaded', 'data' => $data]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
        }
    }

    /**
     * Generate Report for a User
     */
    public function generateUserReport()
    {
        try {
            $validated = $_POST;
            $userId = $validated['user_id'] ?? null;
            $dateRange = [
                'start' => $validated['date_range']['start'] ?? null,
                'end' => $validated['date_range']['end'] ?? null
            ];
            $format = $validated['format'] ?? null;
            $reportType = $validated['report_type'] ?? null;

            if (!$userId || !$dateRange['start'] || !$dateRange['end'] || !$format || !$reportType) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
                exit;
            }
            
            // Generate user-specific report
            $reportPath = $this->reportService->generateUserReport($userId, $reportType, $dateRange, $format);
            
            // Return the report
            return $this->downloadReport($reportPath);
            
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * Download a Report
     */
    public function downloadReport(string $filePath): void
    {
        try {
            if (!file_exists($filePath)) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Report not found', 'data' => []]);
                return;
            }

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($filePath));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
        }
    }
}
