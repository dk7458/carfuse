<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\TransactionLog;
use App\Models\Payment;
use PDO;
use Psr\Log\LoggerInterface;

class PaymentService
{
    private PDO $db;
    private LoggerInterface $logger;
    private Payment $paymentModel;
    private string $payuApiKey;
    private string $payuApiSecret;

    public function __construct(PDO $db, LoggerInterface $logger, Payment $paymentModel, string $payuApiKey, string $payuApiSecret)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->paymentModel = $paymentModel;
        $this->payuApiKey = $payuApiKey;
        $this->payuApiSecret = $payuApiSecret;
    }

    public function processPayment(int $bookingId, float $amount, string $paymentMethod): bool
    {
        try {
            $this->paymentModel->createPayment($bookingId, $amount, $paymentMethod);

            $booking = new Booking($this->db);
            $booking->updateStatus($bookingId, 'paid');

            $this->logTransaction($bookingId, $amount, 'payment');

            $this->logger->info("Payment processed for booking $bookingId");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Payment processing failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function processRefund(int $bookingId, float $amount): bool
    {
        try {
            $this->paymentModel->createRefund($bookingId, $amount);

            $stmt = $this->db->prepare("UPDATE bookings SET refund_status = 'processed' WHERE id = :id");
            $stmt->execute([':id' => $bookingId]);

            $this->logTransaction($bookingId, $amount, 'refund');

            $this->logger->info("Refund processed for booking $bookingId");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Refund processing failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function logTransaction(int $bookingId, float $amount, string $type): void
    {
        $transactionLog = new TransactionLog($this->db);
        $transactionLog->create([
            'booking_id' => $bookingId,
            'amount' => $amount,
            'type' => $type,
            'status' => 'completed',
        ]);
    }

    public function getMonthlyRevenueTrends(): array
    {
        $stmt = $this->db->prepare("
            SELECT MONTH(created_at) AS month, SUM(amount) AS total
            FROM transaction_logs
            WHERE type = 'payment' AND YEAR(created_at) = YEAR(CURRENT_DATE)
            GROUP BY MONTH(created_at)
            ORDER BY MONTH(created_at)
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
