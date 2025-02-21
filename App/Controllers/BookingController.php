<?php

namespace App\Controllers;

use App\Models\Booking;
use App\Models\RefundLog;
use Illuminate\Http\Request;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

/**
 * Booking Controller
 *
 * Handles booking operations, including creating, rescheduling,
 * canceling bookings, and fetching booking details or logs.
 */
class BookingController extends Controller
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
            $booking = Booking::with('logs')->findOrFail($id);
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Booking details fetched',
                'data'    => ['booking' => $booking]
            ]);
            exit;
        } catch (\Exception $e) {
            $this->logger->error("BOOKING ERROR: " . $e->getMessage());
            http_response_code(404);
            echo 'Booking not found';
            exit;
        }
    }

    /**
     * Reschedule Booking
     */
    public function rescheduleBooking(int $id): void
    {
        $data = $_POST; // minimal custom validation assumed
        
        try {
            $booking = Booking::findOrFail($id);
            $booking->update([
                'pickup_date'  => $data['pickup_date'],
                'dropoff_date' => $data['dropoff_date'],
            ]);
            error_log("AUDIT: Booking rescheduled, booking_id: {$id}");
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Booking rescheduled successfully'
            ]);
            exit;
        } catch (\Exception $e) {
            $this->logger->error("BOOKING ERROR: Failed to reschedule booking: " . $e->getMessage());
            http_response_code(500);
            echo 'Failed to reschedule booking';
            exit;
        }
    }

    /**
     * Cancel Booking
     */
    public function cancelBooking(int $id): void
    {
        try {
            $booking = Booking::findOrFail($id);
            $booking->update(['status' => 'canceled']);

            // Process refund if applicable.
            $refundAmount = $booking->calculateRefund(); // Assumes a calculateRefund() method exists.
            if ($refundAmount > 0) {
                RefundLog::create([
                    'booking_id' => $id,
                    'amount'     => $refundAmount,
                    'status'     => 'processed'
                ]);
            }
            error_log("BOOKING: Booking canceled, booking_id: {$id}");
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Booking canceled successfully'
            ]);
            exit;
        } catch (\Exception $e) {
            $this->logger->error("BOOKING ERROR: Failed to cancel booking: " . $e->getMessage());
            http_response_code(500);
            echo 'Failed to cancel booking';
            exit;
        }
    }

    /**
     * Fetch Booking Logs
     */
    public function getBookingLogs(int $bookingId): void
    {
        try {
            $logs = Booking::findOrFail($bookingId)->logs()->latest()->get();
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Booking logs fetched successfully',
                'data'    => ['logs' => $logs]
            ]);
            exit;
        } catch (\Exception $e) {
            $this->logger->error("BOOKING ERROR: Failed to fetch booking logs: " . $e->getMessage());
            http_response_code(500);
            echo 'Failed to fetch booking logs';
            exit;
        }
    }

    /**
     * List All Bookings for a User
     */
    public function getUserBookings(): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                throw new \Exception('User not authenticated');
            }
            $bookings = Booking::where('user_id', $userId)->latest()->get();
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'User bookings fetched successfully',
                'data'    => ['bookings' => $bookings]
            ]);
            exit;
        } catch (\Exception $e) {
            $this->logger->error("BOOKING ERROR: Failed to fetch user bookings: " . $e->getMessage());
            http_response_code(500);
            echo 'Failed to fetch user bookings';
            exit;
        }
    }

    /**
     * Create New Booking
     */
    public function createBooking(): void
    {
        $data = $_POST; // assuming custom validation is performed elsewhere
        
        try {
            // Check vehicle availability using an assumed Booking::isAvailable() scope.
            if (!Booking::isAvailable($data['vehicle_id'], $data['pickup_date'], $data['dropoff_date'])) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Vehicle is not available for the selected dates'
                ]);
                exit;
            }
            $booking = Booking::create($data);
            error_log("BOOKING: Booking created, booking_id: {$booking->id}");
            header('Content-Type: application/json');
            http_response_code(201);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Booking created successfully',
                'data'    => ['booking_id' => $booking->id]
            ]);
            exit;
        } catch (\Exception $e) {
            $this->logger->error("BOOKING ERROR: Failed to create booking: " . $e->getMessage());
            http_response_code(500);
            echo 'Failed to create booking';
            exit;
        }
    }
}
