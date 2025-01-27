<?php

namespace App\Services;

use PDO;

class MetricsService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Fetch dashboard metrics
     */
    public function getDashboardMetrics(): array
    {
        $metrics = [
            'total_users' => 0,
            'active_users' => 0,
            'total_bookings' => 0,
            'completed_bookings' => 0,
            'canceled_bookings' => 0,
            'total_revenue' => 0.0,
            'total_refunds' => 0.0,
            'net_revenue' => 0.0,
        ];

        // Fetch total and active users
        $stmt = $this->db->query("SELECT COUNT(*) AS total_users, SUM(active = 1) AS active_users FROM users");
        $metrics = array_merge($metrics, $stmt->fetch(PDO::FETCH_ASSOC));

        // Fetch bookings data
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) AS total_bookings,
                SUM(status = 'completed') AS completed_bookings,
                SUM(status = 'canceled') AS canceled_bookings
            FROM bookings
        ");
        $metrics = array_merge($metrics, $stmt->fetch(PDO::FETCH_ASSOC));

        // Fetch revenue and refunds
        $stmt = $this->db->query("
            SELECT 
                SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END) AS total_revenue,
                SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END) AS total_refunds
            FROM transaction_logs
            WHERE status = 'completed'
        ");
        $revenueData = $stmt->fetch(PDO::FETCH_ASSOC);
        $metrics['total_revenue'] = $revenueData['total_revenue'];
        $metrics['total_refunds'] = $revenueData['total_refunds'];
        $metrics['net_revenue'] = $metrics['total_revenue'] - $metrics['total_refunds'];

        return $metrics;
    }
}
