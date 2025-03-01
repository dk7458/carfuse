<?php

namespace App\Services;

use App\Helpers\DatabaseHelper; // new import
use App\Models\Payment;
use App\Models\TransactionLog;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use App\Helpers\LoggingHelper;

class RevenueService
{
    public const DEBUG_MODE = true;
    private $db;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;

    // Assume dependency injection now supplies the logger.
    public function __construct(LoggerInterface $logger, DatabaseHelper $db, ExceptionHandler $exceptionHandler)
    {
        $this->logger = getLogger('revenue');
        $this->db = $db;
        $this->exceptionHandler = $exceptionHandler;
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
                'labels' => $labels,
                'data'   => $amounts,
            ];
        } catch (\Exception $e) {
            $this->logger->error("[db] Database error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function getTotalRevenue(): float
    {
        try {
            $total = $this->db->table('transaction_logs')
                ->where('type', 'payment')
                ->sum('amount');
            if (self::DEBUG_MODE) {
                $this->logger->info("[db] Retrieved total revenue", ['category' => 'revenue']);
            }
            return (float) $total;
        } catch (\Exception $e) {
            $this->logger->error("[db] Database error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function getTotalRefunds(): float
    {
        try {
            $total = $this->db->table('transaction_logs')
                ->where('type', 'refund')
                ->sum('amount');
            if (self::DEBUG_MODE) {
                $this->logger->info("[db] Retrieved total refunds", ['category' => 'revenue']);
            }
            return (float) $total;
        } catch (\Exception $e) {
            $this->logger->error("[db] Database error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function getNetRevenue(): float
    {
        return $this->getTotalRevenue() - $this->getTotalRefunds();
    }
}
