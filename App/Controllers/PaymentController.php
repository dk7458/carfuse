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

    /**
     * Get user transactions for HTMX requests
     */
    public function getUserTransactionsHtmx(): void
    {
        try {
            // Get user from session
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                echo '<div class="text-red-500 p-4">Nie jesteś zalogowany. Proszę zalogować się ponownie.</div>';
                return;
            }
            
            // Get pagination and filter parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $type = $_GET['type'] ?? 'all';
            $sortBy = $_GET['sort_by'] ?? 'date';
            $sortDir = $_GET['sort_dir'] ?? 'desc';
            
            // Map frontend sort fields to backend fields
            $sortMapping = [
                'date' => 'created_at',
                'amount' => 'amount',
                'status' => 'status'
            ];
            
            $backendSortField = $sortMapping[$sortBy] ?? 'created_at';
            
            // Get transactions through the service
            $transactions = $this->paymentService->getTransactionHistory(
                $userId, 
                $page, 
                $limit, 
                $type !== 'all' ? $type : null,
                $backendSortField,
                $sortDir
            );
            
            // Check if we have results
            if (empty($transactions['data'])) {
                echo '<script>document.getElementById("no-transactions").classList.remove("hidden");</script>';
                return;
            }
            
            // Render each transaction
            foreach ($transactions['data'] as $transaction) {
                include BASE_PATH . '/public/views/partials/payment-item.php';
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to load user transactions for HTMX", [
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);
            
            echo '<div class="text-red-500 p-4">
                <p class="font-medium">Nie udało się załadować historii transakcji</p>
                <p class="text-sm">Spróbuj odświeżyć stronę lub skontaktuj się z obsługą klienta</p>
            </div>';
        }
    }

    /**
     * Get user payments with filters for HTMX requests (Polish interface)
     */
    public function getUserPayments(): void
    {
        try {
            // Get user from session
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                echo '<div class="text-red-500 p-4">Nie jesteś zalogowany. Proszę zalogować się ponownie.</div>';
                return;
            }
            
            // Get pagination and filter parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $type = $_GET['type'] ?? 'all';
            $query = $_GET['query'] ?? null;
            $sortBy = $_GET['sort_by'] ?? 'date';
            $sortDir = $_GET['sort_dir'] ?? 'desc';
            
            // Map frontend sort fields to backend fields
            $sortMapping = [
                'date' => 'created_at',
                'amount' => 'amount',
                'status' => 'status'
            ];
            
            $backendSortField = $sortMapping[$sortBy] ?? 'created_at';
            
            // Get transactions through the service
            $transactions = $this->paymentService->getTransactionHistory(
                $userId, 
                $page, 
                $limit, 
                $type !== 'all' ? $type : null,
                $backendSortField,
                $sortDir,
                $query
            );
            
            // Check if we have results
            if (empty($transactions['data'])) {
                echo '<script>document.getElementById("no-transactions").classList.remove("hidden");</script>';
                return;
            }
            
            // Render each transaction
            foreach ($transactions['data'] as $transaction) {
                include BASE_PATH . '/public/views/partials/payment-item.php';
            }
            
            // If pagination information is available and there are more pages
            if (isset($transactions['meta']) && $transactions['meta']['has_more_pages']) {
                echo '<script>document.getElementById("load-more-btn").setAttribute("hx-get", "/payments/history?page=' . ($page + 1) . '");</script>';
            } else {
                echo '<script>document.getElementById("load-more-btn").classList.add("hidden");</script>';
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to load user payments for HTMX", [
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);
            
            echo '<div class="text-red-500 p-4">
                <p class="font-medium">Nie udało się załadować historii płatności</p>
                <p class="text-sm">Spróbuj odświeżyć stronę lub skontaktuj się z obsługą klienta</p>
            </div>';
        }
    }

    /**
     * Search transactions by query string
     */
    public function searchPayments(): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                echo '<div class="text-red-500 p-4">Nie jesteś zalogowany. Proszę zalogować się ponownie.</div>';
                return;
            }
            
            $query = $_GET['q'] ?? '';
            $filter = $_GET['payment-filter'] ?? 'all';
            
            $type = ($filter !== 'all') ? $filter : null;
            
            $transactions = $this->paymentService->searchTransactions($userId, $query, $type);
            
            if (empty($transactions)) {
                echo '<tr><td colspan="6" class="text-center py-4 text-gray-500">Nie znaleziono pasujących transakcji</td></tr>';
                return;
            }
            
            foreach ($transactions as $transaction) {
                include BASE_PATH . '/public/views/partials/payment-item.php';
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to search payments", [
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);
            
            echo '<tr><td colspan="6" class="text-center py-4 text-red-500">Wystąpił błąd podczas wyszukiwania</td></tr>';
        }
    }

    /**
     * Filter transactions by type
     */
    public function filterPayments(): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                echo '<div class="text-red-500 p-4">Nie jesteś zalogowany. Proszę zalogować się ponownie.</div>';
                return;
            }
            
            $filter = $_GET['payment-filter'] ?? 'all';
            $type = ($filter !== 'all') ? $filter : null;
            
            $transactions = $this->paymentService->getTransactionHistory($userId, 1, 10, $type);
            
            if (empty($transactions['data'])) {
                echo '<tr><td colspan="6" class="text-center py-4 text-gray-500">Nie znaleziono transakcji danego typu</td></tr>';
                return;
            }
            
            foreach ($transactions['data'] as $transaction) {
                include BASE_PATH . '/public/views/partials/payment-item.php';
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to filter payments", [
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);
            
            echo '<tr><td colspan="6" class="text-center py-4 text-red-500">Wystąpił błąd podczas filtrowania</td></tr>';
        }
    }

    /**
     * Sort transactions by specified criteria
     */
    public function sortPayments(): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                echo '<div class="text-red-500 p-4">Nie jesteś zalogowany. Proszę zalogować się ponownie.</div>';
                return;
            }
            
            $sortValue = $_GET['payment-sort'] ?? 'date_desc';
            list($field, $direction) = explode('_', $sortValue);
            
            $sortMapping = [
                'date' => 'created_at',
                'amount' => 'amount'
            ];
            
            $backendField = $sortMapping[$field] ?? 'created_at';
            
            $transactions = $this->paymentService->getTransactionHistory(
                $userId, 
                1, 
                10, 
                null, 
                $backendField, 
                $direction
            );
            
            if (empty($transactions['data'])) {
                echo '<tr><td colspan="6" class="text-center py-4 text-gray-500">Brak transakcji do wyświetlenia</td></tr>';
                return;
            }
            
            foreach ($transactions['data'] as $transaction) {
                include BASE_PATH . '/public/views/partials/payment-item.php';
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to sort payments", [
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);
            
            echo '<tr><td colspan="6" class="text-center py-4 text-red-500">Wystąpił błąd podczas sortowania</td></tr>';
        }
    }
    
    /**
     * Get payment methods for a user (HTMX format)
     */
    public function getPaymentMethods(): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                echo '<div class="text-red-500 p-4">Nie jesteś zalogowany. Proszę zalogować się ponownie.</div>';
                return;
            }
            
            // Get payment methods from service
            $methods = $this->paymentService->getUserPaymentMethods($userId);
            
            if (empty($methods)) {
                echo '<div class="col-span-full text-center py-4 text-gray-500">
                    Nie masz jeszcze żadnych zapisanych metod płatności.
                </div>';
                return;
            }
            
            // Render each payment method
            foreach ($methods as $method) {
                include BASE_PATH . '/public/views/partials/payment-method-card.php';
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to load payment methods", [
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);
            
            echo '<div class="col-span-full text-red-500 p-4">
                <p class="font-medium">Nie udało się załadować metod płatności</p>
                <p class="text-sm">Spróbuj odświeżyć stronę lub skontaktuj się z obsługą klienta</p>
            </div>';
        }
    }
    
    /**
     * Get payment method details (HTMX format)
     */
    public function getPaymentMethodDetails(int $id): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                echo '<div class="text-red-500 p-4">Nie jesteś zalogowany. Proszę zalogować się ponownie.</div>';
                return;
            }
            
            // Get payment method details
            $method = $this->paymentService->getPaymentMethodDetails($id, $userId);
            
            if (!$method) {
                echo '<div class="text-red-500 p-4">Nie znaleziono metody płatności lub nie masz do niej dostępu.</div>';
                return;
            }
            
            // Include the payment method details template
            include BASE_PATH . '/public/views/partials/payment-method-details.php';
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to load payment method details", [
                'error' => $e->getMessage(),
                'method_id' => $id,
                'user_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);
            
            echo '<div class="text-red-500 p-4">
                <p class="font-medium">Nie udało się załadować szczegółów metody płatności</p>
                <p class="text-sm">Spróbuj odświeżyć stronę lub skontaktuj się z obsługą klienta</p>
            </div>';
        }
    }
    
    /**
     * Get payment details for a specific transaction (HTMX format)
     */
    public function getPaymentDetails(int $id): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                echo '<div class="text-red-500 p-4">Nie jesteś zalogowany. Proszę zalogować się ponownie.</div>';
                return;
            }
            
            // Get transaction details from service (checks user permission internally)
            $details = $this->paymentService->getTransactionDetails($id, $userId, false);
            
            if (!$details) {
                echo '<div class="text-red-500 p-4">Nie znaleziono szczegółów transakcji lub nie masz do nich dostępu.</div>';
                return;
            }
            
            // Include the payment details template
            include BASE_PATH . '/public/views/partials/payment-details.php';
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to load payment details", [
                'error' => $e->getMessage(),
                'payment_id' => $id,
                'user_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);
            
            echo '<div class="text-red-500 p-4">
                <p class="font-medium">Nie udało się załadować szczegółów płatności</p>
                <p class="text-sm">Spróbuj odświeżyć stronę lub skontaktuj się z obsługą klienta</p>
            </div>';
        }
    }
    
    /**
     * Download payment invoice
     */
    public function downloadInvoice(int $id): void
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
                exit;
            }
            
            // Check if user has access to this invoice
            $hasAccess = $this->paymentService->verifyTransactionAccess($id, $userId);
            if (!$hasAccess) {
                header('HTTP/1.1 403 Forbidden');
                echo 'Brak dostępu do tego dokumentu';
                exit;
            }
            
            // Generate and send invoice PDF
            $invoice = $this->paymentService->generateInvoice($id);
            
            if (!$invoice) {
                header('HTTP/1.1 404 Not Found');
                echo 'Nie można wygenerować faktury dla tej transakcji';
                exit;
            }
            
            // Log the invoice download
            $this->auditService->logEvent(
                'invoice_downloaded',
                "User downloaded invoice",
                ['payment_id' => $id, 'user_id' => $userId],
                $userId,
                $id,
                'payment'
            );
            
            // Set headers for PDF download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="faktura-' . $invoice['invoice_number'] . '.pdf"');
            header('Content-Length: ' . strlen($invoice['pdf_content']));
            
            // Output PDF content
            echo $invoice['pdf_content'];
            exit;
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to download invoice", [
                'error' => $e->getMessage(),
                'payment_id' => $id,
                'user_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);
            
            header('HTTP/1.1 500 Internal Server Error');
            echo 'Wystąpił błąd podczas pobierania faktury. Proszę spróbować później.';
            exit;
        }
    }
}
