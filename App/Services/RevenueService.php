<?php

namespace App\Services;

use App\Helpers\DatabaseHelper; // new import
use App\Models\Payment;
use App\Models\TransactionLog;
use Psr\Log\LoggerInterface;

class RevenueService
{
    private $db;
    private LoggerInterface $logger;

    // Assume dependency injection now supplies the logger.
    public function __construct(LoggerInterface $logger, DatabaseHelper $db)
    {
        $this->logger = $logger;
        $this->db = $db;
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
            $this->logger->info("[RevenueService] Retrieved monthly revenue trends");
            return [
                'labels' => $labels,
                'data'   => $amounts,
            ];
        } catch (\Exception $e) {
            $this->logger->error("[RevenueService] Database error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getTotalRevenue(): float
    {
        try {
            $total = $this->db->table('transaction_logs')
                ->where('type', 'payment')
                ->sum('amount');
            $this->logger->info("[RevenueService] Retrieved total revenue", ['category' => 'revenue']);
            return (float) $total;
        } catch (\Exception $e) {
            $this->logger->error("[RevenueService] Database error: " . $e->getMessage(), ['category' => 'db']);
            throw $e;
        }
    }

    public function getTotalRefunds(): float
    {
        try {
            $total = $this->db->table('transaction_logs')
                ->where('type', 'refund')
                ->sum('amount');
            $this->logger->info("[RevenueService] Retrieved total refunds");
            return (float) $total;
        } catch (\Exception $e) {
            $this->logger->error("[RevenueService] Database error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getNetRevenue(): float
    {
        return $this->getTotalRevenue() - $this->getTotalRefunds();
    }
}
