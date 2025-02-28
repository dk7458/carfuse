<?php

namespace App\Services;

use App\Helpers\DatabaseHelper;
use App\Helpers\ExceptionHandler;
use App\Helpers\LoggingHelper;
use App\Models\Payment;
use App\Models\Booking;
use Psr\Log\LoggerInterface;

class PaymentService
{
    public const DEBUG_MODE = true;
    private LoggerInterface $logger;
    private $db;
    private ExceptionHandler $exceptionHandler;
    private Payment $paymentModel;
    private Booking $bookingModel;

    public function __construct(
        LoggerInterface $logger, 
        DatabaseHelper $db, 
        ExceptionHandler $exceptionHandler,
        Payment $paymentModel,
        Booking $bookingModel
    ) {
        $this->logger = LoggingHelper::getLoggerByCategory('payment');
        $this->db = $db;
        $this->exceptionHandler = $exceptionHandler;
        $this->paymentModel = $paymentModel;
        $this->bookingModel = $bookingModel;
    }

    public function processPayment($user, array $paymentData)
    {
        if (empty($user) || empty($user['authenticated']) || !$user['authenticated']) {
            $this->logger->error("[PaymentService] Unauthenticated payment attempt", ['category' => 'auth']);
            return ['status' => 'error', 'message' => 'User not authenticated'];
        }

        if (!empty($paymentData['adminOnly']) && $paymentData['adminOnly'] === true && $user['role'] !== 'admin') {
            $this->logger->error("[PaymentService] Unauthorized admin transaction", ['category' => 'auth']);
            return ['status' => 'error', 'message' => 'Admin privileges required'];
        }

        try {
            // Start a transaction (will use the payment model's transaction handling)
            $this->paymentModel->beginTransaction();

            // Insert payment record using the model
            $paymentId = $this->paymentModel->create([
                'booking_id'     => $paymentData['bookingId'],
                'amount'         => $paymentData['amount'],
                'payment_method' => $paymentData['paymentMethod'],
                'status'         => 'completed',
                'created_at'     => date('Y-m-d H:i:s')
            ]);

            // Check if booking exists
            $booking = $this->bookingModel->find($paymentData['bookingId']);
            if (!$booking) {
                $this->paymentModel->rollBack();
                throw new \Exception("Booking not found");
            }

            // Update booking status
            $this->bookingModel->update($paymentData['bookingId'], [
                'status' => 'paid'
            ]);

            // Log transaction (business level logging)
            $this->paymentModel->logTransaction($paymentData['bookingId'], $paymentData['amount'], 'payment', 'completed');
            
            // Commit the transaction
            $this->paymentModel->commit();
            
            // Business-level logging
            if (self::DEBUG_MODE) {
                $this->logger->info("[payment] Payment processed for booking {$paymentData['bookingId']}", ['category' => 'system']);
            }
            return ['status' => 'success', 'message' => 'Payment processed successfully'];
        } catch (\Exception $e) {
            // Ensure we rollback if any exception occurs
            $this->paymentModel->rollBack();
            
            $this->logger->error("[db] Database error: " . $e->getMessage(), ['category' => 'db']);
            $this->exceptionHandler->handleException($e);
            return ['status' => 'error', 'message' => 'Payment processing failed'];
        }
    }

    public function processRefund(int $bookingId, float $amount): bool
    {
        try {
            // Start a transaction
            $this->paymentModel->beginTransaction();

            // Log the refund
            $this->paymentModel->logRefund($bookingId, $amount, 'processed');

            // Get booking
            $booking = $this->bookingModel->find($bookingId);
            if (!$booking) {
                $this->paymentModel->rollBack();
                throw new \Exception("Booking not found");
            }
            
            // Update booking refund status
            $this->bookingModel->update($bookingId, [
                'refund_status' => 'processed'
            ]);

            // Log transaction (business level logging)
            $this->paymentModel->logTransaction($bookingId, $amount, 'refund', 'completed');
            
            // Commit the transaction
            $this->paymentModel->commit();
            
            // Business-level logging
            if (self::DEBUG_MODE) {
                $this->logger->info("[payment] Refund processed for booking {$bookingId}", ['category' => 'system']);
            }
            return true;
        } catch (\Exception $e) {
            // Ensure we rollback if any exception occurs
            $this->paymentModel->rollBack();
            
            $this->logger->error("[db] Database error: " . $e->getMessage(), ['category' => 'db']);
            $this->exceptionHandler->handleException($e);
            return false;
        }
    }

    public function getMonthlyRevenueTrends(): array
    {
        try {
            $data = $this->paymentModel->getMonthlyRevenueTrends();
            $this->logger->info("[PaymentService] Retrieved monthly revenue trends");
            return $data;
        } catch (\Exception $e) {
            $this->logger->error("[PaymentService] Database error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get payment by ID
     */
    public function getPaymentById(int $id): ?array
    {
        try {
            $payment = $this->paymentModel->find($id);
            if (!$payment) {
                return null;
            }
            return $payment;
        } catch (\Exception $e) {
            $this->logger->error("[PaymentService] Error getting payment: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get all payments for a specific booking
     */
    public function getPaymentsByBooking(int $bookingId): array
    {
        try {
            return $this->paymentModel->getByBookingId($bookingId);
        } catch (\Exception $e) {
            $this->logger->error("[PaymentService] Error getting payments by booking: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get all payments for a specific user
     */
    public function getPaymentsByUser(int $userId): array
    {
        try {
            return $this->paymentModel->getByUserId($userId);
        } catch (\Exception $e) {
            $this->logger->error("[PaymentService] Error getting payments by user: " . $e->getMessage());
            throw $e;
        }
    }
}
