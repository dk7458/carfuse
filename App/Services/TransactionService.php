<?php

namespace App\Services;

use App\Helpers\DatabaseHelper;
use Psr\Log\LoggerInterface;

class TransactionService
{
    private $db;
    private LoggerInterface $logger;

    // Constructor for dependency injection
    public function __construct(LoggerInterface $logger, DatabaseHelper $db)
    {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function getByUserId(int $userId): array
    {
        try {
            $transactions = $this->db->table('transaction_logs')
                                     ->where('user_id', $userId)
                                     ->orderBy('created_at', 'desc')
                                     ->get()
                                     ->toArray();
            $this->logger->info("[TransactionService] Retrieved transactions for user {$userId}");
            return $transactions;
        } catch (\Exception $e) {
            $this->logger->error("[TransactionService] Database error while retrieving transactions: " . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): void
    {
        try {
            $this->db->table('transaction_logs')->insert([
                'user_id'    => $data['user_id'],
                'booking_id' => $data['booking_id'],
                'amount'     => $data['amount'],
                'type'       => $data['type'],
                'status'     => $data['status'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->logger->info("[TransactionService] Transaction created for user {$data['user_id']}");
        } catch (\Exception $e) {
            $this->logger->error("[TransactionService] Database error while creating transaction: " . $e->getMessage());
            throw $e;
        }
    }

    public function updateStatus(int $transactionId, string $status): void
    {
        try {
            $this->db->table('transaction_logs')
                     ->where('id', $transactionId)
                     ->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
            $this->logger->info("[TransactionService] Updated status for transaction {$transactionId}");
        } catch (\Exception $e) {
            $this->logger->error("[TransactionService] Database error while updating transaction status: " . $e->getMessage());
            throw $e;
        }
    }
}
