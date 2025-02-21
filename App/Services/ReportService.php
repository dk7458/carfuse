<?php

namespace App\Services;

use App\Helpers\DatabaseHelper;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Dompdf\Dompdf;
use Psr\Log\LoggerInterface;
use App\Handlers\ExceptionHandler;

class ReportService
{
    public const DEBUG_MODE = true;
    private DatabaseHelper $db;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;

    public function __construct(LoggerInterface $logger, DatabaseHelper $db, ExceptionHandler $exceptionHandler)
    {
        $this->logger = $logger;
        $this->db = $db;
        $this->exceptionHandler = $exceptionHandler;
    }    

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

    private function getBookingReportData(array $dateRange, array $filters): array
    {
        try {
            $query = $this->db->table('bookings')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (self::DEBUG_MODE) {
                $this->logger->info("[db] Fetched booking report data", ['category' => 'report']);
            }
            return $query->get();
        } catch (\Exception $e) {
            $this->logger->error("[db] Database error (booking): " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    private function getPaymentReportData(array $dateRange, array $filters): array
    {
        try {
            $query = $this->db->table('payments')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            if (self::DEBUG_MODE) {
                $this->logger->info("[db] Fetched payment report data", ['category' => 'report']);
            }
            return $query->get();
        } catch (\Exception $e) {
            $this->logger->error("[db] Database error (payments): " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    private function getUserReportData(array $dateRange, array $filters): array
    {
        try {
            $data = $this->db->table('users')
                         ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                         ->get();
            if (self::DEBUG_MODE) {
                $this->logger->info("[db] Fetched user report data");
            }
            return $data;
        } catch (\Exception $e) {
            $this->logger->error("[db] Database error (users): " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    private function exportReport(array $data, string $reportName, string $format): string
    {
        $filePath = __DIR__ . "/../../storage/reports/{$reportName}_" . date('YmdHis') . ".{$format}";

        if ($format === 'csv') {
            $file = fopen($filePath, 'w');
            if (!empty($data)) {
                fputcsv($file, array_keys($data[0])); // headers
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
            }
            fclose($file);
        } elseif ($format === 'pdf') {
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
        if (self::DEBUG_MODE) {
            $this->logger->info("[system] Exported report: {$filePath}");
        }
        return $filePath;
    }
}
