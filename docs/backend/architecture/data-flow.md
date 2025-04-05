# Data Flow

## Overview
The CarFuse system uses a structured data flow pattern to manage information from initial request to final response. This document outlines how data moves through the system's layers, including validation, transformation, and storage processes.

## Request-Response Flow Diagram

```
┌──────────┐     ┌───────────────┐     ┌───────────────┐     ┌─────────────────┐
│  Client  │────▶│  Middleware   │────▶│  Controller   │────▶│  Service Layer  │
└──────────┘     └───────────────┘     └───────────────┘     └─────────────────┘
      ▲                 │                     │                      │
      │                 │                     │                      │
      │                 │                     │                      ▼
      │                 │                     │             ┌─────────────────┐
      │                 │                     │             │  Model Layer    │
      │                 │                     │             └─────────────────┘
      │                 │                     │                      │
      │                 │                     │                      │
      │                 │                     │                      ▼
      │                 │                     │             ┌─────────────────┐
      │                 │                     │             │  Database       │
      │                 │                     │             └─────────────────┘
      │                 │                     │                      │
      │                 ▼                     ▼                      │
┌──────────┐     ┌────────────────────────────────────────────────────┐
│  Client  │◀────│               Response Processing                  │
└──────────┘     └────────────────────────────────────────────────────┘
```

## Input Processing

### 1. Request Capture
- Client requests arrive via HTTP/HTTPS to the application entry point
- Request data includes:
  - URL path parameters
  - Query string parameters
  - HTTP headers (including authentication tokens)
  - Request body (form data or JSON)
  - Cookies

### 2. Middleware Processing
Request data passes through a middleware stack that:
- Validates CSRF tokens for form submissions via CsrfMiddleware
- Extracts and validates authentication tokens via AuthMiddleware
- Loads session data via SessionMiddleware
- Logs request details for audit and monitoring
- Handles HTMX-specific headers and attributes via HtmxMiddleware
- Decrypts sensitive request content via EncryptionMiddleware

### 3. Controller Input Processing
Controllers process input by:
- Extracting relevant data from the request
- Validating input format through the Validator service
- Normalizing data (ex: standardizing date formats)
- Checking permissions via service layer authorization methods
- Logging important operations through AuditService

## Data Transformations

### 1. Business Logic Transformation
Services apply domain-specific transformations:
- Converting raw input to domain models
- Applying business rules and calculations
- Implementing workflow logic via state transitions
- Handling currency conversion and price calculations
- Processing dates, times, and durations

### 2. Model-Level Transformations
The model layer handles data transformations through the BaseModel class hierarchy:

- **Type Casting**: Automatic casting between PHP and database types
  ```php
  protected $casts = [
      'user_id' => 'int',
      'vehicle_id' => 'int',
      'pickup_date' => 'datetime',
      'dropoff_date' => 'datetime'
  ];
  ```

- **Entity Relationship Handling**: Models provide methods to fetch related entities
  ```php
  public function getUser(int|string $bookingId): ?array
  {
      $query = "SELECT u.* FROM users u
                JOIN {$this->table} b ON u.id = b.user_id
                WHERE b.id = :booking_id";
      // ...query execution...
  }
  ```

- **Validation Implementation**: Models define validation rules
  ```php
  public static $rules = [
      'user_id' => 'required|exists:users,id',
      'vehicle_id' => 'required|exists:vehicles,id',
      'pickup_date' => 'required|date',
      'dropoff_date' => 'required|date|after_or_equal:pickup_date',
      'status' => 'required|string|in:pending,confirmed,cancelled,completed',
  ];
  ```

- **Computed Properties**: Derived values calculated from stored data
- **Default Value Application**: Setting defaults for new records

### 3. Security Transformations
Security-focused transformations:
- Sensitive data encryption/decryption via EncryptionService
- Password hashing/verification
- Input sanitization to prevent injection attacks
- Data masking for personally identifiable information (PII)

### 4. Database Interaction
Database operations manage:
- Query construction using DatabaseHelper
- Transaction management for multi-table operations
- Data normalization for database storage
- Database connection pooling and optimization

## Output Generation

### 1. Data Aggregation
- Services aggregate data from multiple sources
- Models are combined to create comprehensive view objects
- Related entities are fetched and linked in optimized queries
- Statistics and summaries are calculated from raw data

### 2. Response Formatting
Controllers format data for client consumption according to endpoint requirements:

- **API responses** are structured JSON following consistent patterns:
  ```php
  protected function success($message, $data = [], $status = 200): ResponseInterface
  {
      return $this->jsonResponse([
          'status' => 'success',
          'message' => $message,
          'data' => $data
      ], $status);
  }
  ```

- **Web responses** include appropriate templates with context data:
  ```php
  include BASE_PATH . '/public/views/partials/user-profile.php';
  ```

- **HTMX responses** return HTML fragments for DOM insertion:
  ```php
  echo '<div class="user-card">' . htmlspecialchars($user['name']) . '</div>';
  ```

- **File downloads** set appropriate headers and stream content:
  ```php
  header('Content-Type: application/pdf');
  header('Content-Disposition: attachment; filename="faktura-' . $invoice['invoice_number'] . '.pdf"');
  ```

### 3. Response Enhancement
- Security headers added to responses
- CSRF tokens included for form submissions
- Cache control headers set appropriately
- CORS headers applied for cross-origin requests

### 4. Error Handling
- Exceptions translated to appropriate HTTP status codes
- Structured error responses with meaningful messages
- Sensitive error details hidden in production environment
- Error logging for monitoring and debugging

## Caching Strategy

### 1. Cache Layers
- Response caching for frequent, unchanged content
- Database query result caching
- Object caching for expensive computations
- Session data caching

### 2. Cache Invalidation
- Time-based expiration for semi-static content
- Event-based invalidation when related data changes
- Manual purging capabilities for administrative tasks

## Cross-Cutting Data Concerns

### 1. Audit Trail
All data modifications are captured in the audit trail through the AuditService:

```php
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
```

The audit system captures:
- User ID of person making the change
- Timestamp of the modification
- Type of operation performed
- Previous and new values
- Related entity information
- IP address and request context

### 2. Data Validation
Multiple validation layers ensure data integrity:
- Field-level validation (type, format, range)
- Entity-level validation (relationship integrity)
- Cross-field validation (logical dependencies)
- Business rule validation (domain-specific rules)

### 3. User Activity Tracking
The system tracks user interactions:
- Page views and API calls
- Search queries
- Feature usage
- Session duration and activity patterns

## Service Communication

### 1. Direct Method Invocation

Services communicate primarily through direct method calls:

```php
// PaymentController invoking PaymentService and NotificationService
$result = $this->paymentService->processPayment($paymentData);
$this->notificationService->sendPaymentConfirmation($user->id, $paymentData['booking_id'], $paymentData['amount']);
```

### 2. Event-Based Communication

Some operations trigger events that are handled by observers:

```php
// Audit logging triggered by events across the system
$this->auditService->logEvent('payment_processed', 'User payment processed', $eventData);
```

### 3. Transaction Boundaries

Database transactions ensure data integrity across multiple operations:

```php
$this->dbHelper->beginTransaction();
try {
    // Multiple database operations...
    $this->dbHelper->commit();
} catch (Exception $e) {
    $this->dbHelper->rollback();
    throw $e;
}
```
