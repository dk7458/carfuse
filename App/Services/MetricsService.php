<?php

namespace App\Services;

use App\Helpers\DatabaseHelper;
use Psr\Log\LoggerInterface;
use Exception;

class MetricsService
{
    private $db;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, DatabaseHelper $db)
    {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getDashboardMetrics(): array
    {
        try {
            $totalUsers        = $this->db->table('users')->count();
            $activeUsers       = $this->db->table('users')->where('active', true)->count();
            $totalBookings     = $this->db->table('bookings')->count();
            $completedBookings = $this->db->table('bookings')->where('status', 'completed')->count();
            $canceledBookings  = $this->db->table('bookings')->where('status', 'canceled')->count();
            $totalRevenue      = $this->db->table('payments')->where('status', 'completed')->sum('amount');
            $totalRefunds      = $this->db->table('payments')
                                          ->where('status', 'completed')
                                          ->where('type', 'refund')
                                          ->sum('amount');
            
            $metrics = [
                'total_users'         => $totalUsers,
                'active_users'        => $activeUsers,
                'total_bookings'      => $totalBookings,
                'completed_bookings'  => $completedBookings,
                'canceled_bookings'   => $canceledBookings,
                'total_revenue'       => $totalRevenue,
                'total_refunds'       => $totalRefunds,
            ];
            $metrics['net_revenue'] = $totalRevenue - $totalRefunds;
            $this->logger->info("[MetricsService] Dashboard metrics retrieved successfully", ['category' => 'metrics']);
            return $metrics;
        } catch (Exception $e) {
            $this->logger->error("[MetricsService] Database error while retrieving metrics: " . $e->getMessage(), ['category' => 'db']);
            return [];
        }
    }
}
