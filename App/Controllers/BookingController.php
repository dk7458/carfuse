<?php

namespace App\Controllers;

use App\Models\Booking;
use App\Models\RefundLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            return response()->json([
                'status'  => 'success',
                'message' => 'Booking details fetched',
                'data'    => ['booking' => $booking]
            ], 200);
        } catch (\Exception $e) {
            Log::channel('booking')->error($e->getMessage());
            abort(404, 'Booking not found');
        }
    }

    /**
     * Reschedule Booking
     */
    public function rescheduleBooking(int $id, Request $request): array
    {
        $data = $request->validate([
            'pickup_date'  => 'required|date|after_or_equal:today',
            'dropoff_date' => 'required|date|after:pickup_date',
        ]);

        try {
            $booking = Booking::findOrFail($id);
            $booking->update([
                'pickup_date'  => $data['pickup_date'],
                'dropoff_date' => $data['dropoff_date'],
            ]);
            Log::channel('audit')->info('Booking rescheduled', ['booking_id' => $id]);
            return response()->json([
                'status'  => 'success',
                'message' => 'Booking rescheduled successfully'
            ], 200)->getData(true);
        } catch (\Exception $e) {
            Log::channel('booking')->error('Failed to reschedule booking: ' . $e->getMessage());
            abort(500, 'Failed to reschedule booking');
        }
    }

    /**
     * Cancel Booking
     */
    public function cancelBooking(int $id): array
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
            Log::channel('booking')->info('Booking canceled', ['booking_id' => $id]);
            return response()->json([
                'status'  => 'success',
                'message' => 'Booking canceled successfully'
            ], 200)->getData(true);
        } catch (\Exception $e) {
            Log::channel('booking')->error('Failed to cancel booking: ' . $e->getMessage());
            abort(500, 'Failed to cancel booking');
        }
    }

    /**
     * Fetch Booking Logs
     */
    public function getBookingLogs(int $bookingId): array
    {
        try {
            $logs = Booking::findOrFail($bookingId)->logs()->latest()->get();
            return response()->json([
                'status'  => 'success',
                'message' => 'Booking logs fetched successfully',
                'data'    => ['logs' => $logs]
            ], 200)->getData(true);
        } catch (\Exception $e) {
            Log::channel('booking')->error('Failed to fetch booking logs: ' . $e->getMessage());
            abort(500, 'Failed to fetch booking logs');
        }
    }

    /**
     * List All Bookings for a User
     */
    public function getUserBookings(): array
    {
        try {
            $bookings = Booking::where('user_id', Auth::id())->latest()->get();
            return response()->json([
                'status'  => 'success',
                'message' => 'User bookings fetched successfully',
                'data'    => ['bookings' => $bookings]
            ], 200)->getData(true);
        } catch (\Exception $e) {
            Log::channel('booking')->error('Failed to fetch user bookings: ' . $e->getMessage());
            abort(500, 'Failed to fetch user bookings');
        }
    }

    /**
     * Create New Booking
     */
    public function createBooking(Request $request): array
    {
        $data = $request->validate([
            'user_id'    => 'required|integer',
            'vehicle_id' => 'required|integer',
            'pickup_date'  => 'required|date|after_or_equal:today',
            'dropoff_date' => 'required|date|after:pickup_date',
        ]);

        try {
            // Check vehicle availability using an assumed Booking::isAvailable() scope.
            if (!Booking::isAvailable($data['vehicle_id'], $data['pickup_date'], $data['dropoff_date'])) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Vehicle is not available for the selected dates'
                ], 400)->getData(true);
            }
            $booking = Booking::create($data);
            Log::channel('booking')->info('Booking created', ['booking_id' => $booking->id]);
            return response()->json([
                'status'  => 'success',
                'message' => 'Booking created successfully',
                'data'    => ['booking_id' => $booking->id]
            ], 201)->getData(true);
        } catch (\Exception $e) {
            Log::channel('booking')->error('Failed to create booking: ' . $e->getMessage());
            abort(500, 'Failed to create booking');
        }
    }
}
