<?php

namespace App\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
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
     * Generate Report for Admin using Eloquent ORM.
     */
    public function generateReport()
    {
        try {
            // Replace Request validation with native PHP validation
            $validated = $_POST; // Assumes JSON-decoded input or form data

            $start      = $validated['date_range']['start'] ?? null;
            $end        = $validated['date_range']['end'] ?? null;
            $format     = $validated['format'] ?? null;
            $reportType = $validated['report_type'] ?? null;
            $userId     = $_SESSION['user_id'] ?? null;

            if (!$start || !$end || !$format || !$reportType) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
                exit;
            }

            switch ($reportType) {
                case 'bookings':
                    $data = Booking::with(['user', 'vehicle'])
                        ->whereBetween('created_at', [$start, $end])
                        ->get()
                        ->toArray();
                    break;
                case 'payments':
                    $data = Payment::whereBetween('created_at', [$start, $end])
                        ->get()
                        ->toArray();
                    break;
                case 'users':
                    $data = User::whereBetween('created_at', [$start, $end])
                        ->get()
                        ->toArray();
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['status' => 'error', 'message' => 'Invalid report type']);
                    exit;
            }
            
            // Log report generation in audit logs
            $this->auditService->logEvent(
                'report_generated',
                "Admin generated {$reportType} report",
                [
                    'report_type' => $reportType,
                    'format' => $format,
                    'date_range' => ['start' => $start, 'end' => $end]
                ],
                $userId,
                null,
                'report'
            );

            $filename = "{$reportType}_report_" . date('YmdHis');
            if ($format === 'csv') {
                // Assuming Excel::download now returns file content in native PHP
                return Excel::download(new \App\Exports\ReportExport($data), $filename . ".csv");
            } elseif ($format === 'pdf') {
                $pdf = PDF::loadView('reports.template', ['data' => $data]);
                return $pdf->download($filename . ".pdf");
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Unsupported format']);
                exit;
            }
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
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
     * Generate Report for a User using Eloquent ORM.
     */
    public function generateUserReport()
    {
        try {
            $validated = $_POST;
            $userId     = $validated['user_id'] ?? null;
            $start      = $validated['date_range']['start'] ?? null;
            $end        = $validated['date_range']['end'] ?? null;
            $format     = $validated['format'] ?? null;
            $reportType = $validated['report_type'] ?? null;

            if (!$userId || !$start || !$end || !$format || !$reportType) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
                exit;
            }

            switch ($reportType) {
                case 'bookings':
                    $data = Booking::with(['user', 'vehicle'])
                        ->where('user_reference', $userId)
                        ->whereBetween('created_at', [$start, $end])
                        ->get()
                        ->toArray();
                    break;
                case 'payments':
                    $data = Payment::where('user_id', $userId)
                        ->whereBetween('created_at', [$start, $end])
                        ->get()
                        ->toArray();
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['status' => 'error', 'message' => 'Invalid report type']);
                    exit;
            }

            $filename = "user_{$userId}_{$reportType}_report_" . date('YmdHis');
            if ($format === 'csv') {
                return Excel::download(new \App\Exports\ReportExport($data), $filename . ".csv");
            } elseif ($format === 'pdf') {
                $pdf = PDF::loadView('reports.template', ['data' => $data]);
                return $pdf->download($filename . ".pdf");
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Unsupported format']);
                exit;
            }
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
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
