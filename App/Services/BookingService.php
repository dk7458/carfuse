<?php

namespace App\Services;

use App\Models\Booking;
use Exception;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;

class BookingService
{
    public const DEBUG_MODE = true;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private Booking $bookingModel;

    public function __construct(
        LoggerInterface $logger,
        ExceptionHandler $exceptionHandler,
        Booking $bookingModel
    ) {
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;
        $this->bookingModel = $bookingModel;
    }

    /**
     * Get booking details by ID
     */
    public function getBookingById(int $id): array
    {
        try {
            $booking = $this->bookingModel->find($id);
            if (!$booking) {
                throw new Exception("Booking not found.");
            }
            if (self::DEBUG_MODE) {
                $this->logger->info("[Booking] Retrieved booking id: {$id}");
            }
            return (array)$booking;
        } catch (Exception $e) {
            $this->logger->error("[Booking] ❌ getBookingById error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Reschedule a booking
     */
    public function rescheduleBooking(int $id, string $pickupDate, string $dropoffDate): void
    {
        try {
            $booking = $this->bookingModel->find($id);
            if (!$booking) {
                throw new Exception("Booking not found.");
            }

            $updated = $this->bookingModel->update($id, [
                'pickup_date'  => $pickupDate,
                'dropoff_date' => $dropoffDate,
                'status'       => 'rescheduled'
            ]);

            if (!$updated) {
                throw new Exception("Failed to update booking.");
            }
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[Booking] Rescheduled booking id: {$id}");
            }
        } catch (Exception $e) {
            $this->logger->error("[Booking] ❌ rescheduleBooking error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Cancel a booking and calculate refund amount
     */
    public function cancelBooking(int $id): float
    {
        try {
            $booking = $this->bookingModel->find($id);
            if (!$booking) {
                throw new Exception("Booking not found.");
            }

            $refundAmount = $this->calculateRefundAmount($booking);
            
            $updated = $this->bookingModel->update($id, [
                'status' => 'canceled',
                'refund_amount' => $refundAmount
            ]);

            if (!$updated) {
                throw new Exception("Failed to update booking status.");
            }
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[Booking] Canceled booking id: {$id}");
            }
            
            return $refundAmount;
        } catch (Exception $e) {
            $this->logger->error("[Booking] ❌ cancelBooking error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Calculate refund amount for a booking
     * 
     * @param array $booking
     * @return float
     */
    private function calculateRefundAmount(array $booking): float
    {
        $pickupDate = new \DateTime($booking['pickup_date']);
        $now = new \DateTime();
        $daysUntilPickup = $now->diff($pickupDate)->days;
        
        $totalAmount = $booking['total_amount'] ?? 0;
        
        // Example refund policy
        if ($daysUntilPickup > 7) {
            return $totalAmount * 0.9; // 90% refund if canceled more than 7 days in advance
        } elseif ($daysUntilPickup > 3) {
            return $totalAmount * 0.5; // 50% refund if canceled 3-7 days in advance
        } elseif ($daysUntilPickup > 1) {
            return $totalAmount * 0.25; // 25% refund if canceled 1-3 days in advance
        }
        
        return 0; // No refund for last-minute cancellations
    }

    /**
     * Get user ID associated with a booking
     */
    public function getUserIdByBooking(int $id): int
    {
        try {
            $userId = $this->bookingModel->getUserId($id);
            
            if (!$userId) {
                throw new Exception("User not found for booking.");
            }
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[Booking] Retrieved user id for booking id: {$id}");
            }
            
            return (int)$userId;
        } catch (Exception $e) {
            $this->logger->error("[Booking] ❌ getUserIdByBooking error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Get monthly booking trends for the current year
     */
    public function getMonthlyBookingTrends(): array
    {
        try {
            $trends = $this->bookingModel->getMonthlyTrends();
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[Booking] Retrieved monthly booking trends.");
            }
            
            return $trends;
        } catch (Exception $e) {
            $this->logger->error("[Booking] ❌ getMonthlyBookingTrends error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Get total number of bookings
     */
    public function getTotalBookings(): int
    {
        try {
            $total = $this->bookingModel->getTotalBookings();
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[Booking] Retrieved total bookings.");
            }
            
            return $total;
        } catch (Exception $e) {
            $this->logger->error("[Booking] ❌ getTotalBookings error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Get the number of completed bookings
     */
    public function getCompletedBookings(): int
    {
        try {
            $completed = $this->bookingModel->getCompletedBookings();
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[Booking] Retrieved completed bookings.");
            }
            
            return $completed;
        } catch (Exception $e) {
            $this->logger->error("[Booking] ❌ getCompletedBookings error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Get the number of canceled bookings
     */
    public function getCanceledBookings(): int
    {
        try {
            $canceled = $this->bookingModel->getCanceledBookings();
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[Booking] Retrieved canceled bookings.");
            }
            
            return $canceled;
        } catch (Exception $e) {
            $this->logger->error("[Booking] ❌ getCanceledBookings error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Get booking logs for a specific booking ID
     */
    public function getBookingLogs(int $bookingId): array
    {
        try {
            $logs = $this->bookingModel->getLogs($bookingId);
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[Booking] Retrieved logs for booking id: {$bookingId}");
            }
            
            return $logs;
        } catch (Exception $e) {
            $this->logger->error("[Booking] ❌ getBookingLogs error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Check booking availability
     */
    public function isBookingAvailable(int $vehicleId, string $pickupDate, string $dropoffDate): bool
    {
        try {
            $available = $this->bookingModel->isAvailable($vehicleId, $pickupDate, $dropoffDate);
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[Booking] Checked availability for vehicle id: {$vehicleId}");
            }
            
            return $available;
        } catch (Exception $e) {
            $this->logger->error("[Booking] ❌ isBookingAvailable error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Create a new booking
     */
    public function createBooking(array $bookingData): array
    {
        if (!$this->isBookingAvailable($bookingData['vehicle_id'], $bookingData['pickup_date'], $bookingData['dropoff_date'])) {
            $this->logger->error("[Booking] ❌ Vehicle not available for booking (vehicle id: {$bookingData['vehicle_id']})");
            return ['status' => 'error', 'message' => 'Vehicle not available for the selected dates'];
        }

        try {
            // Add default fields if not provided
            if (!isset($bookingData['status'])) {
                $bookingData['status'] = 'booked';
            }
            
            if (!isset($bookingData['created_at'])) {
                $bookingData['created_at'] = date('Y-m-d H:i:s');
            }
            
            if (!isset($bookingData['updated_at'])) {
                $bookingData['updated_at'] = date('Y-m-d H:i:s');
            }
            
            $bookingId = $this->bookingModel->create($bookingData);

            if (!$bookingId) {
                throw new Exception("Failed to create booking.");
            }

            if (self::DEBUG_MODE) {
                $this->logger->info("[Booking] Booking created for user {$bookingData['user_id']}");
            }

            return ['status' => 'success', 'message' => 'Booking created successfully', 'booking_id' => $bookingId];
        } catch (Exception $e) {
            $this->logger->error("[Booking] ❌ createBooking error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return ['status' => 'error', 'message' => 'Failed to create booking'];
        }
    }
    
    /**
     * Get all bookings for a user
     */
    public function getUserBookings(int $userId): array
    {
        try {
            $bookings = $this->bookingModel->getByUser($userId);
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[Booking] Retrieved bookings for user id: {$userId}");
            }
            
            return $bookings;
        } catch (Exception $e) {
            $this->logger->error("[Booking] ❌ getUserBookings error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }
    
    /**
     * Delete a booking (soft delete if model supports it)
     */
    public function deleteBooking(int $id): bool
    {
        try {
            $deleted = $this->bookingModel->delete($id);
            
            if (!$deleted) {
                throw new Exception("Failed to delete booking.");
            }
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[Booking] Deleted booking id: {$id}");
            }
            
            return true;
        } catch (Exception $e) {
            $this->logger->error("[Booking] ❌ deleteBooking error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }
}
