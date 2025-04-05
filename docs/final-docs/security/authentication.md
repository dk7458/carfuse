# Authentication Security

*Last updated: 2023-11-15*

This document details the authentication mechanisms available in the CarFuse framework and provides implementation guidelines for secure user authentication.

## Table of Contents
- [Authentication Architecture](#authentication-architecture)
- [Server-Side Authentication](#server-side-authentication)
- [Client-Side Authentication](#client-side-authentication)
- [Session Management](#session-management)
- [Authentication Events](#authentication-events)
- [Common Authentication Patterns](#common-authentication-patterns)

## Authentication Architecture

The CarFuse authentication system is built around a secure session-based mechanism with the following components:

1. **SecurityService** - Core authentication logic
2. **SecurityMiddleware** - Authentication enforcement in request pipeline
3. **User Sessions** - Secure PHP sessions for maintaining authentication state
4. **CarFuseSecurity** - Client-side authentication handler

## Server-Side Authentication

Use the `SecurityService` and `SecurityMiddleware` classes to handle authentication:

```php
// Check if a user is authenticated
if (SecurityService::isAuthenticated()) {
    // User is logged in
}

// Require authentication for a page
SecurityMiddleware::authOnly();

// Require admin role
SecurityMiddleware::adminOnly();

// Require staff role (admin or manager)
SecurityMiddleware::staffOnly();
```

### Authentication Verification

To verify a user's credentials:

```php
try {
    $authenticated = SecurityService::authenticate($email, $password);
    
    if ($authenticated) {
        // Authentication successful
        $userId = SecurityService::getCurrentUserId();
    } else {
        // Authentication failed
    }
} catch (SecurityException $e) {
    // Handle security exception
    SecurityService::logSecurityEvent('auth_error', $e->getMessage());
}
```

### Manual Authentication Management

```php
// Log in a user
SecurityService::login($userId);

// Log out a user
SecurityService::logout();

// Check if a user is authenticated
$isAuthenticated = SecurityService::isAuthenticated();
```

## Client-Side Authentication

Use the `CarFuseSecurity` object in JavaScript to handle authentication state on the client:

```javascript
// Check if user is authenticated
if (CarFuseSecurity.isAuthenticated()) {
    // User is logged in
    const userId = CarFuseSecurity.getUserId();
}

// Check if user has a specific role
if (CarFuseSecurity.hasRole('admin')) {
    // User is an admin
}

// Check if user has any of the specified roles
if (CarFuseSecurity.hasRole(['admin', 'manager'])) {
    // User is either an admin or manager
}
```

### Authentication UI Integration

For Alpine.js components:

```html
<div x-data="securityAwareComponent">
    <div x-show="isAuthenticated()">
        Welcome, <span x-text="getUserName()"></span>!
    </div>
    
    <div x-show="!isAuthenticated()">
        <a href="/login">Please login</a>
    </div>
</div>
```

## Session Management

The session is automatically initialized and managed by the `SecurityService` class.

### Session Security Features

- Sessions are stored server-side
- Session IDs are regenerated on privilege changes
- Session timeout is configurable
- CSRF tokens are tied to sessions

### Session Regeneration

To regenerate a session (e.g., after a privilege change):

```php
SecurityService::regenerateSession();
```

### Session Configuration

Default session settings can be modified in the security configuration:

```php
// In config/security.php
return [
    'session' => [
        'lifetime' => 7200, // 2 hours
        'secure' => true,   // Require HTTPS
        'httponly' => true, // Not accessible via JavaScript
        'samesite' => 'Lax' // CSRF protection
    ]
];
```

## Authentication Events

Authentication events are emitted during the authentication lifecycle:

### Server-Side Events

```php
// Register an authentication listener
SecurityEvents::on('user.login', function ($userId) {
    // Run code after successful login
});

// Available events:
// - user.login
// - user.logout
// - user.auth_failed
// - session.regenerated
```

### Client-Side Events

```javascript
// Listen for authentication events
document.addEventListener('auth:login-success', event => {
    console.log('User logged in:', event.detail.userId);
});

document.addEventListener('auth:logout', () => {
    console.log('User logged out');
});
```

## Common Authentication Patterns

### Login Form Implementation

```php
<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    try {
        $success = SecurityService::authenticate($email, $password);
        
        if ($success) {
            // Redirect to dashboard
            header('Location: /dashboard');
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    } catch (SecurityException $e) {
        $error = $e->getMessage();
        SecurityService::logSecurityEvent('auth_error', $error);
    }
}
?>

<!-- Login form -->
<form method="post" action="/login">
    <!-- CSRF token is automatically added by security.js -->
    
    <div>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
    </div>
    
    <div>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <button type="submit">Log In</button>
</form>
```

### Protecting Routes

The recommended approach for protecting routes is using middleware:

```php
// In routes.php
$router->get('/admin/dashboard', 'AdminController@dashboard')
    ->middleware('SecurityMiddleware::adminOnly');
    
$router->get('/account', 'AccountController@show')
    ->middleware('SecurityMiddleware::authOnly');
```

### Remember Me Functionality

For implementing "Remember Me" functionality:

```php
// When authenticating a user with "Remember Me"
if ($rememberMe) {
    SecurityService::createRememberMeToken($userId);
}

// The SecurityService will automatically check for remember-me cookies
// during initialization and authenticate the user if valid
```

## Related Documentation

- [Security Overview](overview.md)
- [CSRF Protection](csrf-protection.md)
- [Role-Based Access Control](../components/auth/rbac.md)
- [Login System Components](../components/auth/login-system.md)
