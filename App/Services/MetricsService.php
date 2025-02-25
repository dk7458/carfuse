<?php

namespace App\Services;

use App\Helpers\DatabaseHelper;
use App\Helpers\LoggingHelper;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use Exception;

class MetricsService
{
    public const DEBUG_MODE = true;
    private $db;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;

    public function __construct(LoggerInterface $logger, ExceptionHandler $exceptionHandler, DatabaseHelper $db)
    {
        $this->logger = LoggingHelper::getLoggerByCategory('metrics');
        $this->exceptionHandler = $exceptionHandler;
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
            if (self::DEBUG_MODE) {
                $this->logger->info("[Metrics] Dashboard metrics retrieved successfully");
            }
            return $metrics;
        } catch (Exception $e) {
            $this->logger->error("[DB] ❌ MetricsService error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return [];
        }
    }
}
