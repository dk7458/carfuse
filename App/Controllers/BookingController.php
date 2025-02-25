<?php

namespace App\Controllers;

use App\Models\Booking;
use App\Models\RefundLog;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Helpers\DatabaseHelper;
use App\Helpers\JsonResponse;
use App\Helpers\TokenValidator;

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
            return JsonResponse::success('Booking details fetched', ['booking' => $booking]);
        } catch (\Exception $e) {
            $this->logger->error("BOOKING ERROR: " . $e->getMessage());
            return JsonResponse::error('Booking not found', 404);
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
            return JsonResponse::success('Booking rescheduled successfully');
        } catch (\Exception $e) {
            $this->logger->error("BOOKING ERROR: Failed to reschedule booking: " . $e->getMessage());
            return JsonResponse::error('Failed to reschedule booking', 500);
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
            return JsonResponse::success('Booking canceled successfully');
        } catch (\Exception $e) {
            $this->logger->error("BOOKING ERROR: Failed to cancel booking: " . $e->getMessage());
            return JsonResponse::error('Failed to cancel booking', 500);
        }
    }

    /**
     * Fetch Booking Logs
     */
    public function getBookingLogs(int $bookingId): void
    {
        try {
            $logs = Booking::findOrFail($bookingId)->logs()->latest()->get();
            return JsonResponse::success('Booking logs fetched successfully', ['logs' => $logs]);
        } catch (\Exception $e) {
            $this->logger->error("BOOKING ERROR: Failed to fetch booking logs: " . $e->getMessage());
            return JsonResponse::error('Failed to fetch booking logs', 500);
        }
    }

    /**
     * List All Bookings for a User
     */
    public function getUserBookings(): void
    {
        try {
            $userId = AuthService::getUserId();
            if (!$userId) {
                throw new \Exception('User not authenticated');
            }
            $bookings = Booking::where('user_id', $userId)->latest()->get();
            return JsonResponse::success('User bookings fetched successfully', ['bookings' => $bookings]);
        } catch (\Exception $e) {
            $this->logger->error("BOOKING ERROR: Failed to fetch user bookings: " . $e->getMessage());
            return JsonResponse::error('Failed to fetch user bookings', 500);
        }
    }

    /**
     * Create New Booking
     */
    public function createBooking(): void
    {
        $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
        if (!$user) {
            return JsonResponse::unauthorized('Invalid token');
        }

        $db = DatabaseHelper::getConnection('default');
        $data = $_POST; // assuming custom validation is performed elsewhere
        
        try {
            // Check vehicle availability using an assumed Booking::isAvailable() scope.
            if (!Booking::isAvailable($data['vehicle_id'], $data['pickup_date'], $data['dropoff_date'])) {
                return JsonResponse::error('Vehicle is not available for the selected dates', 400);
            }
            $booking = Booking::create($data);
            error_log("BOOKING: Booking created, booking_id: {$booking->id}");
            return JsonResponse::success('Booking created successfully', ['booking_id' => $booking->id], 201);
        } catch (\Exception $e) {
            $this->logger->error("BOOKING ERROR: Failed to create booking: " . $e->getMessage());
            return JsonResponse::error('Failed to create booking', 500);
        }
    }
}
