<?php

namespace App\Services;

use App\Models\TransactionLog;

class TransactionService
{
    public function getByUserId(int $userId): array
    {
        return TransactionLog::where('user_id', $userId)
                              ->latest()
                              ->get()
                              ->toArray();
    }

    public function create(array $data): void
    {
        TransactionLog::create([
            'user_id'    => $data['user_id'],
            'booking_id' => $data['booking_id'],
            'amount'     => $data['amount'],
            'type'       => $data['type'],
            'status'     => $data['status'],
        ]);
    }

    public function updateStatus(int $transactionId, string $status): void
    {
        $transaction = TransactionLog::findOrFail($transactionId);
        $transaction->update(['status' => $status]);
    }
}
