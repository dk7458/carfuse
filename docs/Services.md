## Admin Service

**Class Purpose**: Manages admin user-related operations such as user retrieval, role updates, and dashboard data retrieval.

**Key Methods**:
- `validateAdmin(ServerRequestInterface $request)`
- `getAllUsers(int $page, int $adminId)`
- `updateUserRole(int $userId, string $role, int $adminId)`
- `deleteUser(int $userId, int $adminId)`
- `getDashboardData(int $adminId)`
- `createAdmin(array $data, int $adminId)`

**Input Parameters**:
- `validateAdmin`: `ServerRequestInterface $request`
- `getAllUsers`: `int $page`, `int $adminId`
- `updateUserRole`: `int $userId`, `string $role`, `int $adminId`
- `deleteUser`: `int $userId`, `int $adminId`
- `getDashboardData`: `int $adminId`
- `createAdmin`: `array $data`, `int $adminId`

**Return Data**:
- `validateAdmin`: `?array` (Admin data or null)
- `getAllUsers`: `array` (Paginated user data)
- `updateUserRole`: `bool` (Success status)
- `deleteUser`: `?array` (Deleted user data or null)
- `getDashboardData`: `array` (Dashboard statistics)
- `createAdmin`: `?array` (New admin data or null)

**Route Dependencies**:
- `/admin/users` (AdminController::getAllUsers)
- `/admin/users/{userId}/role` (AdminController::updateUserRole)
- `/admin/users/{userId}` (AdminController::deleteUser)
- `/admin/dashboard/data` (AdminController::getDashboardData)
- `/admin/create` (AdminController::createAdmin)

## Audit Service

**Class Purpose**: Provides centralized logging and auditing functionality for the application.

**Key Methods**:
- `logEvent(string $category, string $message, array $context = [], ?int $userId = null, ?int $bookingId = null, ?string $ipAddress = null, string $logLevel = 'info')`
- `getLogs(array $filters = [])`
- `exportLogs(array $filters)`
- `getLogById(int $logId)`

**Input Parameters**:
- `logEvent`: `string $category`, `string $message`, `array $context`, `?int $userId`, `?int $bookingId`, `?string $ipAddress`, `string $logLevel`
- `getLogs`: `array $filters`
- `exportLogs`: `array $filters`
- `getLogById`: `int $logId`

**Return Data**:
- `logEvent`: `?int` (Log entry ID or null)
- `getLogs`: `array` (Log data with pagination)
- `exportLogs`: `array` (Export information)
- `getLogById`: `?array` (Log details or null)

**Route Dependencies**:
- Used across various controllers for logging events.
- `/audit` (AuditController::index)
- `/audit/fetch` (AuditController::fetchLogs)
- `/audit/{id}` (AuditController::getLog)
- `/audit/export` (AuditController::exportLogs)

## Audit/LogManagement Service

**Class Purpose**: Manages the storage, retrieval, and deletion of audit logs.

**Key Methods**:
- `createLogEntry(string $category, string $message, array $context = [], ?int $userId = null, ?int $bookingId = null, ?string $ipAddress = null, string $logLevel = 'info')`
- `getLogs(array $filters = [])`
- `deleteLogs(array $filters, bool $forceBulkDelete = false)`
- `exportLogs(array $filters)`
- `getLogById(int $logId)`

**Input Parameters**:
- `createLogEntry`: `string $category`, `string $message`, `array $context`, `?int $userId`, `?int $bookingId`, `?string $ipAddress`, `string $logLevel`
- `getLogs`: `array $filters`
- `deleteLogs`: `array $filters`, `bool $forceBulkDelete`
- `exportLogs`: `array $filters`
- `getLogById`: `int $logId`

**Return Data**:
- `createLogEntry`: `?int` (Log entry ID or null)
- `getLogs`: `array` (Log data with pagination)
- `deleteLogs`: `int` (Number of deleted logs)
- `exportLogs`: `array` (Export information)
- `getLogById`: `?array` (Log details or null)

**Route Dependencies**:
- Indirectly used by AuditController through AuditService.

## Audit/TransactionAudit Service

**Class Purpose**: Handles auditing of transaction-related events, including payments and refunds.

