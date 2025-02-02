<?php

namespace App\Services;

use PDO;

class RevenueService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Fetch monthly revenue trends
     */
    public function getMonthlyRevenueTrends(): array
    {
        $stmt = $this->db->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(amount) AS revenue
            FROM transaction_logs
            WHERE type = 'payment' AND status = 'completed'
            GROUP BY month
            ORDER BY month
        ");

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $labels = array_column($data, 'month');
        $amounts = array_column($data, 'revenue');

        return [
            'labels' => $labels,
            'data' => $amounts,
        ];
    }
}
