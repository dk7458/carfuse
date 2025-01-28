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
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($userData) {
            $metrics['total_users'] = $userData['total_users'];
            $metrics['active_users'] = $userData['active_users'];
        }

        // Fetch bookings data
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) AS total_bookings,
                SUM(status = 'completed') AS completed_bookings,
                SUM(status = 'canceled') AS canceled_bookings
            FROM bookings
        ");
        $bookingData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($bookingData) {
            $metrics['total_bookings'] = $bookingData['total_bookings'];
            $metrics['completed_bookings'] = $bookingData['completed_bookings'];
            $metrics['canceled_bookings'] = $bookingData['canceled_bookings'];
        }

        // Fetch revenue and refunds
        $stmt = $this->db->query("
            SELECT 
                SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END) AS total_revenue,
                SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END) AS total_refunds
            FROM transaction_logs
            WHERE status = 'completed'
        ");
        $revenueData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($revenueData) {
            $metrics['total_revenue'] = $revenueData['total_revenue'] ?? 0.0;
            $metrics['total_refunds'] = $revenueData['total_refunds'] ?? 0.0;
            $metrics['net_revenue'] = $metrics['total_revenue'] - $metrics['total_refunds'];
        }

        return $metrics;
    }
}
