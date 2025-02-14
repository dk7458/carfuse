<?php

namespace App\Services;

use App\Models\Booking;
use Exception;
use Psr\Log\LoggerInterface;
use App\Helpers\DatabaseHelper;

class BookingService
{
    private LoggerInterface $logger;
    private $db; // added

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->db = DatabaseHelper::getInstance();
    }

    /**
     * Get booking details by ID
     */
    public function getBookingById(int $id): array
    {
        try {
            $booking = $this->db->table('bookings')->where('id', $id)->first();
            if (!$booking) {
                throw new Exception("Booking not found.");
            }
            $this->logger->info("[BookingService] Retrieved booking with id: {$id}");
            return (array)$booking;
        } catch (Exception $e) {
            $this->logger->error("[BookingService] Error retrieving booking id {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reschedule a booking
     */
    public function rescheduleBooking(int $id, string $pickupDate, string $dropoffDate): void
    {
        try {
            $updated = $this->db->table('bookings')->where('id', $id)->update([
                'pickup_date' => $pickupDate,
                'dropoff_date' => $dropoffDate,
                'status' => 'rescheduled'
            ]);
            if (!$updated) {
                throw new Exception("Failed to update booking.");
            }
            $this->logger->info("[BookingService] Rescheduled booking id: {$id}");
        } catch (Exception $e) {
            $this->logger->error("[BookingService] Error rescheduling booking id {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cancel a booking and calculate refund amount
     */
    public function cancelBooking(int $id): float
    {
        try {
            $booking = $this->db->table('bookings')->where('id', $id)->first();
            if (!$booking) {
                throw new Exception("Booking not found.");
            }
            $updated = $this->db->table('bookings')->where('id', $id)->update(['status' => 'canceled']);
            if (!$updated) {
                throw new Exception("Failed to update booking status.");
            }
            $this->logger->info("[BookingService] Canceled booking id: {$id}");
            // Assuming refund amount is a field in the booking record.
            return isset($booking->refund_amount) ? $booking->refund_amount : 0.0;
        } catch (Exception $e) {
            $this->logger->error("[BookingService] Error canceling booking id {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get user ID associated with a booking
     */
    public function getUserIdByBooking(int $id): int
    {
        try {
            $record = $this->db->table('bookings')->where('id', $id)->first();
            if (!$record || !isset($record->user_id)) {
                throw new Exception("User not found for booking.");
            }
            $this->logger->info("[BookingService] Retrieved user id for booking id: {$id}");
            return $record->user_id;
        } catch (Exception $e) {
            $this->logger->error("[BookingService] Error retrieving user id for booking id {$id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get monthly booking trends for the current year
     */
    public function getMonthlyBookingTrends(): array
    {
        try {
            $trends = $this->db->table('bookings')
                               ->selectRaw('MONTH(created_at) AS month, COUNT(*) AS total')
                               ->groupBy('month')
                               ->get();
            $this->logger->info("[BookingService] Retrieved monthly booking trends");
            return $trends;
        } catch (Exception $e) {
            $this->logger->error("[BookingService] Error retrieving monthly booking trends: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get total number of bookings
     */
    public function getTotalBookings(): int
    {
        try {
            $total = $this->db->table('bookings')->count();
            $this->logger->info("[BookingService] Retrieved total number of bookings");
            return $total;
        } catch (Exception $e) {
            $this->logger->error("[BookingService] Error retrieving total number of bookings: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the number of completed bookings
     */
    public function getCompletedBookings(): int
    {
        try {
            $completed = $this->db->table('bookings')->where('status', 'completed')->count();
            $this->logger->info("[BookingService] Retrieved number of completed bookings");
            return $completed;
        } catch (Exception $e) {
            $this->logger->error("[BookingService] Error retrieving number of completed bookings: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the number of canceled bookings
     */
    public function getCanceledBookings(): int
    {
        try {
            $canceled = $this->db->table('bookings')->where('status', 'canceled')->count();
            $this->logger->info("[BookingService] Retrieved number of canceled bookings");
            return $canceled;
        } catch (Exception $e) {
            $this->logger->error("[BookingService] Error retrieving number of canceled bookings: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get booking logs for a specific booking ID
     */
    public function getBookingLogs(int $bookingId): array
    {
        try {
            $logs = $this->db->table('booking_logs')
                             ->where('booking_id', $bookingId)
                             ->orderBy('created_at', 'desc')
                             ->get();
            $this->logger->info("[BookingService] Retrieved logs for booking id: {$bookingId}");
            return $logs;
        } catch (Exception $e) {
            $this->logger->error("[BookingService] Error retrieving logs for booking id {$bookingId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check booking availability
     */
    private function isBookingAvailable(int $vehicleId, string $pickupDate, string $dropoffDate): bool
    {
        try {
            // Assuming DatabaseHelper provides a method isAvailable() or equivalent query.
            $available = $this->db->table('bookings')
                                  ->where('vehicle_id', $vehicleId)
                                  ->whereBetween('pickup_date', [$pickupDate, $dropoffDate])
                                  ->count() === 0;
            $this->logger->info("[BookingService] Checked availability for vehicle id: {$vehicleId}");
            return $available;
        } catch (Exception $e) {
            $this->logger->error("[BookingService] Error checking availability for vehicle id {$vehicleId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new booking
     */
    public function createBooking(int $userId, int $vehicleId, string $pickupDate, string $dropoffDate): array
    {
        if (!$this->isBookingAvailable($vehicleId, $pickupDate, $dropoffDate)) {
            $this->logger->warning("[BookingService] Booking attempt failed: vehicle not available", [
                'user_id' => $userId,
                'vehicle_id' => $vehicleId,
                'pickup_date' => $pickupDate,
                'dropoff_date' => $dropoffDate,
            ]);
            return ['status' => 'error', 'message' => 'Vehicle not available for the selected dates'];
        }

        try {
            $booking = $this->db->table('bookings')->insertGetId([
                'user_id' => $userId,
                'vehicle_id' => $vehicleId,
                'pickup_date' => $pickupDate,
                'dropoff_date' => $dropoffDate,
                'status' => 'booked',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $this->logger->info("[BookingService] Booking created successfully", [
                'user_id' => $userId,
                'vehicle_id' => $vehicleId,
                'pickup_date' => $pickupDate,
                'dropoff_date' => $dropoffDate,
            ]);

            return ['status' => 'success', 'message' => 'Booking created successfully'];
        } catch (Exception $e) {
            $this->logger->error("[BookingService] Failed to create booking: " . $e->getMessage(), [
                'user_id' => $userId,
                'vehicle_id' => $vehicleId,
                'pickup_date' => $pickupDate,
                'dropoff_date' => $dropoffDate,
            ]);
            return ['status' => 'error', 'message' => 'Failed to create booking'];
        }
    }
}
