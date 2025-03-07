<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Dompdf\Dompdf;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;

class ReportService
{
    public const DEBUG_MODE = true;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private Booking $bookingModel;
    private Payment $paymentModel;
    private User $userModel;

    public function __construct(
        LoggerInterface $logger, 
        ExceptionHandler $exceptionHandler,
        Booking $bookingModel,
        Payment $paymentModel,
        User $userModel
    ) {
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;
        $this->bookingModel = $bookingModel;
        $this->paymentModel = $paymentModel;
        $this->userModel = $userModel;
    }    

    public function generateReport(string $reportType, array $dateRange, string $format, array $filters = []): string
    {
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
        $data = match ($reportType) {
            'bookings' => $this->bookingModel->getByUserAndDateRange($userId, $dateRange['start'], $dateRange['end']),
            'payments' => $this->paymentModel->getByUserAndDateRange($userId, $dateRange['start'], $dateRange['end']),
            default    => throw new \InvalidArgumentException("Invalid report type: $reportType"),
        };
        return $this->exportReport($data, "{$reportType}_user_{$userId}", $format);
    }

    private function getBookingReportData(array $dateRange, array $filters): array
    {
        try {
            $data = $this->bookingModel->getByDateRange($dateRange['start'], $dateRange['end'], $filters);
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[model] Fetched booking report data", ['category' => 'report']);
            }
            return $data;
        } catch (\Exception $e) {
            $this->logger->error("[model] Error fetching booking data: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    private function getPaymentReportData(array $dateRange, array $filters): array
    {
        try {
            $data = $this->paymentModel->getByDateRange($dateRange['start'], $dateRange['end'], $filters);
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[model] Fetched payment report data", ['category' => 'report']);
            }
            return $data;
        } catch (\Exception $e) {
            $this->logger->error("[model] Error fetching payment data: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    private function getUserReportData(array $dateRange, array $filters): array
    {
        try {
            $data = $this->userModel->getByDateRange($dateRange['start'], $dateRange['end'], $filters);
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[model] Fetched user report data");
            }
            return $data;
        } catch (\Exception $e) {
            $this->logger->error("[model] Error fetching user data: " . $e->getMessage());
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
