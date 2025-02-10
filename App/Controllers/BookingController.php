<?php

namespace App\Controllers;

use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\Validator;
use AuditManager\Services\AuditService;
use App\Services\NotificationService;
use Psr\Log\LoggerInterface;
use App\Middleware\AuthMiddleware;
use Exception;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

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
        header('Content-Type: application/json');
        try {
            AuthMiddleware::validateSession();
            $booking = $this->bookingService->getBookingById($id);
            $logs = $this->bookingService->getBookingLogs($id);

            if (!$booking) {
                throw new \Exception("Booking not found.");
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Booking details fetched',
                'data' => ['booking' => $booking, 'logs' => $logs]
            ]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . " " . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Booking not found', 'data' => []]);
        }
    }

    /**
     * Reschedule Booking
     */
    public function rescheduleBooking(int $id, array $data): array
    {
        try {
            AuthMiddleware::validateSession();
            $rules = [
                'pickup_date' => 'required|date|after_or_equal:today',
                'dropoff_date' => 'required|date|after:pickup_date',
            ];

            if (!$this->validator->validate($data, $rules)) {
                return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors(), 'data' => []];
            }

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

            http_response_code(200);
            return ['status' => 'success', 'message' => 'Booking rescheduled successfully', 'data' => []];
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . " " . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to reschedule booking', 'data' => []];
        }
    }

    /**
     * Cancel Booking
     */
    public function cancelBooking(int $id): array
    {
        try {
            AuthMiddleware::validateSession();
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

            http_response_code(200);
            return ['status' => 'success', 'message' => 'Booking canceled successfully', 'data' => []];
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . " " . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to cancel booking', 'data' => []];
        }
    }

    /**
     * Fetch Booking Logs
     */
    public function getBookingLogs(int $bookingId): array
    {
        try {
            AuthMiddleware::validateSession();
            $logs = $this->bookingService->getBookingLogs($bookingId);
            http_response_code(200);
            return ['status' => 'success', 'message' => 'Booking logs fetched successfully', 'data' => ['logs' => $logs]];
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . " " . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to fetch booking logs', 'data' => []];
        }
    }

    /**
     * List All Bookings for a User
     */
    public function getUserBookings(int $userId): array
    {
        try {
            AuthMiddleware::validateSession();
            $bookings = $this->bookingService->getUserBookings($userId);
            http_response_code(200);
            return ['status' => 'success', 'message' => 'User bookings fetched successfully', 'data' => ['bookings' => $bookings]];
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . " " . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to fetch user bookings', 'data' => []];
        }
    }

    /**
     * Create New Booking
     */
    public function createBooking(array $data): array
    {
        header('Content-Type: application/json');
        try {
            AuthMiddleware::validateSession();
            $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
            if (!validateCsrfToken($csrf)) {
                throw new Exception("Invalid CSRF token");
            }
            $rules = [
                'user_id' => 'required|integer',
                'vehicle_id' => 'required|integer',
                'pickup_date' => 'required|date|after_or_equal:today',
                'dropoff_date' => 'required|date|after:pickup_date',
            ];

            if (!$this->validator->validate($data, $rules)) {
                return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors(), 'data' => []];
            }

            if (!$this->bookingService->isVehicleAvailable($data['vehicle_id'], $data['pickup_date'], $data['dropoff_date'])) {
                return ['status' => 'error', 'message' => 'Vehicle is not available for the selected dates', 'data' => []];
            }

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

            http_response_code(201);
            return ['status' => 'success', 'message' => 'Booking created successfully', 'data' => ['booking_id' => $bookingId]];
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . " " . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to create booking', 'data' => []];
        }
    }
}
