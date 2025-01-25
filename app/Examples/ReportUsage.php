<?php

use App\Services\ReportService;
use Carbon\Carbon;

$reportService = new ReportService();

// Generate monthly revenue report
$startDate = Carbon::now()->startOfMonth();
$endDate = Carbon::now()->endOfMonth();

$report = $reportService->generateReport(
    'revenue',
    'monthly',
    $startDate,
    $endDate,
    ['include_tax' => true]
);

if ($report['success']) {
    // Export report as CSV
    try {
        $csvPath = $reportService->exportReport($report['report'], 'csv');
        echo "Report exported to: $csvPath\n";

        // Send report via email
        $emailOptions = [
            'to' => 'finance@example.com',
            'subject' => 'Monthly Revenue Report',
        ];
        
        if ($reportService->sendReport($csvPath, 'email', $emailOptions)) {
            echo "Report sent successfully\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Report generation failed: " . $report['error'] . "\n";
}
