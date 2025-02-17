<?php

namespace App\Services;

use App\Helpers\DatabaseHelper; // new import
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Dompdf\Dompdf;
use Psr\Log\LoggerInterface;

class ReportService
{
    private $db;
    private LoggerInterface $logger;

    // Constructor updated to initialize DatabaseHelper and Logger.
    public function __construct(LoggerInterface $logger, DatabaseHelper $db)
    {
        $this->logger = $logger;
        $this->db = $db;
    }    

    /**
     * Generate a report for admin
     */
    public function generateReport(string $reportType, array $dateRange, string $format, array $filters = []): string
    {
        $start = $dateRange['start'];
        $end   = $dateRange['end'];
        $data = match ($reportType) {
            'bookings' => $this->getBookingReportData($dateRange, $filters),
            'payments' => $this->getPaymentReportData($dateRange, $filters),
            'users'    => $this->getUserReportData($dateRange, $filters),
            default    => throw new \InvalidArgumentException("Invalid report type: $reportType"),
        };
        return $this->exportReport($data, "{$reportType}_" . date('YmdHis'), $format);
    }

    /**
     * Generate a user-specific report
     */
    public function generateUserReport(int $userId, string $reportType, array $dateRange, string $format): string
    {
        $start = $dateRange['start'];
        $end   = $dateRange['end'];
        $data = match ($reportType) {
            'bookings' => Booking::with('user')
                         ->where('user_id', $userId)
                         ->whereBetween('created_at', [$start, $end])
                         ->get()
                         ->toArray(),
            'payments' => Payment::where('user_id', $userId)
                         ->whereBetween('created_at', [$start, $end])
                         ->get()
                         ->toArray(),
            default    => throw new \InvalidArgumentException("Invalid report type: $reportType"),
        };
        return $this->exportReport($data, "{$reportType}_user_{$userId}", $format);
    }

    /**
     * Fetch booking report data
     */
    private function getBookingReportData(array $dateRange, array $filters): array
    {
        try {
            $query = $this->db->table('bookings')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            $this->logger->info("[ReportService] Fetched booking report data", ['category' => 'report']);
            return $query->get();
        } catch (\Exception $e) {
            $this->logger->error("[ReportService] Database error (booking): " . $e->getMessage(), ['category' => 'db']);
            throw $e;
        }
    }

    /**
     * Fetch payment report data
     */
    private function getPaymentReportData(array $dateRange, array $filters): array
    {
        try {
            $query = $this->db->table('payments')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            $this->logger->info("[ReportService] Fetched payment report data");
            return $query->get();
        } catch (\Exception $e) {
            $this->logger->error("[ReportService] Database error (payments): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch user report data
     */
    private function getUserReportData(array $dateRange, array $filters): array
    {
        try {
            $data = $this->db->table('users')
                         ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                         ->get();
            $this->logger->info("[ReportService] Fetched user report data");
            return $data;
        } catch (\Exception $e) {
            $this->logger->error("[ReportService] Database error (users): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export the report data
     */
    private function exportReport(array $data, string $reportName, string $format): string
    {
        $filePath = __DIR__ . "/../../storage/reports/{$reportName}_" . date('YmdHis') . ".{$format}";

        if ($format === 'csv') {
            // Placeholder: In a Laravel app, you might use Maatwebsite\Excel here.
            $file = fopen($filePath, 'w');
            if (!empty($data)) {
                fputcsv($file, array_keys($data[0])); // headers
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
            }
            fclose($file);
        } elseif ($format === 'pdf') {
            // Placeholder: In a Laravel app, you might use Dompdf integration.
            $dompdf = new Dompdf();
            $html = '<table border="1"><tr>';
            if (!empty($data)) {
                foreach (array_keys($data[0]) as $header) {
                    $html .= "<th>$header</th>";
                }
                $html .= "</tr>";
                foreach ($data as $row) {
                    $html .= "<tr>";
                    foreach ($row as $cell) {
                        $html .= "<td>$cell</td>";
                    }
                    $html .= "</tr>";
                }
            }
            $html .= "</table>";
            $dompdf->loadHtml($html);
            $dompdf->render();
            file_put_contents($filePath, $dompdf->output());
        } else {
            throw new \InvalidArgumentException("Unsupported format: $format");
        }
        $this->logger->info("[ReportService] Exported report: {$filePath}");
        return $filePath;
    }
}
