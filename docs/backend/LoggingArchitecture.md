# CarFuse Logging Architecture

This document provides a comprehensive overview of the logging system used throughout the CarFuse application.

## 1. Log Categories

The application uses specialized loggers for different components:

| Category       | Purpose                                           |
|----------------|---------------------------------------------------|
| `application`  | General application logs (default)                |
| `auth`         | Authentication and authorization events           |
| `user`         | User-related operations                           |
| `db`           | Database operations and queries                   |
| `api`          | API requests and responses                        |
| `security`     | Security events, warnings, and breaches           |
| `system`       | System-level events                               |
| `audit`        | Security audit trail                              |
| `dependencies` | Service dependency initialization                 |
| `payment`      | Payment processing operations                     |
| `booking`      | Booking-related operations                        |
| `file`         | File operations and storage                       |
| `admin`        | Administrative operations                         |
| `metrics`      | Performance metrics                               |
| `report`       | Report generation                                 |
| `revenue`      | Revenue tracking                                  |
| `notification` | Notification services                             |
| `document`     | Document management                               |
| `dashboard`    | Dashboard operations                              |
| `encryption`   | Encryption operations                             |
| `cache`        | Cache operations                                  |
| `session`      | Session management                                |
| `validation`   | Data validation                                   |

## 2. Obtaining and Using Loggers

### 2.1 Dependency Injection

Loggers are primarily obtained through constructor injection:

```php
class UserController extends Controller
{
    protected LoggerInterface $logger;
    
    public function __construct(
        LoggerInterface $logger,
        // other dependencies
    ) {
        parent::__construct($logger, $exceptionHandler);
        // additional initialization
    }
}
```

### 2.2 Specialized Logger Injection

Controllers can request specific category loggers:

```php
$container->set(PaymentController::class, function($c) use ($logger, $loggers) {
    return new PaymentController(
        $loggers['payment'] ?? $logger,
        // other dependencies
    );
});
```

### 2.3 Fallback Pattern

When requesting specialized loggers, always implement a fallback pattern:

```php
$logger = $c->get('logger.auth') ?? $c->get(LoggerInterface::class);
```

### 2.4 Global Access (Avoid when possible)

For legacy code or non-DI contexts:

```php
global $loggers;
$loggers['payment']->info('Processing payment');
```

## 3. Log Configuration

### 3.1 File Storage

Logs are stored in the `/logs` directory with separate files for each category:
- `/logs/application.log`
- `/logs/auth.log`
- `/logs/db.log`
- etc.

### 3.2 Log Rotation

Log files are automatically rotated using Monolog's `RotatingFileHandler`:
- 14-day retention period
- Files are named with date suffixes (e.g., `application-2023-07-08.log`)

### 3.3 Formatting

All logs use a consistent format:

```
[2023-07-08 15:42:13.123456] [auth] INFO: User login successful {"email":"user@example.com"} {"memory_usage":2097152}
```

The format includes:
- Timestamp with microsecond precision
- Logger channel (category)
- Log level
- Message
- Context array (JSON)
- Extra information (JSON)

## 4. Best Practices

### 4.1 Choosing the Right Log Level

- `DEBUG`: Development information (query parameters, detailed flow)
- `INFO`: Normal operations (user login, resource creation)
- `NOTICE`: Uncommon but expected events
- `WARNING`: Unexpected events that don't affect operations
- `ERROR`: Failures that prevent operations from completing
- `CRITICAL`: Major issues requiring immediate attention
- `ALERT`: Issues requiring action (security breach)
- `EMERGENCY`: System is unusable

### 4.2 Providing Context

Always include relevant context as a second parameter:

```php
// Good practice
$this->logger->info("User login successful", ['email' => $data['email']]);

// Avoid
$this->logger->info("User login successful for " . $data['email']);
```

### 4.3 Controller Logging

Controllers should:
- Log the beginning of important operations
- Include user IDs and request IDs when available
- Log successful completion of operations
- Use proper error handling with the ExceptionHandler

```php
public function processPayment(): ResponseInterface
{
    try {
        $this->logger->info("Processing payment request", ['user_id' => $userId]);
        
        // Process payment
        
        $this->logger->info("Payment processed successfully", [
            'user_id' => $userId,
            'amount' => $amount,
            'transaction_id' => $transactionId
        ]);
        
        return $response;
    } catch (\Exception $e) {
        $this->exceptionHandler->handleException($e);
        // The exception handler will log the error
    }
}
```

### 4.4 Sensitive Information

Never log sensitive information:
- Passwords (even hashed)
- API keys
- Personal identification information
- Credit card numbers
- Access tokens (use redacted versions)

### 4.5 Performance Considerations

- Use context arrays instead of string concatenation
- Log moderately in production (INFO level and above)
- Use DEBUG level sparingly in production
- Validate that log-heavy operations don't impact performance

## 5. Log Access and Monitoring

Log files are stored in the `/logs` directory with appropriate permissions:
- Files: 0664
- Directory: 0775

During development, logs are also output to stderr for immediate visibility.

For log analysis, use standard Linux tools or dedicated log monitoring solutions:

```bash
# View recent authentication errors
tail -f /logs/auth.log | grep ERROR

# Count errors by type
grep ERROR /logs/application.log | cut -d':' -f4 | sort | uniq -c | sort -nr
```

## 6. Error Handling Integration

The logging system integrates with the `ExceptionHandler` to ensure exceptions are:
1. Logged with appropriate context and stack traces
2. Categorized correctly based on the exception source
3. Processed at the appropriate log level

```php
try {
    // Operation that might fail
} catch (\Exception $e) {
    $this->exceptionHandler->handleException($e);
    // Additional recovery logic if needed
}
```
