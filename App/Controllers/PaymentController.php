<?php

namespace App\Controllers;

use App\Services\PaymentService;
use App\Helpers\TokenValidator;
use App\Helpers\ExceptionHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Services\NotificationService;
use App\Services\Validator;

/**
 * Payment Controller
 *
 * Handles payment processing, refunds, and user transactions.
 */
class PaymentController extends Controller
{
    private PaymentService $paymentService;
    private Validator $validator;
    private NotificationService $notificationService;
    protected LoggerInterface $logger;
    protected ExceptionHandler $exceptionHandler;

    public function __construct(
        LoggerInterface $logger,
        PaymentService $paymentService,
        Validator $validator,
        NotificationService $notificationService,
        ExceptionHandler $exceptionHandler
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->paymentService = $paymentService;
        $this->validator = $validator;
        $this->notificationService = $notificationService;
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * Process a payment.
     */
    public function processPayment(): ResponseInterface
    {
        try {
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            $data = $this->validator->validate($_POST, [
                'booking_id'       => 'required|integer',
                'amount'           => 'required|numeric|min:0.01',
                'payment_method_id' => 'required|integer',
                'currency'         => 'nullable|string|size:3',
            ]);
            
            if ($this->validator->failed()) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $this->validator->errors()
                ], 400);
            }
            
            // Add user_id to payment data
            $paymentData = array_merge($data, ['user_id' => $user->id]);
            
            // Delegate all payment processing to the service
            $result = $this->paymentService->processPayment($paymentData);
            
