<?php

namespace App\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use PDF;
use Psr\Log\LoggerInterface;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

class ReportController extends Controller
{
    private ReportService $reportService;
    private NotificationService $notificationService;
    protected LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        ReportService $reportService,
        NotificationService $notificationService,
    ) {
        parent::__construct($logger);
        $this->reportService = $reportService;
        $this->notificationService = $notificationService;
    }

    /**
     * Admin Report Dashboard View
     */
    public function index()
    {
        try {
            $data = ['view' => 'admin/reports'];
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Report dashboard loaded', 'data' => $data]);
        } catch (\Exception $e) {
            $this->logger->error(date('Y-m-d H:i:s') . ' ' . $e->getMessage());
            $this->logger->error("Error: Failed to load admin report dashboard, error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to load report dashboard', 'data' => []]);
        }
        exit;
    }

    /**
     * Generate Report for Admin using Eloquent ORM.
     */
    public function generateReport()
    {
        // Replace Request validation with native PHP validation
        $validated = $_POST; // Assumes JSON-decoded input or form data

        $start      = $validated['date_range']['start'] ?? null;
        $end        = $validated['date_range']['end'] ?? null;
        $format     = $validated['format'] ?? null;
        $reportType = $validated['report_type'] ?? null;

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
            $this->logger->error(date('Y-m-d H:i:s') . ' ' . $e->getMessage());
            $this->logger->error("Error: Failed to load user report dashboard, error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to load report dashboard', 'data' => []]);
        }
        exit;
    }

    /**
     * Generate Report for a User using Eloquent ORM.
     */
    public function generateUserReport()
    {
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
            $this->logger->error(date('Y-m-d H:i:s') . ' ' . $e->getMessage());
            $this->logger->error("Error: Failed to download report, error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to download report', 'data' => []]);
        }
    }
}
