<?php

namespace App\Controllers;

use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\Validator;
use AuditManager\Services\AuditService;
use App\Services\NotificationService;
use Psr\Log\LoggerInterface;

/**
 * Booking Controller
 *
 * Handles booking operations, including creating, rescheduling,
 * canceling bookings, and fetching booking details or logs.
 */
class BookingController
{
    private BookingService $bookingService;
    private PaymentService $paymentService;
    private Validator $validator;
    private AuditService $auditService;
    private NotificationService $notificationService;
    private LoggerInterface $logger;

    public function __construct(
        BookingService $bookingService,
        PaymentService $paymentService,
        Validator $validator,
        AuditService $auditService,
        NotificationService $notificationService,
        LoggerInterface $logger
    ) {
        $this->bookingService = $bookingService;
        $this->paymentService = $paymentService;
        $this->validator = $validator;
        $this->auditService = $auditService;
        $this->notificationService = $notificationService;
        $this->logger = $logger;
    }

    /**
     * View Booking Details
     */
    public function viewBooking(int $id)
    {
        try {
            $booking = $this->bookingService->getBookingById($id);
            $logs = $this->bookingService->getBookingLogs($id);

            if (!$booking) {
                throw new \Exception("Booking not found.");
            }

            require_once __DIR__ . '/../views/bookings/view.php';
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch booking details', ['error' => $e->getMessage()]);
            http_response_code(404);
            echo 'Booking not found.';
        }
    }

    /**
     * Reschedule Booking
     */
    public function rescheduleBooking(int $id, array $data): array
    {
        $rules = [
            'pickup_date' => 'required|date|after_or_equal:today',
            'dropoff_date' => 'required|date|after:pickup_date',
        ];

        if (!$this->validator->validate($data, $rules)) {
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors()];
        }

        try {
            $this->bookingService->rescheduleBooking($id, $data['pickup_date'], $data['dropoff_date']);
            $this->auditService->log(
                'booking_rescheduled',
                'Booking successfully rescheduled.',
                $this->bookingService->getUserIdByBooking($id),
                $id,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            $this->notificationService->sendNotification(
                $this->bookingService->getUserIdByBooking($id),
                'email',
                'Your booking has been rescheduled successfully.',
                []
            );

            return ['status' => 'success', 'message' => 'Booking rescheduled successfully'];
        } catch (\Exception $e) {
            $this->logger->error('Failed to reschedule booking', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to reschedule booking'];
        }
    }

    /**
     * Cancel Booking
     */
    public function cancelBooking(int $id): array
    {
        try {
            $refundAmount = $this->bookingService->cancelBooking($id);

            if ($refundAmount > 0) {
                $this->paymentService->processRefundForBooking($id, $refundAmount);
            }

            $this->auditService->log(
                'booking_canceled',
                'Booking successfully canceled.',
                $this->bookingService->getUserIdByBooking($id),
                $id,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            $this->notificationService->sendNotification(
                $this->bookingService->getUserIdByBooking($id),
                'email',
                'Your booking has been canceled.',
                []
            );

            return ['status' => 'success', 'message' => 'Booking canceled successfully'];
        } catch (\Exception $e) {
            $this->logger->error('Failed to cancel booking', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to cancel booking'];
        }
    }

    /**
     * Fetch Booking Logs
     */
    public function getBookingLogs(int $bookingId): array
    {
        try {
            $logs = $this->bookingService->getBookingLogs($bookingId);
            return ['status' => 'success', 'logs' => $logs];
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch booking logs', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to fetch booking logs'];
        }
    }

    /**
     * List All Bookings for a User
     */
    public function getUserBookings(int $userId): array
    {
        try {
            $bookings = $this->bookingService->getUserBookings($userId);
            return ['status' => 'success', 'bookings' => $bookings];
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch user bookings', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to fetch user bookings'];
        }
    }

    /**
     * Create New Booking
     */
    public function createBooking(array $data): array
    {
        $rules = [
            'user_id' => 'required|integer',
            'vehicle_id' => 'required|integer',
            'pickup_date' => 'required|date|after_or_equal:today',
            'dropoff_date' => 'required|date|after:pickup_date',
        ];

        if (!$this->validator->validate($data, $rules)) {
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors()];
        }

        try {
            $bookingId = $this->bookingService->createBooking($data);

            $this->auditService->log(
                'booking_created',
                'New booking created.',
                $data['user_id'],
                $bookingId,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            $this->notificationService->sendNotification(
                $data['user_id'],
                'email',
                'Your booking has been created successfully.',
                []
            );

            return ['status' => 'success', 'message' => 'Booking created successfully', 'booking_id' => $bookingId];
        } catch (\Exception $e) {
            $this->logger->error('Failed to create booking', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Failed to create booking'];
        }
    }
}