**Key Methods**:
- `logEvent(string $category, string $message, array $context, ?int $userId = null, ?int $bookingId = null, string $logLevel = 'info')`
- `recordPaymentSuccess(array $paymentData)`

**Input Parameters**:
- `logEvent`: `string $category`, `string $message`, `array $context`, `?int $userId`, `?int $bookingId`, `string $logLevel`
- `recordPaymentSuccess`: `array $paymentData`

**Return Data**:
- `logEvent`: `?int` (Log entry ID or null)
- `recordPaymentSuccess`: `?int` (Log entry ID or null)

**Route Dependencies**:
- PaymentController (for logging payment processing and refunds)

## Audit/UserAudit Service

**Class Purpose**: Manages auditing of user-related events, including authentication and profile changes.

**Key Methods**:
- `logUserEvent(string $category, string $action, string $message, array $context = [], ?int $userId = null, string $logLevel = 'info')`
- `logAuthEvent(string $action, string $message, array $context = [], ?int $userId = null, string $logLevel = 'info')`

**Input Parameters**:
- `logUserEvent`: `string $category`, `string $action`, `string $message`, `array $context`, `?int $userId`, `string $logLevel`
- `logAuthEvent`: `string $action`, `string $message`, `array $context`, `?int $userId`, `string $logLevel`

**Return Data**:
- `logUserEvent`: `?int` (Log entry ID or null)
- `logAuthEvent`: `?int` (Log entry ID or null)

**Route Dependencies**:
- AuthController (for logging authentication events)
- UserController (for logging profile management events)


## Auth/AuthService

**Class Purpose**: Manages user authentication, registration, and password reset functionalities.

**Key Methods**:
- `login(array $data)`
- `register(array $data)`
- `refresh(array $data)`
- `resetPasswordRequest(array $data)`
- `resetPassword(array $data)`

**Input Parameters**:
- `login`: `array $data` (email, password)
- `register`: `array $data` (name, surname, email, password, confirm_password)
- `refresh`: `array $data` (refresh_token)
- `resetPasswordRequest`: `array $data` (email)
- `resetPassword`: `array $data` (token, password, confirm_password)

**Return Data**:
- `login`: `array` (token, refresh_token, user_id, name, email)
- `register`: `array` (user_id)
- `refresh`: `array` (token)
- `resetPasswordRequest`: `array` (message, debug_token)
- `resetPassword`: `array` (message)

**Route Dependencies**:
- `/auth/login` (AuthController::login)
- `/auth/register` (AuthController::register)
- `/auth/refresh` (AuthController::refresh)
- `/auth/reset-password-request` (AuthController::resetPasswordRequest)
- `/auth/reset-password` (AuthController::resetPassword)

## Auth/TokenService

**Class Purpose**: Generates, verifies, and manages JWT tokens and refresh tokens.

**Key Methods**:
- `generateToken($user)`
- `generateRefreshToken($user)`
- `verifyToken(string $token)`
- `validateRequest($request)`

**Input Parameters**:
- `generateToken`: `$user` (user object or array)
- `generateRefreshToken`: `$user` (user object or array)
- `verifyToken`: `string $token`
- `validateRequest`: `$request` (PSR-7 Request object)

**Return Data**:
- `generateToken`: `string` (JWT token)
- `generateRefreshToken`: `string` (Refresh token)
- `verifyToken`: `array` (Decoded token payload)
- `validateRequest`: `?array` (User data or null)

**Route Dependencies**:
- Used across various controllers for authentication and authorization.

## BookingService

**Class Purpose**: Manages booking-related operations such as creating, rescheduling, canceling, and retrieving bookings.

**Key Methods**:
- `getBookingById(int $id)`
- `rescheduleBooking(int $id, string $pickupDate, string $dropoffDate)`
- `cancelBooking(int $id)`
- `createBooking(array $bookingData)`
- `getUserBookings(int $userId)`

**Input Parameters**:
- `getBookingById`: `int $id`
- `rescheduleBooking`: `int $id`, `string $pickupDate`, `string $dropoffDate`
- `cancelBooking`: `int $id`
- `createBooking`: `array $bookingData`
- `getUserBookings`: `int $userId`

**Return Data**:
- `getBookingById`: `array` (Booking details)
- `rescheduleBooking`: `void`
- `cancelBooking`: `float` (Refund amount)
- `createBooking`: `array` (status, message, booking_id)
- `getUserBookings`: `array` (List of bookings)

