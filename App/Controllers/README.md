# Controller Audit Logging Guide

## Overview
This guide explains how to properly log audit events directly from controllers using the `AuditService`. The `AuditTrailMiddleware` is now deprecated and controllers should handle their own audit logging.

## Basic Usage

```php
// Inject AuditService in your controller
private AuditService $auditService;

public function __construct(AuditService $auditService) 
{
    $this->auditService = $auditService;
}

// Example usage in a controller method
public function login(Request $request)
{
    // Process login
    $success = $this->authService->authenticate($request->email, $request->password);
    
    if ($success) {
        // Log successful login
        $this->auditService->logAuthEvent(
            $user->id,
            'login',
            ['device' => $request->userAgent()]
        );
        
        return $this->response->json(['success' => true]);
    } else {
        // Log failed login attempt
        $this->auditService->logAuthEvent(
            0,  // No user ID for failed login
            'failed_login',
            ['email' => $request->email]
        );
        
        return $this->response->json(['error' => 'Invalid credentials'], 401);
    }
}
```

## Common Audit Scenarios

### Authentication Events
```php
// Login
$this->auditService->logAuthEvent($userId, 'login', $context);

// Logout
$this->auditService->logAuthEvent($userId, 'logout', $context);

// Password reset
$this->auditService->logAuthEvent($userId, 'password_reset', $context);
```

### User Actions
```php
// Create user
$this->auditService->logUserAction(
    $adminId,
    'create',
    'user',
    $newUserId,
    ['user_data' => $userData]
);

// Update user
$this->auditService->logUserAction(
    $userId,
    'update',
    'user',
    $userId,
    [
        'before' => $oldData,
        'after' => $newData
    ]
);

// Delete user
$this->auditService->logUserAction($adminId, 'delete', 'user', $deletedUserId);
```

### Booking Events
```php
// Create booking
$this->auditService->logBookingEvent(
    $bookingId,
    'created',
    $userId,
    ['amount' => $amount, 'details' => $bookingDetails]
);

// Update booking
$this->auditService->logBookingEvent(
    $bookingId,
    'updated',
    $userId,
    ['changes' => $changes]
);

// Cancel booking
$this->auditService->logBookingEvent(
    $bookingId,
    'cancelled',
    $userId,
    ['reason' => $reason]
);
```

### API Requests
```php
// In API controllers, after processing
$this->auditService->logApiRequest(
    '/api/v1/resource',
    'POST',
    $request->all(),
    $responseData,
    $statusCode,
    $userId
);
```

### Security Events
```php
// Failed login attempts
$this->auditService->logSecurityEvent(
    'failed_login',
    'Multiple failed login attempts',
    ['attempts' => $attempts, 'email' => $email],
    null
);

// Permission denied
$this->auditService->logSecurityEvent(
    'permission_denied',
    'User attempted to access restricted resource',
    ['resource' => $resource],
    $userId
);
```

## Best Practices

1. Always log at the outcome of an operation, not before it happens
2. Include relevant context but avoid sensitive information
3. Use the appropriate helper method for the type of action
4. For custom events, use the generic `logEvent()` method
5. Keep log messages concise and action-oriented
