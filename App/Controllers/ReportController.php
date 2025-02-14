<?php

namespace App\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

class ReportController extends Controller
{
    private ReportService $reportService;
    private Validator $validator;
    private NotificationService $notificationService;
    private LoggerInterface $logger;

    public function __construct(
        ReportService $reportService,
        Validator $validator,
        NotificationService $notificationService,
        LoggerInterface $logger
    ) {
        $this->reportService = $reportService;
        $this->validator = $validator;
        $this->notificationService = $notificationService;
        $this->logger = $logger;
    }

    /**
     * Admin Report Dashboard View
     */
    public function index()
    {
        try {
            $data = ['view' => 'admin/reports'];
            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'Report dashboard loaded','data' => $data]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Failed to load admin report dashboard', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Failed to load report dashboard','data' => []]);
        }
        exit;
    }

    /**
     * Generate Report for Admin using Eloquent ORM.
     */
    public function generateReport(Request $request)
    {
        $validated = $request->validate([
            'report_type'          => 'required|in:bookings,payments,users',
            'date_range.start'     => 'required|date',
            'date_range.end'       => 'required|date',
            'filters'              => 'array',
            'format'               => 'required|in:csv,pdf',
        ]);

        $start = $validated['date_range']['start'];
        $end = $validated['date_range']['end'];
        $format = $validated['format'];
        $reportType = $validated['report_type'];

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
                return response()->json(['status' => 'error', 'message' => 'Invalid report type'], 400);
        }

        $filename = "{$reportType}_report_" . date('YmdHis');
        if ($format === 'csv') {
            // Assumes ReportExport implements necessary interfaces (e.g., FromArray)
            return Excel::download(new \App\Exports\ReportExport($data), $filename . ".csv");
        } elseif ($format === 'pdf') {
            $pdf = PDF::loadView('reports.template', ['data' => $data]);
            return $pdf->download($filename . ".pdf");
        } else {
            return response()->json(['status' => 'error', 'message' => 'Unsupported format'], 400);
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
            echo json_encode(['status' => 'success','message' => 'User report dashboard loaded','data' => $data]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Failed to load user report dashboard', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Failed to load report dashboard','data' => []]);
        }
        exit;
    }

    /**
     * Generate Report for a User using Eloquent ORM.
     */
    public function generateUserReport(Request $request)
    {
        $validated = $request->validate([
            'user_id'              => 'required|integer',
            'report_type'          => 'required|in:bookings,payments',
            'date_range.start'     => 'required|date',
            'date_range.end'       => 'required|date',
            'format'               => 'required|in:csv,pdf',
        ]);

        $userId = $validated['user_id'];
        $start  = $validated['date_range']['start'];
        $end    = $validated['date_range']['end'];
        $format = $validated['format'];
        $reportType = $validated['report_type'];

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
                return response()->json(['status' => 'error', 'message' => 'Invalid report type'], 400);
        }

        $filename = "user_{$userId}_{$reportType}_report_" . date('YmdHis');
        if ($format === 'csv') {
            return Excel::download(new \App\Exports\ReportExport($data), $filename . ".csv");
        } elseif ($format === 'pdf') {
            $pdf = PDF::loadView('reports.template', ['data' => $data]);
            return $pdf->download($filename . ".pdf");
        } else {
            return response()->json(['status' => 'error', 'message' => 'Unsupported format'], 400);
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
                echo json_encode(['status' => 'error','message' => 'Report not found','data' => []]);
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
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Failed to download report', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Failed to download report','data' => []]);
        }
    }
}
