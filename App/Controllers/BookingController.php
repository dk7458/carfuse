<?php

namespace App\Controllers;

use App\Models\RefundLog;
use App\Services\AuthService;
use App\Services\AuditService;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\NotificationService;
use App\Services\Auth\TokenService;
use App\Helpers\ExceptionHandler;
use App\Services\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

require_once   'ViewHelper.php';

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
    private ResponseFactoryInterface $responseFactory;
    protected LoggerInterface $logger;
    private TokenService $tokenService;
    protected ExceptionHandler $exceptionHandler;

    public function __construct(
        LoggerInterface $logger,
        BookingService $bookingService,
        PaymentService $paymentService,
        Validator $validator,
        AuditService $auditService,
        NotificationService $notificationService,
        ResponseFactoryInterface $responseFactory,
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->bookingService = $bookingService;
        $this->paymentService = $paymentService;
        $this->validator = $validator;
        $this->auditService = $auditService;
        $this->notificationService = $notificationService;
        $this->responseFactory = $responseFactory;
        $this->tokenService = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * Create standardized PSR-7 JSON response
     */
    public function jsonResponse(ResponseInterface $response, $data, $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    /**
     * View Booking Details
     */
    public function viewBooking(int $id): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateTokenFromHeader($this->request->getHeader('Authorization')[0] ?? null);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            $booking = $this->bookingService->getBookingById($id);
            
            // Audit log for viewing booking
            $this->auditService->logEvent(
                'booking_viewed',
                "Booking #{$id} details viewed",
                ['booking_id' => $id, 'user_id' => $user['id']],
                $user['id'],
                $id,
                'booking'
            );
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Booking details fetched',
                'data' => ['booking' => $booking]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Failed to fetch booking details'
            ], 500);
        }
    }

    /**
     * Reschedule Booking
     */
    public function rescheduleBooking(int $id): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateTokenFromHeader($this->request->getHeader('Authorization')[0] ?? null);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            $data = $this->validator->validate($_POST, [
                'pickup_date' => 'required|date',
                'dropoff_date' => 'required|date|after:pickup_date'
            ]);
            
            if ($this->validator->failed()) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $this->validator->errors()
                ], 400);
            }
            
            // Let the service handle the business logic
            $result = $this->bookingService->rescheduleBooking($id, $data['pickup_date'], $data['dropoff_date'], $user['id']);
            
            if (isset($result['error'])) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => $result['error']
                ], 400);
            }
            
            // Audit the rescheduling action
            $this->auditService->logEvent(
                'booking_rescheduled',
                "Booking #{$id} rescheduled",
                [
                    'booking_id' => $id,
                    'user_id' => $user['id'],
                    'new_pickup' => $data['pickup_date'],
                    'new_dropoff' => $data['dropoff_date']
                ],
                $user['id'],
                $id,
                'booking'
            );
            
            // Send notification about rescheduled booking
            $this->notificationService->sendBookingUpdatedNotification($id, $user['id']);
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Booking rescheduled successfully'
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Failed to reschedule booking'
            ], 500);
        }
    }

    /**
     * Cancel Booking
     */
    public function cancelBooking(int $id): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateTokenFromHeader($this->request->getHeader('Authorization')[0] ?? null);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            // Let the service handle the cancellation and refund calculation
            $result = $this->bookingService->cancelBooking($id, $user['id']);
            
            if (isset($result['error'])) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => $result['error']
                ], 400);
            }

            $refundAmount = $result['refund_amount'] ?? 0;

            // Process refund if applicable through payment service
            if ($refundAmount > 0) {
                $refundResult = $this->paymentService->processRefund([
                    'booking_id' => $id,
                    'amount' => $refundAmount,
                    'user_id' => $user['id'],
                    'reason' => 'Booking cancellation'
                ]);
                
                // Audit the refund processed
                $this->auditService->logEvent(
                    'refund_processed',
                    "Refund processed for booking #{$id}",
                    [
                        'booking_id' => $id,
                        'user_id' => $user['id'],
                        'refund_amount' => $refundAmount,
                        'refund_id' => $refundResult['refund_id'] ?? null
                    ],
                    $user['id'],
                    $id,
                    'payment'
                );
            }
            
            // Audit the cancellation
            $this->auditService->logEvent(
                'booking_canceled',
                "Booking #{$id} canceled",
                [
                    'booking_id' => $id,
                    'user_id' => $user['id'],
                    'refund_amount' => $refundAmount
                ],
                $user['id'],
                $id,
                'booking'
            );
            
            // Send notification about canceled booking
            $this->notificationService->sendBookingCanceledNotification($id, $user['id']);
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Booking canceled successfully',
                'data' => [
                    'refund_amount' => $refundAmount
                ]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Failed to cancel booking'
            ], 500);
        }
    }

    /**
     * Fetch Booking Logs
     */
    public function getBookingLogs(int $bookingId): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateTokenFromHeader($this->request->getHeader('Authorization')[0] ?? null);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            // Check if user has access to this booking
            $hasAccess = $this->bookingService->validateBookingAccess($bookingId, $user['id']);
            if (!$hasAccess && !isset($user['role']) && $user['role'] !== 'admin') {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'You do not have permission to view this booking'
                ], 403);
            }
            
            // Get logs through the service
            $logs = $this->bookingService->getBookingLogs($bookingId);
            
            // Log this access to audit logs
            $this->auditService->logEvent(
                'booking_logs_viewed',
                "Booking #{$bookingId} logs accessed",
                [
                    'booking_id' => $bookingId,
                    'user_id' => $user['id']
                ],
                $user['id'],
                $bookingId,
                'booking'
            );
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Booking logs fetched successfully',
                'data' => ['logs' => $logs]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Failed to fetch booking logs'
            ], 500);
        }
    }

    /**
     * List All Bookings for a User
     */
    public function getUserBookings(): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateTokenFromHeader($this->request->getHeader('Authorization')[0] ?? null);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            // Get pagination parameters
            $page = (int) ($this->request->getQueryParams()['page'] ?? 1);
            $perPage = (int) ($this->request->getQueryParams()['per_page'] ?? 10);
            $status = $this->request->getQueryParams()['status'] ?? null;
            
            $bookings = $this->bookingService->getUserBookings($user['id'], $page, $perPage, $status);
            
            // Log the fetch operation
            $this->auditService->logEvent(
                'user_bookings_listed',
                "User retrieved their booking list",
                ['user_id' => $user['id']],
                $user['id'],
                null,
                'booking'
            );
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'User bookings fetched successfully',
                'data' => $bookings
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Failed to fetch user bookings'
            ], 500);
        }
    }

    /**
     * Create New Booking
     */
    public function createBooking(): ResponseInterface
    {
        try {
            $user = $this->tokenService->validateTokenFromHeader($this->request->getHeader('Authorization')[0] ?? null);
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Invalid token'
                ], 401);
            }

            $data = $this->validator->validate($_POST, [
                'vehicle_id' => 'required|integer',
                'pickup_date' => 'required|date',
                'dropoff_date' => 'required|date|after:pickup_date',
                'pickup_location' => 'required|string',
                'dropoff_location' => 'required|string',
                'payment_method_id' => 'required|integer'
            ]);
            
            if ($this->validator->failed()) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $this->validator->errors()
                ], 400);
            }
            
            $data['user_id'] = $user['id'];
            
            // Let the BookingService handle all booking creation logic
            $result = $this->bookingService->createBooking($data);
            
            if (isset($result['status']) && $result['status'] == 'error') {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => $result['message'] ?? 'Failed to create booking'
                ], 400);
            }

            // Log the booking creation to the audit logs
            $this->auditService->logEvent(
                'booking_created',
                "New booking created",
                [
                    'booking_id' => $result['booking_id'],
                    'user_id' => $user['id'],
                    'vehicle_id' => $data['vehicle_id'],
                    'pickup_date' => $data['pickup_date'], 
                    'dropoff_date' => $data['dropoff_date']
                ],
                $user['id'],
                $result['booking_id'],
                'booking'
            );
            
            // Send notification for new booking
            $this->notificationService->sendBookingConfirmationNotification($result['booking_id'], $user['id']);
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Booking created successfully',
                'data' => ['booking_id' => $result['booking_id']]
            ], 201);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status' => 'error',
                'message' => 'Failed to create booking'
            ], 500);
        }
    }

    /**
     * Get booking list for HTMX requests
     */
    public function getBookingListHtmx(): void
    {
        try {
            // Get user from session
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                echo '<div class="text-red-500 p-4">Nie jesteś zalogowany. Proszę zalogować się ponownie.</div>';
                return;
            }
            
            // Get pagination and filter parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 5;
            $status = $_GET['status'] ?? 'all';
            
            // Get bookings using service
            $bookings = $this->bookingService->getUserBookings($userId, $page + 1, $perPage, $status !== 'all' ? $status : null);
            
            // Log access to audit trail
            $this->auditService->logEvent(
                'user_bookings_htmx_fetched',
                "User fetched their bookings via HTMX",
                [
                    'user_id' => $userId,
                    'page' => $page,
                    'per_page' => $perPage,
                    'status' => $status
                ],
                $userId,
                null,
                'booking'
            );
            
            // Check if we have results
            if (empty($bookings['data']) && $page === 0) {
                echo '<script>document.getElementById("no-bookings-message").classList.remove("hidden");</script>';
                return;
            }
            
            // Render each booking
            foreach ($bookings['data'] as $booking) {
                include BASE_PATH . '/public/views/partials/booking-list-item.php';
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to load user bookings for HTMX", [
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);
            
            echo '<div class="text-red-500 p-4">
                <p class="font-medium">Nie udało się załadować rezerwacji</p>
                <p class="text-sm">Spróbuj odświeżyć stronę lub skontaktuj się z obsługą klienta</p>
            </div>';
        }
    }
}