            // Notify user about successful payment
            $this->notificationService->sendPaymentConfirmation($user->id, $paymentData['booking_id'], $paymentData['amount']);
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Payment processed',
                'data'    => ['payment' => $result]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Payment processing failed'
            ], 500);
        }
    }

    /**
     * Refund a payment.
     */
    public function refundPayment(): ResponseInterface
    {
        try {
            $admin = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$admin || !$admin->isAdmin()) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized - admin rights required'
                ], 401);
            }
            
            $data = $this->validator->validate($_POST, [
                'payment_id'    => 'required|integer',
                'amount'        => 'required|numeric|min:0.01',
                'reason'        => 'required|string',
            ]);

            if ($this->validator->failed()) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $this->validator->errors()
                ], 400);
            }
            
            // Add admin_id to refund data
            $refundData = array_merge($data, ['admin_id' => $admin->id]);
            
            // Delegate all refund processing to the service
            $result = $this->paymentService->refundPayment($refundData);
            
            // Notify user about refund
            if (isset($result['user_id']) && isset($result['booking_id'])) {
                $this->notificationService->sendRefundNotification(
                    $result['user_id'],
                    $result['booking_id'],
                    $refundData['amount'],
                    $refundData['reason']
                );
            }
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Refund processed',
                'data'    => ['refund' => $result]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Refund processing failed'
            ], 500);
        }
    }

    /**
     * Fetch all user transactions.
     */
    public function getUserTransactions(): ResponseInterface
    {
        try {
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            // Get pagination parameters
            $page = (int)($this->request->getQueryParams()['page'] ?? 1);
            $limit = (int)($this->request->getQueryParams()['limit'] ?? 20);
            
            // Get transactions from service
            $transactions = $this->paymentService->getTransactionHistory($user->id, $page, $limit);
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Transactions fetched',
                'data'    => $transactions
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to fetch user transactions'
            ], 500);
        }
    }

    /**
     * Fetch payment details.
     */
    public function getPaymentDetails(int $transactionId): ResponseInterface
    {
        try {
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            // Delegate to service which handles permission checking
            $details = $this->paymentService->getTransactionDetails($transactionId, $user->id, $user->isAdmin());
            
            // If details are null, user doesn't have permission
            if ($details === null) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'You do not have permission to view this transaction'
                ], 403);
            }
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Payment details fetched',
                'data'    => ['details' => $details]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to fetch payment details'
            ], 500);
        }
    }

    /**
     * Add a payment method.
     */
    public function addPaymentMethod(): ResponseInterface
    {
        try {
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            $data = $this->validator->validate($_POST, [
                'type'        => 'required|string',
                'card_last4'  => 'required_if:type,credit_card|numeric',
                'card_brand'  => 'required_if:type,credit_card|string',
                'expiry_date' => 'required_if:type,credit_card|string',
                'is_default'  => 'nullable|boolean',
            ]);

            if ($this->validator->failed()) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $this->validator->errors()
                ], 400);
            }
            
            // Add user_id to payment method data
            $methodData = array_merge($data, ['user_id' => $user->id]);
            
            // Delegate to service
            $paymentMethod = $this->paymentService->addPaymentMethod($methodData);
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Payment method added successfully',
                'data'    => ['payment_method' => $paymentMethod]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to add payment method'
            ], 500);
        }
    }
    
    /**
     * Get all payment methods for a user.
     */
    public function getUserPaymentMethods(): ResponseInterface
    {
        try {
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            // Delegate to service
            $paymentMethods = $this->paymentService->getUserPaymentMethods($user->id);
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Payment methods retrieved successfully',
                'data'    => ['payment_methods' => $paymentMethods]
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to retrieve payment methods'
            ], 500);
        }
    }

    /**
     * Process a payment using a specific gateway.
     */
    public function processGatewayPayment(): ResponseInterface
    {
        try {
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            $data = $this->validator->validate($_POST, [
                'gateway'          => 'required|string|in:stripe,paypal,payu',
                'booking_id'       => 'required|integer',
                'amount'           => 'required|numeric|min:0.01',
                'currency'         => 'required|string|size:3',
                'return_url'       => 'required|url',
                'cancel_url'       => 'required|url',
            ]);

            if ($this->validator->failed()) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Validation error',
                    'errors' => $this->validator->errors()
                ], 400);
            }
            
            // Add user_id to payment data
            $paymentData = array_merge($data, [
                'user_id' => $user->id,
                'ip_address' => $this->request->getServerParams()['REMOTE_ADDR'] ?? null,
                'user_agent' => $this->request->getHeaderLine('User-Agent')
            ]);
            
            // Delegate to gateway service
            $result = $this->paymentService->processPaymentGateway(
                $paymentData['gateway'],
                $paymentData
            );
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Gateway payment initiated',
                'data'    => $result
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Gateway payment processing failed'
            ], 500);
        }
    }

    /**
     * Handle gateway callback.
     */
    public function handleGatewayCallback(string $gateway): ResponseInterface
    {
        try {
            // No authentication for callbacks - they come from payment providers
            
            // Get payload data (could be POST, GET, or JSON)
            $callbackData = [];
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $callbackData = $_POST;
            } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $callbackData = $_GET;
            } else {
                $callbackData = json_decode(file_get_contents('php://input'), true) ?? [];
            }
            
            // Add request metadata for verification
            $callbackData['_meta'] = [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'timestamp' => time(),
                'request_method' => $_SERVER['REQUEST_METHOD']
            ];
            
            // Delegate to service
            $result = $this->paymentService->handlePaymentCallback($gateway, $callbackData);
            
            // If successful and we have user data, send notification
            if (isset($result['success']) && $result['success'] && isset($result['user_id'])) {
                $this->notificationService->sendPaymentConfirmation(
                    $result['user_id'],
                    $result['booking_id'] ?? null,
                    $result['amount'] ?? 0
                );
            }
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Callback processed',
                'data'    => $result
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to process gateway callback'
            ], 500);
        }
    }

    /**
     * Delete a payment method
     */
    public function deletePaymentMethod(int $methodId): ResponseInterface
    {
        try {
            $user = TokenValidator::validateToken($this->request->getHeader('Authorization'));
            if (!$user) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
            
            // Delegate to service
            $result = $this->paymentService->deletePaymentMethod($methodId, $user->id);
            
            if (!$result) {
                return $this->jsonResponse([
                    'status' => 'error',
                    'message' => 'Payment method not found or you do not have permission to delete it'
                ], 404);
            }
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Payment method deleted successfully'
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $this->jsonResponse([
                'status'  => 'error',
                'message' => 'Failed to delete payment method'
            ], 500);
        }
    }
}
