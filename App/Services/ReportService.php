<?php

namespace App\Services;

use PDO;

class ReportService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Generate a report for admin
     */
    public function generateReport(string $reportType, array $dateRange, array $filters = [], string $format): string
    {
        $data = match ($reportType) {
            'bookings' => $this->getBookingReportData($dateRange, $filters),
            'payments' => $this->getPaymentReportData($dateRange, $filters),
            'users' => $this->getUserReportData($dateRange, $filters),
            default => throw new \InvalidArgumentException("Invalid report type: $reportType"),
        };

        return $this->exportReport($data, $reportType, $format);
    }

    /**
     * Generate a user-specific report
     */
    public function generateUserReport(int $userId, string $reportType, array $dateRange, string $format): string
    {
        $data = match ($reportType) {
            'bookings' => $this->getUserBookingReportData($userId, $dateRange),
            'payments' => $this->getUserPaymentReportData($userId, $dateRange),
            default => throw new \InvalidArgumentException("Invalid report type: $reportType"),
        };

        return $this->exportReport($data, "{$reportType}_user_{$userId}", $format);
    }

    /**
     * Fetch booking report data
     */
    private function getBookingReportData(array $dateRange, array $filters): array
    {
        $query = "
            SELECT b.id, b.pickup_date, b.dropoff_date, b.status, b.total_price, u.email AS user_email
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE b.created_at BETWEEN :start AND :end
        ";

        if (!empty($filters['status'])) {
            $query .= " AND b.status = :status";
        }

        $stmt = $this->db->prepare($query);
        $params = [
            ':start' => $dateRange['start'],
            ':end' => $dateRange['end'],
        ];

        if (!empty($filters['status'])) {
            $params[':status'] = $filters['status'];
        }

        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch payment report data
     */
    private function getPaymentReportData(array $dateRange, array $filters): array
    {
        $query = "
            SELECT t.id, t.amount, t.type, t.status, t.created_at, u.email AS user_email
            FROM transaction_logs t
            JOIN users u ON t.user_id = u.id
            WHERE t.created_at BETWEEN :start AND :end
        ";

        if (!empty($filters['type'])) {
            $query .= " AND t.type = :type";
        }

        $stmt = $this->db->prepare($query);
        $params = [
            ':start' => $dateRange['start'],
            ':end' => $dateRange['end'],
        ];

        if (!empty($filters['type'])) {
            $params[':type'] = $filters['type'];
        }

        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch user report data
     */
    private function getUserReportData(array $dateRange, array $filters): array
    {
        $query = "
            SELECT id, email, created_at, active
            FROM users
            WHERE created_at BETWEEN :start AND :end
        ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':start' => $dateRange['start'],
            ':end' => $dateRange['end'],
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch user-specific booking report data
     */
    private function getUserBookingReportData(int $userId, array $dateRange): array
    {
        $stmt = $this->db->prepare("
            SELECT id, pickup_date, dropoff_date, status, total_price
            FROM bookings
            WHERE user_id = :user_id AND created_at BETWEEN :start AND :end
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':start' => $dateRange['start'],
            ':end' => $dateRange['end'],
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch user-specific payment report data
     */
    private function getUserPaymentReportData(int $userId, array $dateRange): array
    {
        $stmt = $this->db->prepare("
            SELECT id, amount, type, status, created_at
            FROM transaction_logs
            WHERE user_id = :user_id AND created_at BETWEEN :start AND :end
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':start' => $dateRange['start'],
            ':end' => $dateRange['end'],
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Export the report data
     */
    private function exportReport(array $data, string $reportName, string $format): string
    {
        $filePath = __DIR__ . "/../../storage/reports/{$reportName}_" . date('YmdHis') . ".{$format}";

        if ($format === 'csv') {
            $file = fopen($filePath, 'w');
            if (!empty($data)) {
                fputcsv($file, array_keys($data[0])); // Add headers
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
            }
            fclose($file);
        } elseif ($format === 'pdf') {
            // For simplicity, we use plain text for PDF export (enhance later with libraries like FPDF or TCPDF)
            $content = '';
            foreach ($data as $row) {
                $content .= implode(' | ', $row) . "\n";
            }
            file_put_contents($filePath, $content);
        } else {
            throw new \InvalidArgumentException("Unsupported format: $format");
        }

        return $filePath;
    }
}
