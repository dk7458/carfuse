<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\TransactionLog;
use PDO;
use Psr\Log\LoggerInterface;

class PaymentService
{
    private PDO $db;
    private LoggerInterface $logger;

    public function __construct(PDO $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Process a payment for a booking.
     */
    public function processPayment(int $bookingId, float $amount, string $paymentMethod): bool
    {
        try {
            // Insert payment record
            $stmt = $this->db->prepare("
                INSERT INTO payments (booking_id, amount, method, status, created_at)
                VALUES (:booking_id, :amount, :method, 'completed', NOW())
            ");
            $stmt->execute([
                ':booking_id' => $bookingId,
                ':amount' => $amount,
                ':method' => $paymentMethod,
            ]);

            // Update booking payment status
            $booking = new Booking($this->db);
            $booking->updateStatus($bookingId, 'paid');

            // Log transaction
            $this->logTransaction($bookingId, $amount, 'payment');

            $this->logger->info("Payment processed for booking $bookingId");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Payment processing failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Process a refund for a booking.
     */
    public function processRefund(int $bookingId, float $amount): bool
    {
        try {
            // Insert refund record
            $stmt = $this->db->prepare("
                INSERT INTO refunds (booking_id, amount, status, created_at)
                VALUES (:booking_id, :amount, 'processed', NOW())
            ");
            $stmt->execute([
                ':booking_id' => $bookingId,
                ':amount' => $amount,
            ]);

            // Update booking refund status
            $stmt = $this->db->prepare("UPDATE bookings SET refund_status = 'processed' WHERE id = :id");
            $stmt->execute([':id' => $bookingId]);

            // Log transaction
            $this->logTransaction($bookingId, $amount, 'refund');

            $this->logger->info("Refund processed for booking $bookingId");
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Refund processing failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Log a transaction.
     */
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
