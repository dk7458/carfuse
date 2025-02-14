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
        return Booking::with('vehicle')->findOrFail($id)->toArray();
    }

    /**
     * Reschedule a booking
     */
    public function rescheduleBooking(int $id, string $pickupDate, string $dropoffDate): void
    {
        $booking = Booking::findOrFail($id);
        $booking->update([
            'pickup_date' => $pickupDate,
            'dropoff_date' => $dropoffDate,
            'status' => 'rescheduled'
        ]);
    }

    /**
     * Cancel a booking and calculate refund amount
     */
    public function cancelBooking(int $id): float
    {
        $booking = Booking::findOrFail($id);
        $booking->update(['status' => 'canceled']);
        return $booking->calculateRefundAmount();
    }

    /**
     * Get user ID associated with a booking
     */
    public function getUserIdByBooking(int $id): int
    {
        return Booking::where('id', $id)->value('user_id');
    }

    /**
     * Get monthly booking trends for the current year
     */
    public function getMonthlyBookingTrends(): array
    {
        return Booking::selectRaw('MONTH(created_at) AS month, COUNT(*) AS total')
                      ->groupBy('month')
                      ->get()
                      ->toArray();
    }

    /**
     * Get total number of bookings
     */
    public function getTotalBookings(): int
    {
        return Booking::count();
    }

    /**
     * Get the number of completed bookings
     */
    public function getCompletedBookings(): int
    {
        return Booking::where('status', 'completed')->count();
    }

    /**
     * Get the number of canceled bookings
     */
    public function getCanceledBookings(): int
    {
        return Booking::where('status', 'canceled')->count();
    }

    /**
     * Get booking logs for a specific booking ID
     */
    public function getBookingLogs(int $bookingId): array
    {
        return Booking::findOrFail($bookingId)->logs()->orderBy('created_at', 'desc')->get()->toArray();
    }

    /**
     * Check booking availability
     */
    private function isBookingAvailable(int $vehicleId, string $pickupDate, string $dropoffDate): bool
    {
        return Booking::isAvailable($vehicleId, $pickupDate, $dropoffDate);
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
