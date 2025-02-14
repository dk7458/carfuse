<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\TransactionLog;
use App\Models\Payment;
use App\Models\RefundLog;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

class PaymentService
{
    private LoggerInterface $logger;
    private Payment $paymentModel;
    private string $payuApiKey;
    private string $payuApiSecret;

    public function __construct(LoggerInterface $logger, Payment $paymentModel, string $payuApiKey, string $payuApiSecret)
    {
        $this->logger = $logger;
        $this->paymentModel = $paymentModel;
        $this->payuApiKey = $payuApiKey;
        $this->payuApiSecret = $payuApiSecret;
    }

    public function processPayment($user, array $paymentData)
    {
        // Verify user authentication
        if (empty($user) || empty($user['authenticated']) || !$user['authenticated']) {
            $this->logger->error('Unauthenticated payment attempt', ['user' => $user]);
            return ['status' => 'error', 'message' => 'User not authenticated'];
        }

        // Role-based access control for admin-only transactions
        if (!empty($paymentData['adminOnly']) && $paymentData['adminOnly'] === true) {
            if ($user['role'] !== 'admin') {
                $this->logger->error('Unauthorized admin transaction', ['user' => $user]);
                return ['status' => 'error', 'message' => 'Admin privileges required'];
            }
        }

        return DB::transaction(function () use ($paymentData) {
            // Create payment record using Eloquent
            Payment::create([
                'booking_id'     => $paymentData['bookingId'],
                'amount'         => $paymentData['amount'],
                'payment_method' => $paymentData['paymentMethod'],
                'status'         => 'completed'
            ]);
            // Update booking status using Eloquent
            $booking = Booking::findOrFail($paymentData['bookingId']);
            $booking->update(['status' => 'paid']);

            $this->logTransaction($paymentData['bookingId'], $paymentData['amount'], 'payment');

            $this->logger->info("Payment processed for booking {$paymentData['bookingId']}");
            return ['status' => 'success', 'message' => 'Payment processed successfully'];
        });
    }

    public function processRefund(int $bookingId, float $amount): bool
    {
        return DB::transaction(function () use ($bookingId, $amount) {
            // Create refund record using Eloquent
            RefundLog::create([
                'booking_id' => $bookingId,
                'amount'     => $amount,
                'status'     => 'processed'
            ]);
            // Update booking refund status using Eloquent
            $booking = Booking::findOrFail($bookingId);
            $booking->update(['refund_status' => 'processed']);

            $this->logTransaction($bookingId, $amount, 'refund');

            $this->logger->info("Refund processed for booking $bookingId");
            return true;
        });
    }

    private function logTransaction(int $bookingId, float $amount, string $type): void
    {
        TransactionLog::create([
            'booking_id' => $bookingId,
            'amount'     => $amount,
            'type'       => $type,
            'status'     => 'completed',
        ]);
    }

    public function getMonthlyRevenueTrends(): array
    {
        return Payment::where('status', 'completed')
                      ->selectRaw('MONTH(created_at) AS month, SUM(amount) AS total')
                      ->groupBy('month')
                      ->orderBy('month')
                      ->get()
                      ->toArray();
    }
}