**Route Dependencies**:
- `/bookings/{id}` (BookingController::viewBooking)
- `/bookings/{id}/reschedule` (BookingController::rescheduleBooking)
- `/bookings/{id}/cancel` (BookingController::cancelBooking)
- `/bookings/user` (BookingController::getUserBookings)
- `/bookings` (BookingController::createBooking)


## DocumentService

**Class Purpose**: Manages document-related operations including templates, contracts, and terms and conditions.

**Key Methods**:
- `uploadTemplate(string $name, string $content)`
- `generateContractSecure(int $bookingId, int $userId)`
- `getTemplates()`
- `getTemplateById(int $templateId)`

**Input Parameters**:
- `uploadTemplate`: `string $name`, `string $content`
- `generateContractSecure`: `int $bookingId`, `int $userId`
- `getTemplates`: None
- `getTemplateById`: `int $templateId`

**Return Data**:
- `uploadTemplate`: `void`
- `generateContractSecure`: `string` (File path to the generated contract)
- `getTemplates`: `array` (List of templates)
- `getTemplateById`: `array` (Template details)

**Route Dependencies**:
- `/documents/templates` (DocumentController::uploadTemplate, DocumentController::getTemplates)
- `/documents/contracts/{bookingId}` (DocumentController::generateContract)
- `/documents/templates/{templateId}` (DocumentController::getTemplate)

## EncryptionService

**Class Purpose**: Provides encryption and decryption functionalities for sensitive data.

**Key Methods**:
- `encrypt(string $data)`
- `decrypt(string $encryptedData)`

**Input Parameters**:
- `encrypt`: `string $data`
- `decrypt`: `string $encryptedData`

**Return Data**:
- `encrypt`: `string` (Encrypted data)
- `decrypt`: `?string` (Decrypted data or null)

**Route Dependencies**:
- Used internally by other services, not directly exposed in API endpoints.

## FileStorage

**Class Purpose**: Manages file storage operations, including storing, retrieving, and deleting files.

**Key Methods**:
- `storeFile(string $directory, string $fileName, string $content, bool $encrypt = false)`
- `retrieveFile(string $filePath, bool $decrypt = false)`
- `deleteFile(string $filePath)`

**Input Parameters**:
- `storeFile`: `string $directory`, `string $fileName`, `string $content`, `bool $encrypt`
- `retrieveFile`: `string $filePath`, `bool $decrypt`
- `deleteFile`: `string $filePath`

**Return Data**:
- `storeFile`: `string` (File path)
- `retrieveFile`: `string` (File content)
- `deleteFile`: `void`

**Route Dependencies**:
- Used internally by other services, not directly exposed in API endpoints.

## MetricsService

**Class Purpose**: Retrieves dashboard metrics for admin interfaces.

**Key Methods**:
- `getDashboardMetrics()`

**Input Parameters**:
- `getDashboardMetrics`: None

**Return Data**:
- `getDashboardMetrics`: `array` (Dashboard metrics)

**Route Dependencies**:
- `/admin/dashboard/data` (AdminDashboardController::getDashboardData)

## NotificationService

**Class Purpose**: Handles sending and managing user notifications.

**Key Methods**:
- `sendNotification(int $userId, string $type, string $message, array $options = [])`
- `getUserNotifications(int $userId)`
- `markNotificationAsRead(int $notificationId)`

**Input Parameters**:
- `sendNotification`: `int $userId`, `string $type`, `string $message`, `array $options`
- `getUserNotifications`: `int $userId`
- `markNotificationAsRead`: `int $notificationId`

**Return Data**:
- `sendNotification`: `bool` (Success status)
- `getUserNotifications`: `array` (List of notifications)
- `markNotificationAsRead`: `bool` (Success status)

**Route Dependencies**:
- `/notifications` (NotificationController::viewNotifications)
- `/notifications/user` (NotificationController::getUserNotifications)
- `/notifications/mark-read` (NotificationController::markNotificationAsRead)


## Payment/PaymentGatewayService

**Class Purpose**: Handles interactions with external payment gateways for processing payments and handling callbacks.

