<?php

namespace App\Services;

use App\Helpers\DatabaseHelper; // new import
use App\Models\Payment;
use App\Models\TransactionLog;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use App\Services\AuthService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevenueService
{
    public const DEBUG_MODE = true;
    private $db;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private AuthService $authService;

    // Assume dependency injection now supplies the logger.
    public function __construct(LoggerInterface $logger, DatabaseHelper $db, ExceptionHandler $exceptionHandler, AuthService $authService)
    {
        $this->logger = $logger;
        $this->db = $db;
        $this->exceptionHandler = $exceptionHandler;
        $this->authService = $authService;
    }

    public function getMonthlyRevenueTrends(): array
    {
        try {
            $data = $this->db->table('payments')
                ->where('status', 'completed')
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as revenue')
                ->groupBy('month')
                ->orderBy('month')
                ->get();
            $labels = array_column($data, 'month');
            $amounts = array_column($data, 'revenue');
            if (self::DEBUG_MODE) {
                $this->logger->info("[db] Retrieved monthly revenue trends", ['category' => 'revenue']);
            }
            return [
                'status' => 'success',
                'data' => [
                    'labels' => $labels,
                    'data'   => $amounts,
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error("[db] Database error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve monthly revenue trends',
                'error' => $e->getMessage()
            ];
        }
    }

    public function getTotalRevenue(): array
    {
        try {
            $total = $this->db->table('transaction_logs')
                ->where('type', 'payment')
                ->sum('amount');
            if (self::DEBUG_MODE) {
                $this->logger->info("[db] Retrieved total revenue", ['category' => 'revenue']);
            }
            return [
                'status' => 'success',
                'data' => (float) $total
            ];
        } catch (\Exception $e) {
            $this->logger->error("[db] Database error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve total revenue',
                'error' => $e->getMessage()
            ];
        }
    }

    public function getTotalRefunds(): array
    {
        try {
            $total = $this->db->table('transaction_logs')
                ->where('type', 'refund')
                ->sum('amount');
            if (self::DEBUG_MODE) {
                $this->logger->info("[db] Retrieved total refunds", ['category' => 'revenue']);
            }
            return [
                'status' => 'success',
                'data' => (float) $total
            ];
        } catch (\Exception $e) {
            $this->logger->error("[db] Database error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve total refunds',
                'error' => $e->getMessage()
            ];
        }
    }

    public function getNetRevenue(): array
    {
        try {
            $netRevenue = $this->getTotalRevenue()['data'] - $this->getTotalRefunds()['data'];
            return [
                'status' => 'success',
                'data' => (float) $netRevenue
            ];
        } catch (\Exception $e) {
            $this->logger->error("[db] Database error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return [
                'status' => 'error',
                'message' => 'Failed to calculate net revenue',
                'error' => $e->getMessage()
            ];
        }
    }

    public function calculateRevenue(array $parameters): array
    {
        try {
            $user = $this->authService->getUserFromToken();
            $revenueData = $this->computeRevenueData($parameters, $user);
            return [
                'status' => 'success',
                'data' => $revenueData
            ];
        } catch (\Exception $e) {
            Log::error("Revenue calculation failed: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Revenue calculation failed',
                'error' => $e->getMessage()
            ];
        }
    }

    private function computeRevenueData(array $parameters, $user)
    {
        // ...existing code...
    }
}
