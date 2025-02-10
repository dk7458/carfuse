<?php

namespace App\Controllers;

use App\Services\ReportService;
use App\Services\Validator;
use App\Services\NotificationService;
use Psr\Log\LoggerInterface;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

class ReportController
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
     * Generate Report for Admin
     */
    public function generateReport(array $data): array
    {
        $rules = [
            'report_type' => 'required|in:bookings,payments,users',
            'date_range' => 'required|array',
            'date_range.start' => 'required|date',
            'date_range.end' => 'required|date',
            'filters' => 'array',
            'format' => 'required|in:csv,pdf',
        ];

        if (!$this->validator->validate($data, $rules)) {
            http_response_code(400);
            echo json_encode(['status' => 'error','message' => 'Validation failed','data' => $this->validator->errors()]);
            exit;
        }

        try {
            $report = $this->reportService->generateReport(
                $data['report_type'],
                $data['date_range'],
                $data['filters'] ?? [],
                $data['format']
            );
            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'Report generated','data' => ['report' => $report]]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Failed to generate report', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Failed to generate report','data' => []]);
        }
        exit;
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
     * Generate Report for Users
     */
    public function generateUserReport(array $data): array
    {
        $rules = [
            'user_id' => 'required|integer',
            'report_type' => 'required|in:bookings,payments',
            'date_range' => 'required|array',
            'date_range.start' => 'required|date',
            'date_range.end' => 'required|date',
            'format' => 'required|in:csv,pdf',
        ];

        if (!$this->validator->validate($data, $rules)) {
            http_response_code(400);
            echo json_encode(['status' => 'error','message' => 'Validation failed','data' => $this->validator->errors()]);
            exit;
        }

        try {
            $report = $this->reportService->generateUserReport(
                $data['user_id'],
                $data['report_type'],
                $data['date_range'],
                $data['format']
            );
            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'User report generated','data' => ['report' => $report]]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            $this->logger->error('Failed to generate user report', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Failed to generate report','data' => []]);
        }
        exit;
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
