<?php

namespace App\Controllers;

use App\Services\PaymentService;
use App\Helpers\TokenValidator;
use App\Helpers\ExceptionHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

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
    private ResponseFactoryInterface $responseFactory;
    protected LoggerInterface $logger;
    protected ExceptionHandler $exceptionHandler;

    public function __construct(
        LoggerInterface $logger,
        PaymentService $paymentService,
        Validator $validator,
        NotificationService $notificationService,
        ResponseFactoryInterface $responseFactory,
        ExceptionHandler $exceptionHandler
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->paymentService = $paymentService;
        $this->validator = $validator;
        $this->notificationService = $notificationService;
        $this->responseFactory = $responseFactory;
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
            
            $data = $this->validateRequest($_POST, [
                'booking_id'       => 'required|integer',
                'amount'           => 'required|numeric|min:0.01',
                'payment_method_id' => 'required|integer',
                'currency'         => 'nullable|string|size:3',
            ]);
            
            // Add user_id to payment data
            $paymentData = array_merge($data, ['user_id' => $user->id]);
            
            // Delegate all payment processing to the service
            $result = $this->paymentService->processPayment($paymentData);
            
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
            
            $data = $this->validateRequest($_POST, [
                'payment_id'    => 'required|integer',
                'amount'        => 'required|numeric|min:0.01',
                'reason'        => 'required|string',
            ]);
            
            // Add admin_id to refund data
            $refundData = array_merge($data, ['admin_id' => $admin->id]);
            
            // Delegate all refund processing to the service
            $result = $this->paymentService->refundPayment($refundData);
            
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
            
            // Get transactions from service
            $transactions = $this->paymentService->getTransactionHistory($user->id);
            
            return $this->jsonResponse([
                'status'  => 'success',
                'message' => 'Transactions fetched',
                'data'    => ['transactions' => $transactions]
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
            
            $data = $this->validateRequest($_POST, [
                'type'        => 'required|string',
                'card_last4'  => 'required_if:type,credit_card|numeric',
                'card_brand'  => 'required_if:type,credit_card|string',
                'expiry_date' => 'required_if:type,credit_card|string',
                'is_default'  => 'nullable|boolean',
            ]);
            
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
            
            $data = $this->validateRequest($_POST, [
                'gateway'          => 'required|string',
                'booking_id'       => 'required|integer',
                'amount'           => 'required|numeric|min:0.01',
                'currency'         => 'nullable|string|size:3',
            ]);
            
            // Add user_id to payment data
            $paymentData = array_merge($data, ['user_id' => $user->id]);
            
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
            
            // Delegate to service
            $result = $this->paymentService->handlePaymentCallback($gateway, $callbackData);
            
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
}
