<?php

namespace App\Services;

use App\Models\Booking;
use Exception;
use Psr\Log\LoggerInterface;

class BookingService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get booking details by ID
     */
    public function getBookingById(int $id): array
    {
        try {
            $booking = Booking::with('vehicle')->findOrFail($id);
            $this->logger->info("[BookingService] Retrieved booking with id: {$id}");
            return $booking->toArray();
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
            $booking = Booking::findOrFail($id);
            $booking->update([
                'pickup_date' => $pickupDate,
                'dropoff_date' => $dropoffDate,
                'status' => 'rescheduled'
            ]);
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
            $booking = Booking::findOrFail($id);
            $booking->update(['status' => 'canceled']);
            $this->logger->info("[BookingService] Canceled booking id: {$id}");
            return $booking->calculateRefundAmount();
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
            $userId = Booking::where('id', $id)->value('user_id');
            $this->logger->info("[BookingService] Retrieved user id for booking id: {$id}");
            return $userId;
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
            $trends = Booking::selectRaw('MONTH(created_at) AS month, COUNT(*) AS total')
                             ->groupBy('month')
                             ->get()
                             ->toArray();
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
            $total = Booking::count();
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
            $completed = Booking::where('status', 'completed')->count();
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
            $canceled = Booking::where('status', 'canceled')->count();
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
            $logs = Booking::findOrFail($bookingId)->logs()->orderBy('created_at', 'desc')->get()->toArray();
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
            $available = Booking::isAvailable($vehicleId, $pickupDate, $dropoffDate);
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
            $this->logger->warning('Booking attempt failed: vehicle not available', [
                'user_id' => $userId,
                'vehicle_id' => $vehicleId,
                'pickup_date' => $pickupDate,
                'dropoff_date' => $dropoffDate,
            ]);
            return ['status' => 'error', 'message' => 'Vehicle not available for the selected dates'];
        }

        try {
            $booking = Booking::create([
                'user_id' => $userId,
                'vehicle_id' => $vehicleId,
                'pickup_date' => $pickupDate,
                'dropoff_date' => $dropoffDate,
                'status' => 'booked'
            ]);

            $this->logger->info('Booking created successfully', [
                'user_id' => $userId,
                'vehicle_id' => $vehicleId,
                'pickup_date' => $pickupDate,
                'dropoff_date' => $dropoffDate,
            ]);

            return ['status' => 'success', 'message' => 'Booking created successfully'];
        } catch (Exception $e) {
            $this->logger->error('Failed to create booking', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'vehicle_id' => $vehicleId,
                'pickup_date' => $pickupDate,
                'dropoff_date' => $dropoffDate,
            ]);
            return ['status' => 'error', 'message' => 'Failed to create booking'];
        }
    }
}
