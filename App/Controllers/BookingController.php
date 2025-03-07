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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

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
    protected function jsonResponse(array $data, int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
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
            
            $data = $_POST; // minimal custom validation assumed
            
            // Let the service handle the business logic
            $this->bookingService->rescheduleBooking($id, $data['pickup_date'], $data['dropoff_date']);
            
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
            $refundAmount = $this->bookingService->cancelBooking($id);

            // Process refund if applicable through payment service
            if ($refundAmount > 0) {
                // The RefundLog creation should ideally be handled by a PaymentService
                // This is a potential future refactoring
                $refundLog = new RefundLog();
                $refundId = $refundLog->create([
                    'booking_id' => $id,
                    'amount'     => $refundAmount,
                    'status'     => 'processed'
                ]);
                
                // Audit the refund processed
                $this->auditService->logEvent(
                    'refund_processed',
                    "Refund processed for booking #{$id}",
                    [
                        'booking_id' => $id,
                        'user_id' => $user['id'],
                        'refund_amount' => $refundAmount,
                        'refund_id' => $refundId
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
            
            return $this->jsonResponse([
                'status' => 'success',
                'message' => 'Booking canceled successfully'
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
            
            $bookings = $this->bookingService->getUserBookings($user['id']);
            
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
                'data' => ['bookings' => $bookings]
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

            $data = $_POST; // assuming custom validation is performed elsewhere
            $data['user_id'] = $user['id'];
            
            // Let the BookingService handle all booking creation logic
            $result = $this->bookingService->createBooking($data);
            
            if ($result['status'] == 'error') {
                return $this->jsonResponse($result, 400);
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
}
