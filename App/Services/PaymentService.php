<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use App\Services\AuthService;

class PaymentService
{
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private AuthService $authService;

    public function __construct(LoggerInterface $logger, ExceptionHandler $exceptionHandler, AuthService $authService)
    {
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;
        $this->authService = $authService;
    }

    public function processPayment(array $paymentData): array
    {
        try {
            $userId = $this->authService->getUserIdFromToken($paymentData['token']);
            $this->db->transaction(function () use ($paymentData) {
                // Insert payment record
                $this->db->table('payments')->insert([
                    'booking_id'     => $paymentData['bookingId'],
                    'amount'         => $paymentData['amount'],
                    'payment_method' => $paymentData['paymentMethod'],
                    'status'         => 'completed',
                    'created_at'     => now()
                ]);

                // Update booking status
                $booking = $this->db->table('bookings')
                                ->where('id', $paymentData['bookingId'])
                                ->first();
                if (!$booking) {
                    throw new \Exception("Booking not found");
                }
                $this->db->table('bookings')
                        ->where('id', $paymentData['bookingId'])
                        ->update(['status' => 'paid']);

                // Log transaction
                $this->db->table('transaction_logs')->insert([
                    'booking_id' => $paymentData['bookingId'],
                    'amount'     => $paymentData['amount'],
                    'type'       => 'payment',
                    'status'     => 'completed',
                    'created_at' => now()
                ]);
            });
            if (self::DEBUG_MODE) {
                $this->logger->info("[payment] Payment processed for booking {$paymentData['bookingId']}", ['category' => 'system']);
            }
            return ['status' => 'success', 'message' => 'Payment processed successfully'];
        } catch (\Exception $e) {
            $this->logger->error("[Payment] ❌ Processing payment failed: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return ['status' => 'error', 'message' => 'Failed to process payment'];
        }
    }

    public function processRefund(int $bookingId, float $amount): bool
    {
        try {
            $this->db->transaction(function () use ($bookingId, $amount) {
                $this->db->table('refund_logs')->insert([
                    'booking_id' => $bookingId,
                    'amount'     => $amount,
                    'status'     => 'processed',
                    'created_at' => now()
                ]);
                $booking = $this->db->table('bookings')
                                ->where('id', $bookingId)
                                ->first();
                if (!$booking) {
                    throw new \Exception("Booking not found");
                }
                $this->db->table('bookings')
                        ->where('id', $bookingId)
                        ->update(['refund_status' => 'processed']);

                $this->db->table('transaction_logs')->insert([
                    'booking_id' => $bookingId,
                    'amount'     => $amount,
                    'type'       => 'refund',
                    'status'     => 'completed',
                    'created_at' => now()
                ]);
            });
            if (self::DEBUG_MODE) {
                $this->logger->info("[payment] Refund processed for booking {$bookingId}", ['category' => 'system']);
            }
            return true;
        } catch (\Exception $e) {
            $this->logger->error("[db] Database error: " . $e->getMessage(), ['category' => 'db']);
            $this->exceptionHandler->handleException($e);
            return false;
        }
    }

    public function getMonthlyRevenueTrends(): array
    {
        try {
            $data = $this->db->table('payments')
                             ->where('status', 'completed')
                             ->selectRaw('MONTH(created_at) AS month, SUM(amount) AS total')
                             ->groupBy('month')
                             ->orderBy('month')
                             ->get();
            $this->logger->info("[PaymentService] Retrieved monthly revenue trends");
            return $data;
        } catch (\Exception $e) {
            $this->logger->error("[PaymentService] Database error: " . $e->getMessage());
            throw $e;
        }
    }
}
