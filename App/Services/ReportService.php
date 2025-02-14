<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Dompdf\Dompdf;

class ReportService
{
    // Removed PDO dependency and constructor

    /**
     * Generate a report for admin
     */
    public function generateReport(string $reportType, array $dateRange, array $filters = [], string $format): string
    {
        $start = $dateRange['start'];
        $end   = $dateRange['end'];
        $data = match ($reportType) {
            'bookings' => $this->getBookingReportData($dateRange, $filters),
            'payments' => $this->getPaymentReportData($dateRange, $filters),
            'users'    => $this->getUserReportData($dateRange, $filters),
            default    => throw new \InvalidArgumentException("Invalid report type: $reportType"),
        };
        return $this->exportReport($data, $reportType, $format);
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
        $query = Booking::with('user')->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        if(!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query->get()->toArray();
    }

    /**
     * Fetch payment report data
     */
    private function getPaymentReportData(array $dateRange, array $filters): array
    {
        $query = Payment::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        if(!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        return $query->get()->toArray();
    }

    /**
     * Fetch user report data
     */
    private function getUserReportData(array $dateRange, array $filters): array
    {
        return User::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->get()->toArray();
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
        return $filePath;
    }
}
