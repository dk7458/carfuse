<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use App\Services\AuthService;

class MetricsService
{
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private AuthService $authService;

    public function __construct(LoggerInterface $logger, ExceptionHandler $exceptionHandler, AuthService $authService)
    {
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;
        $this->authService = $authService;
    }

    public function getMetrics(string $token): array
    {
        try {
            $userId = $this->authService->getUserIdFromToken($token);
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
            return ['status' => 'success', 'data' => $metrics];
        } catch (\Exception $e) {
            $this->logger->error("[Metrics] ❌ Fetching metrics failed: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return ['status' => 'error', 'message' => 'Failed to fetch metrics'];
        }
    }
}
