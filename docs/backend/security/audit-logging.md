# Audit Logging

## Overview
The CarFuse platform implements a comprehensive audit logging system to track user activities, system events, and data modifications. This documentation covers how the audit system works and how developers can properly use it.

## Audit Log Architecture

The audit logging system consists of:

- **AuditService**: Main service that routes events to appropriate handlers based on category
- **Specialized Services**: Handlers for specific event types (UserAuditService, TransactionAuditService)
- **Storage Models**: AuditLog and AuditTrail models for database interaction

### Audit Categories

| Category | Description | Database Storage |
|----------|-------------|------------------|
| `system` | System-level events | General logs only |
| `auth` | Authentication events | Audit database |
| `transaction` | Payment transactions | Audit database |
| `booking` | Booking operations | Audit database |
| `user` | User profile changes | Audit database |
| `admin` | Administrative actions | Audit database |
| `document` | Document operations | Audit database |
| `api` | API requests | General logs only |
| `security` | Security-related events | Audit database |
| `payment` | Payment processing | Audit database |

### Log Levels

| Level | Description | Use Case |
|-------|-------------|----------|
| `debug` | Detailed debugging information | Development troubleshooting |
| `info` | Normal events | Regular user activities |
| `warning` | Unexpected but non-critical issues | Validation failures, retry attempts |
| `error` | Runtime errors | Failed operations, exceptions |
| `critical` | Critical failures | Security breaches, data corruption |

## Audited Events

### Authentication Events
- User login attempts (success/failure)
- Password changes
- Multi-factor authentication events
- Session management

### User Events
- Profile creation
- Profile updates
- Role changes
- Account deactivation/reactivation

### Transaction Events
- Payment processing
- Refunds
- Booking financial transactions

### Administrative Events
- Settings changes
- User management actions
- System configuration updates
- Report generation

### Security Events
- Access control violations
- Suspicious activities
- IP address changes

## Audit Log Structure

### Common Log Fields

| Field | Description |
|-------|-------------|
| `id` | Unique identifier |
| `category` | Event category (see categories table) |
| `action` | Specific event type |
| `message` | Human-readable description |
| `user_id` | Associated user (if applicable) |
| `booking_id` | Associated booking (if applicable) |
| `ip_address` | Source IP address |
| `details` | JSON-encoded context information |
| `log_level` | Severity level |
| `created_at` | Timestamp |

### Details Field Structure

The `details` JSON field typically contains:

```json
{
  "request_id": "uuid-for-request-tracking",
  "before": { "field": "original-value" },
  "after": { "field": "new-value" },
  "additional_context": "event-specific-data"
}
```

## Log Storage and Retention

### Storage
- High-priority events: Stored in the `audit_logs` database table
- System events: Stored in application log files
- Security events: Duplicated in both database and secure log files

### Retention Policy
- Production audit logs: Retained for 90 days by default
- Financial transaction logs: Retained for 7 years (legal requirement)
- Security incident logs: Retained for 1 year
- System logs: Rotated weekly with 4-week retention

### Export and Archiving
Logs can be exported to CSV format for long-term archiving using the AuditController's export functionality.

## Adding Audit Logging to New Code

### Basic Usage

```php
// Inject AuditService into your class
private AuditService $auditService;

public function __construct(AuditService $auditService) {
    $this->auditService = $auditService;
}

// Log an event
$this->auditService->logEvent(
    'user_updated',           // action
    'User profile updated',   // message
    [                         // context
        'user_id' => 123,
        'fields_updated' => ['name', 'email']
    ],
    123,                      // userId
    null,                     // bookingId (if applicable)
    '192.168.1.1',            // ipAddress (if available)
    AuditService::LOG_LEVEL_INFO // log level
);
```

### Selecting the Right Category

Choose the appropriate category from the predefined constants:

```php
// For user-related activities
$this->auditService->logEvent(
    'profile_updated',
    'User updated their profile',
    ['fields' => ['name', 'avatar']],
    $userId,
    null,
    $ipAddress,
    AuditService::LOG_LEVEL_INFO
);

// For security events
$this->auditService->logEvent(
    'password_changed',
    'User changed their password',
    ['user_agent' => $userAgent],
    $userId,
    null,
    $ipAddress,
    AuditService::LOG_LEVEL_INFO
);
```

### Controller Integration Examples

Here's how audit logging is typically implemented in controllers:

```php
public function updateProfile(Request $request, Response $response): Response
{
    // Process the update...
    $userData = $this->userModel->updateProfile($userId, $data);
    
    // Log the update
    $this->auditService->logEvent(
        'profile_updated',
        'User updated their profile',
        [
            'user_id' => $userId,
            'fields_updated' => array_keys($data)
        ],
        $userId,
        null,
        $request->getServerParams()['REMOTE_ADDR'] ?? null,
        AuditService::LOG_LEVEL_INFO
    );
    
    return $response;
}
```

## Accessing and Analyzing Logs

### Admin Interface
Administrators can access logs through the Admin Dashboard under Audit Logs.

### Filtering Capabilities
Logs can be filtered by:
- Date range
- User ID
- Event category
- Action type
- Log level

### Programmatic Access
```php
// Via the AuditController
$logs = $auditService->getLogs([
    'category' => 'security',
    'user_id' => 123,
    'start_date' => '2023-01-01',
    'end_date' => '2023-01-31',
    'page' => 1,
    'per_page' => 50
]);
```

## Best Practices

1. **Be consistent with log levels** - Use appropriate severity levels
2. **Include meaningful context** - Add relevant details that help with troubleshooting
3. **Don't log sensitive data** - Never include passwords, tokens, or PII in log details
4. **Structure the context** - Use consistent field names in the context array
5. **Log both before and after states** - For data modifications, include previous and new values

## Troubleshooting

If audit logs aren't being recorded as expected:

1. Check the log level filter settings
2. Verify the event category is in the AUDIT_CATEGORIES list
3. Ensure the database connection is working properly
4. Check for exceptions in the application error logs
