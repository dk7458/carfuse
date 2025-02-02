<?php

namespace App\Services;

use PDO;
use Psr\Log\LoggerInterface;

class BookingService
{
    private PDO $db;
    private LoggerInterface $logger;

    public function __construct(PDO $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Get booking details by ID
     */
    public function getBookingById(int $id): array
    {
        $stmt = $this->db->prepare("
            SELECT b.*, CONCAT(f.make, ' ', f.model) AS vehicle
            FROM bookings b
            JOIN fleet f ON b.vehicle_id = f.id
            WHERE b.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            throw new \Exception('Booking not found');
        }

        return $booking;
    }

    /**
     * Reschedule a booking
     */
    public function rescheduleBooking(int $id, string $pickupDate, string $dropoffDate): void
    {
        $stmt = $this->db->prepare("
            UPDATE bookings
            SET pickup_date = :pickup_date, dropoff_date = :dropoff_date, status = 'rescheduled'
            WHERE id = :id
        ");
        $stmt->execute([
            'id' => $id,
            'pickup_date' => $pickupDate,
            'dropoff_date' => $dropoffDate,
        ]);
    }

    /**
     * Cancel a booking and calculate refund amount
     */
    public function cancelBooking(int $id): float
    {
        $stmt = $this->db->prepare("
            UPDATE bookings
            SET status = 'canceled'
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);

        // Calculate refund amount (example: 80% of total price if canceled)
        $refundStmt = $this->db->prepare("
            SELECT total_price * 0.8 AS refund_amount
            FROM bookings
            WHERE id = :id
        ");
        $refundStmt->execute(['id' => $id]);
        $result = $refundStmt->fetch(PDO::FETCH_ASSOC);

        return $result['refund_amount'] ?? 0.0;
    }

    /**
     * Get user ID associated with a booking
     */
    public function getUserIdByBooking(int $id): int
    {
        $stmt = $this->db->prepare("
            SELECT user_id 
            FROM bookings 
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get monthly booking trends for the current year
     */
    public function getMonthlyBookingTrends(): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                MONTH(created_at) AS month, 
                COUNT(*) AS total
            FROM bookings
            WHERE YEAR(created_at) = YEAR(CURRENT_DATE)
            GROUP BY MONTH(created_at)
            ORDER BY MONTH(created_at)
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total number of bookings
     */
    public function getTotalBookings(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM bookings");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get the number of completed bookings
     */
    public function getCompletedBookings(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM bookings WHERE status = 'completed'");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get the number of canceled bookings
     */
    public function getCanceledBookings(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM bookings WHERE status = 'canceled'");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get booking logs for a specific booking ID
     */
    public function getBookingLogs(int $bookingId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                action, 
                details, 
                created_at 
            FROM booking_logs
            WHERE booking_id = :booking_id
            ORDER BY created_at DESC
        ");
        $stmt->execute(['booking_id' => $bookingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check booking availability
     */
    private function isBookingAvailable(int $vehicleId, string $pickupDate, string $dropoffDate): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM bookings 
            WHERE vehicle_id = :vehicle_id 
              AND status NOT IN ('canceled', 'completed')
              AND (
                  (pickup_date BETWEEN :pickup_date AND :dropoff_date) OR
                  (dropoff_date BETWEEN :pickup_date AND :dropoff_date) OR
                  (:pickup_date BETWEEN pickup_date AND dropoff_date) OR
                  (:dropoff_date BETWEEN pickup_date AND dropoff_date)
              )
        ");
        $stmt->execute([
            'vehicle_id' => $vehicleId,
            'pickup_date' => $pickupDate,
            'dropoff_date' => $dropoffDate,
        ]);
        return (int)$stmt->fetchColumn() === 0;
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
            $stmt = $this->db->prepare("
                INSERT INTO bookings (user_id, vehicle_id, pickup_date, dropoff_date, status)
                VALUES (:user_id, :vehicle_id, :pickup_date, :dropoff_date, 'booked')
            ");
            $stmt->execute([
                'user_id' => $userId,
                'vehicle_id' => $vehicleId,
                'pickup_date' => $pickupDate,
                'dropoff_date' => $dropoffDate,
            ]);

            $this->logger->info('Booking created successfully', [
                'user_id' => $userId,
                'vehicle_id' => $vehicleId,
                'pickup_date' => $pickupDate,
                'dropoff_date' => $dropoffDate,
            ]);

            return ['status' => 'success', 'message' => 'Booking created successfully'];
        } catch (\Exception $e) {
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
