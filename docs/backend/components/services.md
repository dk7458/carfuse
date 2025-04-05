# Services

## Table of Contents
- [Overview](#overview)
- [Service Structure](#service-structure)
- [Available Services](#available-services)
  - [Core Services](#core-services)
    - [UserService](#userservice)
    - [BookingService](#bookingservice)
    - [TemplateService](#templateservice)
    - [DocumentService](#documentservice)
    - [SignatureService](#signatureservice)
    - [NotificationService](#notificationservice)
    - [ReportService](#reportservice)
    - [SettingsService](#settingsservice)
    - [RevenueService](#revenueservice)
  - [Auth Services](#auth-services)
    - [AuthService](#authservice)
    - [TokenService](#tokenservice)
  - [Payment Services](#payment-services)
    - [PaymentService](#paymentservice)
    - [PaymentProcessingService](#paymentprocessingservice)
    - [RefundService](#refundservice)
    - [TransactionService](#transactionservice)
    - [PaymentGatewayService](#paymentgatewayservice)
  - [Audit Services](#audit-services)
    - [AuditService](#auditservice)
    - [TransactionAuditService](#transactionauditservice)
    - [LogManagementService](#logmanagementservice)
    - [UserAuditService](#userauditservice)
  - [Security Services](#security-services)
    - [EncryptionService](#encryptionservice)
    - [FraudDetectionService](#frauddetectionservice)
    - [KeyManager](#keymanager)
    - [RateLimiter](#ratelimiter)
  - [Utility Services](#utility-services)
    - [FileStorage](#filestorage)
    - [MetricsService](#metricsservice)
    - [Validator](#validator)
  - [Administrative Services](#administrative-services)
    - [AdminService](#adminservice)
    - [AuditService](#auditservice)
- [Service Interactions](#service-interactions)

## Overview
The service layer forms the core business logic of the application. Services encapsulate complex operations, enforce business rules, and coordinate interactions between controllers and models. All services follow SOLID principles and use dependency injection to promote maintainability and testability.

## Service Structure
Services are organized into domains and subdomains:
- Core services (UserService, BookingService, etc.) implement primary business functionality
- Specialized services (Auth, Payment, etc.) handle specific domains
- Support services (Audit, Metrics, etc.) provide cross-cutting functionality

Each service typically:
1. Receives dependencies via constructor injection
2. Provides a focused set of public methods
3. Handles exceptions and logs appropriately
4. Uses models for data access instead of direct DB operations

## Available Services

### Core Services

#### UserService
**Purpose:** Manages user accounts including creation, authentication, profile updates, and role management.

| Method | Description |
|--------|-------------|
| `createUser(array $data): array` | Creates a new user with validation |
| `updateUser(int $id, array $data): array` | Updates user profile information |
| `updateUserRole(int $id, string $role): array` | Changes a user's role with audit logging |
| `deleteUser(int $id): array` | Soft-deletes a user account |
| `changePassword(int $id, string $currentPassword, string $newPassword): array` | Updates user password with verification |
| `authenticate(string $email, string $password): array` | Authenticates user and returns JWT token |
| `requestPasswordReset(string $email): array` | Initiates password reset process |

**Dependencies:** DatabaseHelper, LoggerInterface, ExceptionHandler, AuditService, User model

#### BookingService
**Purpose:** Manages the vehicle booking process including creation, modification, and cancellation of bookings.

| Method | Description |
|--------|-------------|
| `createBooking(array $data): array` | Creates a new booking with validation |
| `getBookingById(int $id): ?array` | Retrieves booking details by ID |
| `getUserBookings(int $userId, int $page = 1, int $perPage = 10, ?string $status = null): array` | Gets user's bookings with pagination and optional filtering |
| `cancelBooking(int $id, int $userId): array` | Cancels a booking and calculates any applicable refund |
| `rescheduleBooking(int $id, string $pickupDate, string $dropoffDate, int $userId): array` | Reschedules a booking to new dates |
| `validateBookingAccess(int $bookingId, int $userId): bool` | Verifies user has access to a booking |
| `getBookingLogs(int $bookingId): array` | Retrieves activity logs for a booking |
| `completeBooking(int $id): bool` | Marks a booking as completed |

**Dependencies:** DatabaseHelper, AuditService, LoggerInterface, ExceptionHandler, Booking model, Vehicle model, Payment model

#### TemplateService
**Purpose:** Provides functionality for managing and rendering document templates with placeholder replacement.

| Method | Description |
|--------|-------------|
| `listTemplates(): array` | Returns all available templates |
| `loadTemplate($templateId): DocumentTemplate` | Loads template content by ID or name |
| `renderTemplate($templateId, array $data): string` | Replaces placeholders with data and returns rendered content |
| `saveTemplate(string $templateName, string $content, ?int $templateId = null): DocumentTemplate` | Creates or updates a template |
| `deleteTemplate(int $templateId): bool` | Deletes a template by ID |

**Dependencies:** LoggerInterface, ExceptionHandler, AuditService, DocumentTemplate model

#### DocumentService
**Purpose:** Manages documents including templates, contracts, and T&C with encryption and secure storage.

| Method | Description |
|--------|-------------|
| `uploadTemplate(string $name, string $content): void` | Uploads an encrypted document template |
| `uploadTerms(string $content): void` | Uploads Terms & Conditions document |
| `generateContract(int $bookingId, int $userId): string` | Creates a contract document dynamically |
| `retrieveDocument(string $filePath): string` | Retrieves and decrypts a document |
| `deleteDocument(int $documentId): void` | Deletes a document securely |
| `getTemplates(): array` | Gets a list of all available templates |
| `getTemplateById(int $templateId): array` | Gets a specific template by ID |
| `getUserContracts(int $userId): array` | Gets all contracts for a specific user |
| `getBookingContract(int $bookingId): array` | Gets contract for a specific booking |
| `generateInvoice(int $bookingId): string` | Generates an invoice document |

**Dependencies:** LoggerInterface, ExceptionHandler, AuditService, FileStorage, EncryptionService, TemplateService, various models (Document, DocumentTemplate, Contract, User, Booking)

#### SignatureService
**Purpose:** Manages electronic signatures both locally and via an external AES API.

| Method | Description |
|--------|-------------|
| `uploadSignature(string $filePath, int $userId): string` | Uploads a local signature securely |
| `sendForAdvancedSignature(string $filePath, int $userId, string $callbackUrl): array` | Sends document for AES signature |
| `verifySignature(string $signedFilePath, string $originalFilePath): bool` | Verifies an AES signature |
| `getSignatures(int $userId): array` | Retrieves all stored signatures for a user |
| `getSignature(int $userId): ?string` | Gets a specific signature for a user |
| `checkAdvancedSignatureStatus(string $requestId): array` | Checks status of an AES signature request |
| `downloadSignedDocument(string $requestId, string $outputPath): bool` | Downloads a signed AES document |

**Dependencies:** LoggerInterface, Signature model, FileStorage, EncryptionService, ExceptionHandler

#### NotificationService
**Purpose:** Manages user notifications across multiple channels (email, SMS, in-app, push).

| Method | Description |
|--------|-------------|
| `sendNotification(int $userId, string $type, string $message, array $options = []): bool` | Sends notification via specified channel |
| `getUserNotifications(int $userId): array` | Gets all notifications for a user |
| `getUnreadNotifications(int $userId): array` | Gets only unread notifications for a user |
| `markAsRead(int $notificationId): bool` | Marks a notification as read |
| `deleteNotification(int $notificationId): bool` | Deletes a notification |
| `sendBookingConfirmationNotification(int $bookingId, int $userId): bool` | Sends booking confirmation |
| `sendPaymentConfirmation(int $userId, ?int $bookingId, float $amount): bool` | Sends payment confirmation |
| `sendRefundNotification(int $userId, int $bookingId, float $amount, string $reason): bool` | Sends refund notification |
| `verifyNotificationOwnership(int $notificationId, int $userId): ?array` | Verifies user owns notification |

**Dependencies:** LoggerInterface, NotificationManager, EmailService, SMSService, User model, Notification model, AuditService

#### ReportService
**Purpose:** Generates and manages various business reports with filterable criteria.

| Method | Description |
|--------|-------------|
| `generateReport(string $reportType, array $dateRange, string $format, array $filters = []): string` | Generates specified report |
| `generateUserReport(int $userId, string $reportType, array $dateRange, string $format): string` | Generates report for specific user |
| `getAvailableReportTypes(): array` | Returns all available report types |
| `scheduleReport(string $reportType, array $schedule, int $userId, array $filters = []): int` | Schedules periodic report generation |
| `cancelScheduledReport(int $scheduleId, int $userId): bool` | Cancels a scheduled report |
| `getReportHistory(int $userId): array` | Gets history of generated reports |

**Dependencies:** LoggerInterface, ExceptionHandler, AuditService, DatabaseHelper, PDFGenerator, ExcelGenerator, User model, Booking model, Payment model

#### SettingsService
**Purpose:** Manages application-wide and user-specific settings with validation rules.

| Method | Description |
|--------|-------------|
| `getAllSettings(): array` | Retrieves all system settings |
| `saveSettings(array $settings): bool` | Saves multiple settings at once |
| `getSetting(string $key, $default = null)` | Gets a specific setting with fallback default |
| `updateSetting(string $key, $value): bool` | Updates a single setting |
| `getUserSettings(int $userId): array` | Gets settings for a specific user |
| `updateUserSetting(int $userId, string $key, $value): bool` | Updates a user-specific setting |
| `saveTabSettings(string $tab, array $data): bool` | Saves settings for a specific section |
| `testEmailConnection(array $settings): bool|string` | Tests email server connection |

**Dependencies:** LoggerInterface, AuditService, ExceptionHandler, DatabaseHelper, Settings model, UserSettings model

#### RevenueService
**Purpose:** Analyzes revenue streams, forecasts, and financial performance metrics.

| Method | Description |
|--------|-------------|
| `getRevenueSummary(string $period = 'month'): array` | Gets revenue summary for specified period |
| `getRevenueByVehicleType(array $dateRange): array` | Analyzes revenue by vehicle category |
| `getRevenueByPaymentMethod(array $dateRange): array` | Breaks down revenue by payment method |
| `calculateGrowthRate(string $period = 'month'): float` | Calculates revenue growth rate |
| `getRefundMetrics(array $dateRange): array` | Gets statistics about refunds |
| `forecastRevenue(int $months = 3): array` | Forecasts future revenue |
| `exportRevenueReport(array $dateRange, string $format = 'csv'): string` | Exports revenue data |

**Dependencies:** LoggerInterface, DatabaseHelper, AuditService, ExceptionHandler, Booking model, Payment model, RefundLog model

### Auth Services

#### AuthService
**Purpose:** Handles authentication, registration, and token management for users.

| Method | Description |
|--------|-------------|
| `login(array $data): array` | Authenticates user and returns tokens |
| `register(array $data): array` | Creates new user account |
| `refresh(array $data): array` | Refreshes access token using refresh token |
| `logout(array $data): array` | Logs out a user and invalidates tokens |
| `updateProfile($userId, array $data): array` | Updates user profile information |
| `resetPasswordRequest(array $data): array` | Initiates password reset process |
| `resetPassword(array $data): array` | Completes password reset with new password |
| `validateRequest(?string $authHeader = null): ?object` | Validates token from request header |

**Dependencies:** DatabaseHelper, TokenService, ExceptionHandler, LoggerInterface, AuditService, Validator, User model

#### TokenService
**Purpose:** Manages JWT token generation, validation, and refresh token handling.

| Method | Description |
|--------|-------------|
| `generateToken($user): string` | Generates JWT access token |
| `generateRefreshToken($user): string` | Generates and stores refresh token |
| `verifyToken(string $token): array` | Verifies JWT token validity and returns payload |
| `decodeRefreshToken(string $refreshToken)` | Decodes and validates refresh token |
| `refreshToken(string $refreshToken): string` | Creates new access token using refresh token |
| `revokeToken(string $token): void` | Revokes a refresh token |
| `purgeExpiredTokens(): int` | Removes expired tokens from database |
| `getActiveTokensForUser(int $userId): array` | Gets all active tokens for a user |
| `validateRequest($request): ?array` | Validates token and returns user data |
| `extractToken($request): ?string` | Extracts token from various request formats |

**Dependencies:** LoggerInterface, ExceptionHandler, DatabaseHelper, AuditService, RefreshToken model, User model

### Payment Services

#### PaymentService
**Purpose:** Acts as a facade for payment operations, delegating to specialized payment services.

| Method | Description |
|--------|-------------|
| `processPayment(array $paymentData): array` | Processes payment and updates booking status |
| `refundPayment(array $refundData): array` | Handles payment refunds |
| `addPaymentMethod(array $methodData): array` | Adds a payment method for a user |
| `getUserPaymentMethods(int $userId): array` | Gets all payment methods for a user |
| `getTransactionDetails(int $transactionId, int $userId, bool $isAdmin = false): ?array` | Gets transaction details |
| `handlePaymentCallback(string $gatewayName, array $callbackData): array` | Processes payment gateway callbacks |
| `getTransactionHistory(int $userId): array` | Gets transaction history for a user |
| `getTransactionHistoryAdmin(array $filters): array` | Gets filtered transaction history for admins |
| `processPaymentGateway(string $gatewayName, array $paymentData): array` | Direct interaction with payment gateway |

**Dependencies:** Payment services (PaymentProcessingService, RefundService, PaymentGatewayService, TransactionService), models (Payment, PaymentMethod, TransactionLog, Booking), AuditService

#### PaymentProcessingService
**Purpose:** Handles payment initiation, validation, database transactions, and fraud detection.

| Method | Description |
|--------|-------------|
| `processPayment(array $paymentData): array` | Processes a payment with fraud validation |
| `performFraudValidation(array $paymentData): array` | Validates payment data for fraud indicators |

**Dependencies:** DatabaseHelper, models (Payment, Booking, TransactionLog), AuditService, LoggerInterface

#### RefundService
**Purpose:** Handles all refund-related operations including validation and processing.

| Method | Description |
|--------|-------------|
| `refund(array $refundData): array` | Processes a refund request |
| `isValidRefundData(array $refundData): bool` | Validates refund data completeness |
| `isRefundable(array $originalPayment, array $refundData): bool` | Checks if payment is eligible for refund |

**Dependencies:** DatabaseHelper, models (Payment, TransactionLog), AuditService, LoggerInterface

#### TransactionService
**Purpose:** Handles transaction consistency, logging, and history retrieval.

| Method | Description |
|--------|-------------|
| `logTransaction(array $transactionData): array` | Logs a transaction in the system |
| `getHistoryByUser(int $userId): array` | Gets transaction history for a user |
| `getHistoryAdmin(array $filters): array` | Gets filtered transaction history for admins |
| `getTransactionDetails(int $transactionId, int $userId, bool $isAdmin = false): ?array` | Gets transaction details |

**Dependencies:** TransactionLog model, Payment model, AuditService, LoggerInterface

#### PaymentGatewayService
**Purpose:** Interfaces with external payment providers (Stripe, PayPal, etc.).

| Method | Description |
|--------|-------------|
| `processGatewayPayment(string $gateway, array $paymentData): array` | Processes payment through specified gateway |
| `createPaymentIntent(string $gateway, array $paymentData): array` | Creates payment intent for client-side processing |
| `verifyPayment(string $gateway, array $verificationData): bool` | Verifies a payment was successful |
| `refundGatewayPayment(string $gateway, array $refundData): array` | Issues refund through gateway |
| `getGatewayConfig(string $gateway): array` | Retrieves configuration for specified gateway |
| `validateWebhook(string $gateway, array $data, string $signature): bool` | Validates incoming webhook signature |

**Dependencies:** LoggerInterface, TransactionLog model, Payment model, AuditService, gateway-specific libraries (Stripe, PayPal SDKs)

### Audit Services

#### AuditService
**Purpose:** Provides centralized event logging with security-sensitive events stored in audit database.

| Method | Description |
|--------|-------------|
| `logEvent(string $category, string $message, array $context = [], ?int $userId = null, ?int $bookingId = null, ?string $ipAddress = null, string $logLevel = self::LOG_LEVEL_INFO): ?int` | Main entry point for logging events |
| `recordEvent(string $category, string $action, string $message, array $context = [], ?int $userId = null, ?int $objectId = null, string $logLevel = self::LOG_LEVEL_INFO): ?int` | Legacy method for event recording |
| `recordPaymentSuccess(array $paymentData): ?int` | Records successful payment events |
| `getLogs(array $filters = []): array` | Gets filtered logs |
| `deleteLogs(array $filters, bool $forceBulkDelete = false): int` | Deletes logs matching filters |
| `exportLogs(array $filters): array` | Exports logs for reporting |
| `getLogById(int $logId): ?array` | Gets specific log entry |

**Dependencies:** LoggerInterface, ExceptionHandler, LogManagementService, UserAuditService, TransactionAuditService, LogLevelFilter, AuditLog model

#### TransactionAuditService
**Purpose:** Handles audit logging for financial transactions with fraud detection capabilities.

| Method | Description |
|--------|-------------|
| `logEvent(string $category, string $message, array $context, ?int $userId = null, ?int $bookingId = null, string $logLevel = 'info'): ?int` | Logs transaction events |
| `recordPaymentSuccess(array $paymentData): ?int` | Records successful payment transactions |
| `recordFraudValidationFailure(array $paymentData, array $fraudIndicators = []): ?int` | Records fraud validation failures |
| `recordTransaction(string $transactionType, array $transactionData, string $status, ?string $message = null): ?int` | Records transaction with status |
| `recordRefund(array $refundData, string $status): ?int` | Records refund events |

**Dependencies:** LogManagementService, FraudDetectionService, ExceptionHandler, LoggerInterface

#### LogManagementService
**Purpose:** Manages storage, retrieval, and maintenance of application logs and audit records.

| Method | Description |
|--------|-------------|
| `createLogEntry(string $category, string $message, array $context, ?int $userId, ?int $bookingId, ?string $ipAddress, string $logLevel): ?int` | Creates a new log entry |
| `getLogs(array $filters = []): array` | Retrieves logs with pagination and filtering |
| `exportLogs(array $filters): array` | Exports logs in specified format (CSV, JSON) |
| `getLogById(int $logId): ?array` | Gets a specific log entry by ID |
| `deleteLogs(array $filters, bool $forceBulkDelete = false): int` | Deletes logs matching criteria |
| `purgeOldLogs(int $days = 90, array $excludeCategories = []): int` | Purges logs older than specified days |
| `getLogger(): LoggerInterface` | Gets the logger instance |

**Dependencies:** LoggerInterface, ExceptionHandler, DatabaseHelper, AuditLog model

#### UserAuditService
**Purpose:** Specializes in user-related audit trail logging with enhanced privacy protection.

| Method | Description |
|--------|-------------|
| `logUserEvent(string $category, string $action, string $message, array $context, ?int $userId, string $logLevel = 'info'): ?int` | Logs user events |
| `logAuthEvent(string $action, string $message, array $context, ?int $userId, string $logLevel): ?int` | Logs authentication events |
| `recordProfileChange(int $userId, array $changes, string $source): ?int` | Records profile data changes |
| `recordPermissionChange(int $userId, array $permissionChanges, int $adminId): ?int` | Records permission changes |
| `getUserAuditLog(int $userId, array $filters = []): array` | Gets audit log for specific user |

**Dependencies:** LogManagementService, ExceptionHandler, LoggerInterface

### Security Services

#### EncryptionService
**Purpose:** Provides secure encryption and decryption for sensitive data using industry standards.

| Method | Description |
|--------|-------------|
| `encrypt(string $data): string` | Encrypts data using application key |
| `decrypt(string $encryptedData): string` | Decrypts previously encrypted data |
| `generateKey(int $length = 32): string` | Generates a secure random key |
| `hashPassword(string $password): string` | Securely hashes passwords |
| `verifyPassword(string $password, string $hash): bool` | Verifies password against hash |
| `encryptFile(string $inputPath, string $outputPath): bool` | Encrypts a file |
| `decryptFile(string $inputPath, string $outputPath): bool` | Decrypts a file |

**Dependencies:** LoggerInterface, KeyManager, ExceptionHandler

#### FraudDetectionService
**Purpose:** Detects and prevents fraudulent transactions using pattern analysis and risk scoring.

| Method | Description |
|--------|-------------|
| `analyzeTransaction(array $paymentData): array` | Analyzes transaction for fraud indicators |
| `validateIPAddress(string $ipAddress): array` | Validates IP address against known fraud sources |
| `validateUserBehavior(int $userId, array $currentAction): array` | Analyzes user behavior patterns |
| `getRiskScore(array $indicators): int` | Calculates overall risk score (0-100) |
| `getFraudRules(): array` | Gets all fraud detection rules |
| `updateFraudRule(int $ruleId, array $ruleData): bool` | Updates a fraud detection rule |
| `recordSuspiciousActivity(array $activityData): int` | Records suspicious activity |

**Dependencies:** LoggerInterface, AuditService, ExceptionHandler, DatabaseHelper, User model, IP geolocation service

#### KeyManager
**Purpose:** Manages cryptographic keys with secure storage, rotation, and access control.

| Method | Description |
|--------|-------------|
| `getKey(string $keyName): string` | Retrieves a key by name |
| `rotateKey(string $keyName): bool` | Rotates a key (generates new key while preserving old) |
| `generateKey(string $keyName, int $length = 32): string` | Generates and stores a new key |
| `deleteKey(string $keyName): bool` | Deletes a key |
| `backupKeys(): string` | Creates encrypted backup of all keys |
| `importKeys(string $backupData, string $password): bool` | Imports keys from backup |
| `validateKeyStrength(string $keyName): bool` | Validates key strength |

**Dependencies:** LoggerInterface, ExceptionHandler, secure storage mechanism (file/database), AuditService

#### RateLimiter
**Purpose:** Prevents abuse by limiting request frequency for APIs and sensitive operations.

| Method | Description |
|--------|-------------|
| `attempt(string $key, int $maxAttempts, int $decaySeconds = 60): bool` | Checks if attempt should be allowed |
| `hit(string $key, int $decaySeconds = 60): int` | Records an attempt and returns total attempts |
| `clear(string $key): bool` | Resets attempts for a key |
| `remaining(string $key, int $maxAttempts): int` | Gets remaining attempts |
| `availableIn(string $key): int` | Gets seconds until attempts reset |
| `tooManyAttempts(string $key, int $maxAttempts): bool` | Checks if max attempts exceeded |

**Dependencies:** CacheInterface or DatabaseHelper, LoggerInterface

### Utility Services

#### FileStorage
**Purpose:** Handles secure file operations with support for various storage backends (local, S3, etc.).

| Method | Description |
|--------|-------------|
| `storeFile(string $directory, string $filename, string $content, bool $public = false): string` | Stores a file and returns path |
| `retrieveFile(string $path, bool $public = false): string` | Retrieves file content |
| `deleteFile(string $path, bool $public = false): bool` | Deletes a file |
| `fileExists(string $path, bool $public = false): bool` | Checks if file exists |
| `getPublicUrl(string $path): string` | Gets public URL for a file |
| `moveFile(string $source, string $destination): bool` | Moves a file |
| `getStorageDriver(string $driver = null)` | Gets storage driver instance |

**Dependencies:** LoggerInterface, ExceptionHandler, storage drivers (local filesystem, S3, etc.)

#### MetricsService
**Purpose:** Collects, analyzes, and provides application performance and business metrics.

| Method | Description |
|--------|-------------|
| `recordMetric(string $name, $value, array $tags = []): bool` | Records a metric data point |
| `incrementCounter(string $name, int $value = 1, array $tags = []): int` | Increments a counter metric |
| `recordTiming(string $name, float $milliseconds, array $tags = []): bool` | Records timing information |
| `getMetric(string $name, array $filter = [], string $aggregation = 'avg'): array` | Gets metric data |
| `getSystemMetrics(): array` | Gets system performance metrics |
| `getBusinessMetrics(string $period = 'day'): array` | Gets business performance metrics |
| `exportMetrics(array $filter = []): array` | Exports metrics data |

**Dependencies:** LoggerInterface, DatabaseHelper, CacheInterface, various models

#### Validator
**Purpose:** Provides data validation with customizable rules and error messages.

| Method | Description |
|--------|-------------|
| `validate(array $data, array $rules): bool` | Validates data against rules |
| `errors(): array` | Returns validation errors |
| `failed(): bool` | Checks if validation failed |
| `passes(): bool` | Checks if validation passed |
| `addRule(string $ruleName, callable $validator): void` | Adds custom validation rule |
| `getRule(string $ruleName): ?callable` | Gets a validation rule |
| `setErrorMessages(array $messages): void` | Sets custom error messages |

**Dependencies:** None (standalone utility)

### Administrative Services

#### AdminService
**Purpose:** Provides administrative functionality for user management and system monitoring.

| Method | Description |
|--------|-------------|
| `validateAdmin(ServerRequestInterface $request): ?array` | Validates admin token and returns admin data |
| `getAllUsers(int $page, int $adminId): array` | Gets all users with pagination |
| `updateUserRole(int $userId, string $role, int $adminId): bool` | Updates a user's role |
| `deleteUser(int $userId, int $adminId): ?array` | Soft-deletes a user |
| `getDashboardData(int $adminId): array` | Gets dashboard statistics |
| `createAdmin(array $data, int $adminId): ?array` | Creates a new admin user |

**Dependencies:** Admin model, AuditService, LoggerInterface, TokenService, ExceptionHandler

#### AuditService
**Purpose:** Provides audit logging functionality for security, compliance, and operational monitoring across the application.

| Method | Description |
|--------|-------------|
| `logEvent(string $eventType, string $message, array $context, ?int $userId, ?int $resourceId, string $category = 'system'): void` | Records an audit event with detailed context |
| `getLogs(array $filters): array` | Retrieves audit logs with comprehensive filtering and pagination |
| `getLogById(int $id): ?array` | Retrieves a specific audit log entry by ID |
| `exportLogs(array $filters): array` | Exports filtered logs to CSV format |
| `deleteLogs(array $filters, bool $forceBulkDelete = false): bool` | Deletes logs based on specified criteria |

**Dependencies:** AuditLog model, LoggerInterface, ExceptionHandler

## Service Interactions

Services interact with each other through their public interfaces:

1. **Auth Flow**:
   - Controllers → AuthService → TokenService → User model
   - TokenService ← → AuditService (logs authentication events)

2. **Document Flow**:
   - DocumentService → TemplateService → FileStorage → EncryptionService
   - DocumentService → AuditService (logs document operations)

3. **Payment Flow**:
   - PaymentService → PaymentProcessingService → Payment models
   - PaymentService → RefundService → TransactionService
   - All payment operations → AuditService/TransactionAuditService (logs events)

4. **Booking Flow**:
   - BookingService → PaymentService → NotificationService
   - BookingService → DocumentService (for contracts)
   - BookingService → AuditService (logs booking events)

5. **Admin Operations**:
   - AdminService → TokenService (for validation)
   - AdminService → AuditService (logs admin activities)
   - AdminService → UserService (for user management)

6. **Security Layers**:
   - Most services → EncryptionService (for sensitive data)
   - API endpoints → RateLimiter (for abuse prevention)
   - PaymentService → FraudDetectionService (for transaction validation)

7. **Cross-cutting Concerns**:
   - All services → LoggerInterface (for application logging)
   - All services → ExceptionHandler (for standardized exception handling)
   - Most services → Validator (for input validation)

This service architecture ensures separation of concerns while maintaining cohesion within service domains.
