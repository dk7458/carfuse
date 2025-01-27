<?php

namespace App\Controllers;

use App\Services\ReportService;
use App\Services\Validator;
use App\Services\NotificationService;
use Psr\Log\LoggerInterface;

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
            // Render admin report dashboard
            require_once __DIR__ . '/../views/admin/reports.php';
        } catch (\Exception $e) {
            $this->logger->error('Failed to load admin report dashboard', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo 'Failed to load report dashboard.';
        }
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
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors()];
        }

        try {
            $report = $this->reportService->generateReport(
                $data['report_type'],
                $data['date_range'],
                $data['filters'] ?? [],
                $data['format']
            );

            return ['status' => 'success', 'report' => $report];
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate report', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to generate report'];
        }
    }

    /**
     * User Report Dashboard View
     */
    public function userReports()
    {
        try {
            // Render user report dashboard
            require_once __DIR__ . '/../views/user/reports.php';
        } catch (\Exception $e) {
            $this->logger->error('Failed to load user report dashboard', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo 'Failed to load report dashboard.';
        }
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
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors()];
        }

        try {
            $report = $this->reportService->generateUserReport(
                $data['user_id'],
                $data['report_type'],
                $data['date_range'],
                $data['format']
            );

            return ['status' => 'success', 'report' => $report];
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate user report', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to generate report'];
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
                echo 'Report not found.';
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
            $this->logger->error('Failed to download report', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo 'Failed to download report.';
        }
    }
}
