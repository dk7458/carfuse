<?php

namespace App\Services;

use App\Models\Booking;
use Exception;
use App\Helpers\DatabaseHelper;
use App\Helpers\LoggingHelper;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;

class BookingService
{
    public const DEBUG_MODE = true;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private DatabaseHelper $db;
    private Booking $bookingModel;

    public function __construct(LoggerInterface $logger, ExceptionHandler $exceptionHandler, DatabaseHelper $db, Booking $bookingModel)
    {
        $this->logger = LoggingHelper::getLoggerByCategory('booking');
        $this->exceptionHandler = $exceptionHandler;
        $this->db = $db;
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
            
            // Business-level logging (keep it as it's not just a record change)
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

            $updated = $this->bookingModel->update($id, ['status' => 'canceled']);

            if (!$updated) {
                throw new Exception("Failed to update booking status.");
            }
            
            // Business-level logging (keep it as it's not just a record change)
            if (self::DEBUG_MODE) {
                $this->logger->info("[Booking] Canceled booking id: {$id}");
            }
            
            return isset($booking['refund_amount']) ? (float)$booking['refund_amount'] : 0.0;
        } catch (Exception $e) {
            $this->logger->error("[Booking] ❌ cancelBooking error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Get user ID associated with a booking
     */
    public function getUserIdByBooking(int $id): int
    {
        try {
            $booking = $this->bookingModel->find($id);
            if (!$booking) {
                throw new Exception("Booking not found.");
            }
            
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
    private function isBookingAvailable(int $vehicleId, string $pickupDate, string $dropoffDate): bool
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
    public function createBooking(int $userId, int $vehicleId, string $pickupDate, string $dropoffDate): array
    {
        if (!$this->isBookingAvailable($vehicleId, $pickupDate, $dropoffDate)) {
            $this->logger->error("[Booking] ❌ Vehicle not available for booking (vehicle id: {$vehicleId})");
            return ['status' => 'error', 'message' => 'Vehicle not available for the selected dates'];
        }

        try {
            $bookingData = [
                'user_id'     => $userId,
                'vehicle_id'  => $vehicleId,
                'pickup_date' => $pickupDate,
                'dropoff_date'=> $dropoffDate,
                'status'      => 'booked',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s')
            ];
            
            $booking = $this->bookingModel->create($bookingData);

            // Business-level logging (keep it as it's not just a record change)
            if (self::DEBUG_MODE) {
                $this->logger->info("[Booking] Booking created for user {$userId}");
            }

            return ['status' => 'success', 'message' => 'Booking created successfully'];
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