**Key Methods**:
- `processPayment(string $gatewayName, array $paymentData)`
- `handleCallback(string $gatewayName, array $callbackData)`

**Input Parameters**:
- `processPayment`: `string $gatewayName`, `array $paymentData`
- `handleCallback`: `string $gatewayName`, `array $callbackData`

**Return Data**:
- `processPayment`: `array` (Payment processing result)
- `handleCallback`: `array` (Callback processing result)

**Route Dependencies**:
- `/payments/gateway/process` (PaymentController::processGatewayPayment)
- `/payments/gateway/callback/{gateway}` (PaymentController::handleGatewayCallback)

## Payment/PaymentProcessingService

**Class Purpose**: Manages the core logic for processing payments, including fraud validation and database transactions.

**Key Methods**:
- `processPayment(array $paymentData)`

**Input Parameters**:
- `processPayment`: `array $paymentData`

**Return Data**:
- `processPayment`: `array` (Payment processing result)

**Route Dependencies**:
- `/payments/process` (PaymentController::processPayment)

## Payment/RefundService

**Class Purpose**: Handles refund-related operations, including processing refund requests and verifying eligibility.

**Key Methods**:
- `refund(array $refundData)`

**Input Parameters**:
- `refund`: `array $refundData`

**Return Data**:
- `refund`: `array` (Refund processing result)

**Route Dependencies**:
- `/payments/refund` (PaymentController::refundPayment)

## Payment/TransactionService

**Class Purpose**: Manages transaction logging and history retrieval.

**Key Methods**:
- `getTransactionDetails(int $transactionId, int $userId, bool $isAdmin = false)`
- `getHistoryByUser(int $userId)`

**Input Parameters**:
- `getTransactionDetails`: `int $transactionId`, `int $userId`, `bool $isAdmin`
- `getHistoryByUser`: `int $userId`

**Return Data**:
- `getTransactionDetails`: `?array` (Transaction details or null)
- `getHistoryByUser`: `array` (Transaction history)

**Route Dependencies**:
- `/payments/{transactionId}` (PaymentController::getPaymentDetails)
- `/payments/transactions` (PaymentController::getUserTransactions)

## PaymentService

**Class Purpose**: Acts as a facade for payment-related operations, delegating tasks to specialized subservices.

**Key Methods**:
- `processPayment(array $paymentData)`
- `refundPayment(array $refundData)`
- `addPaymentMethod(array $methodData)`
- `getUserPaymentMethods(int $userId)`

**Input Parameters**:
- `processPayment`: `array $paymentData`
- `refundPayment`: `array $refundData`
- `addPaymentMethod`: `array $methodData`
- `getUserPaymentMethods`: `int $userId`

**Return Data**:
- `processPayment`: `array` (Payment details)
- `refundPayment`: `array` (Refund details)
- `addPaymentMethod`: `array` (Payment method details)
- `getUserPaymentMethods`: `array` (List of payment methods)

**Route Dependencies**:
- `/payments/process` (PaymentController::processPayment)
- `/payments/refund` (PaymentController::refundPayment)
- `/payments/methods` (PaymentController::addPaymentMethod, PaymentController::getUserPaymentMethods)

## RateLimiter

**Class Purpose**: Implements IP-based rate limiting to protect against abuse.

**Key Methods**:
- `isRateLimited(string $email, string $ipAddress, string $action)`

**Input Parameters**:
- `isRateLimited`: `string $email`, `string $ipAddress`, `string $action`

**Return Data**:
- `isRateLimited`: `bool` (Rate limited status)

**Route Dependencies**:
- `/auth/login` (AuthController::login)

## ReportService

**Class Purpose**: Generates reports for bookings, payments, and users in various formats.

**Key Methods**:
- `generateReport(string $reportType, array $dateRange, string $format, array $filters = [])`
- `generateUserReport(int $userId, string $reportType, array $dateRange, string $format)`

**Input Parameters**:
- `generateReport`: `string $reportType`, `array $dateRange`, `string $format`, `array $filters`
- `generateUserReport`: `int $userId`, `string $reportType`, `array $dateRange`, `string $format`

**Return Data**:
- `generateReport`: `string` (File path to the generated report)
- `generateUserReport`: `string` (File path to the generated report)

