<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\TransactionLog;

class RevenueService
{
    public function getMonthlyRevenueTrends(): array
    {
        $data = Payment::where('status', 'completed')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as revenue')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        $labels = $data->pluck('month')->toArray();
        $amounts = $data->pluck('revenue')->toArray();

        return [
            'labels' => $labels,
            'data'   => $amounts,
        ];
    }

    public function getTotalRevenue(): float
    {
        return (float) TransactionLog::where('type', 'payment')->sum('amount');
    }

    public function getTotalRefunds(): float
    {
        return (float) TransactionLog::where('type', 'refund')->sum('amount');
    }

    public function getNetRevenue(): float
    {
        return $this->getTotalRevenue() - $this->getTotalRefunds();
    }
}
