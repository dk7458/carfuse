<?php

namespace App\Services;

use App\Helpers\DatabaseHelper;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;

class TransactionService
{
    public const DEBUG_MODE = true;
    private $db;
    private LoggerInterface $bookingLogger;
    private ExceptionHandler $exceptionHandler;

    // Constructor for dependency injection
    public function __construct(LoggerInterface $bookingLogger, DatabaseHelper $db, ExceptionHandler $exceptionHandler)
    {
        $this->bookingLogger = $bookingLogger;
        $this->db = $db;
        $this->exceptionHandler = $exceptionHandler;
    }

    public function getByUserId(int $userId): array
    {
        try {
            $transactions = $this->db->table('transaction_logs')
                                     ->where('user_id', $userId)
                                     ->orderBy('created_at', 'desc')
                                     ->get()
                                     ->toArray();
            if (self::DEBUG_MODE) {
                $this->bookingLogger->info("[db] Retrieved transactions", ['userId' => $userId]);
            }
            return $transactions;
        } catch (\Exception $e) {
            $this->bookingLogger->error("[db] ❌ Error retrieving transactions: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
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
            $this->bookingLogger->info("Transaction created", ['userId' => $data['user_id']]);
        } catch (\Exception $e) {
            $this->bookingLogger->error("Database error while creating transaction", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateStatus(int $transactionId, string $status): void
    {
        try {
            $this->db->table('transaction_logs')
                     ->where('id', $transactionId)
                     ->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
            $this->bookingLogger->info("Updated transaction status", ['transactionId' => $transactionId, 'status' => $status]);
        } catch (\Exception $e) {
            $this->bookingLogger->error("Database error while updating transaction status", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