**Route Dependencies**:
- `/reports/generate` (ReportController::generateReport)
- `/reports/user/generate` (ReportController::generateUserReport)

## RevenueService

**Class Purpose**: Provides methods for calculating and retrieving revenue-related data.

**Key Methods**:
- `getMonthlyRevenueTrends()`
- `getTotalRevenue()`
- `getTotalRefunds()`
- `getNetRevenue()`

**Input Parameters**:
- `getMonthlyRevenueTrends`: None
- `getTotalRevenue`: None
- `getTotalRefunds`: None
- `getNetRevenue`: None

**Return Data**:
- `getMonthlyRevenueTrends`: `array` (Monthly revenue trends)
- `getTotalRevenue`: `float` (Total revenue)
- `getTotalRefunds`: `float` (Total refunds)
- `getNetRevenue`: `float` (Net revenue)

**Route Dependencies**:
- Used internally by other services, not directly exposed in API endpoints.

## Security/FraudDetectionService

**Class Purpose**: Analyzes transactions for potential fraud and calculates risk scores.

**Key Methods**:
- `analyzeTransaction(array $transactionData, array $options = [])`

**Input Parameters**:
- `analyzeTransaction`: `array $transactionData`, `array $options`

**Return Data**:
- `analyzeTransaction`: `array` (Analysis results with indicators, score, and risk level)

**Route Dependencies**:
- Used internally by PaymentProcessingService, not directly exposed in API endpoints.

## Security/KeyManager

**Class Purpose**: Manages encryption keys, including generation, storage, rotation, and revocation.

**Key Methods**:
- `getKey(string $identifier)`

**Input Parameters**:
- `getKey`: `string $identifier`

**Return Data**:
- `getKey`: `string` (Encryption key)

**Route Dependencies**:
- Used internally by other services, not directly exposed in API endpoints.

## SignatureService

**Class Purpose**: Manages electronic signatures, including uploading, verifying, and retrieving signatures.

**Key Methods**:
- `uploadSignature(string $filePath, int $userId)`
- `getSignature(int $userId)`

**Input Parameters**:
- `uploadSignature`: `string $filePath`, `int $userId`
- `getSignature`: `int $userId`

**Return Data**:
- `uploadSignature`: `string` (Storage path of the uploaded signature)
- `getSignature`: `?string` (Signature path or null)

**Route Dependencies**:
- `/signatures/upload` (SignatureController::uploadSignature)
- `/signatures/{userId}` (SignatureController::getSignature)

## TemplateService

**Class Purpose**: Manages document templates, including loading and rendering templates with dynamic data.

**Key Methods**:
- `renderTemplate(string $templateId, array $data)`

**Input Parameters**:
- `renderTemplate`: `string $templateId`, `array $data`

**Return Data**:
- `renderTemplate`: `string` (Rendered template content)

**Route Dependencies**:
- Used internally by DocumentService, not directly exposed in API endpoints.

## UserService

**Class Purpose**: Manages user-related operations, including creation, update, authentication, and password resets.

**Key Methods**:
- `createUser(array $data)`
- `updateUser(int $id, array $data)`
- `updateUserRole(int $id, string $role)`
- `deleteUser(int $id)`
- `requestPasswordReset(string $email)`

**Input Parameters**:
- `createUser`: `array $data`
- `updateUser`: `int $id`, `array $data`
- `updateUserRole`: `int $id`, `string $role`
- `deleteUser`: `int $id`
- `requestPasswordReset`: `string $email`

**Return Data**:
- `createUser`: `array` (Status and message)
- `updateUser`: `array` (Status and message)
- `updateUserRole`: `array` (Status and message)
- `deleteUser`: `array` (Status and message)
- `requestPasswordReset`: `array` (Status and message)

**Route Dependencies**:
- `/users/register` (UserController::registerUser)
- `/users/profile` (UserController::updateProfile)
- `/users/password/request` (UserController::requestPasswordReset)

## Validator

**Class Purpose**: Validates input data against defined rules.

**Key Methods**:
- `validate(array $data, array $rules)`

**Input Parameters**:
- `validate`: `array $data`, `array $rules`

**Return Data**:
- `validate`: `bool` (Validation status)

**Route Dependencies**:
- Used across various controllers for input validation, not directly exposed in API endpoints.