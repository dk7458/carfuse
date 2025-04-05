# CarFuse Security Implementation Guide

This document provides guidelines on how to implement security features consistently across the CarFuse application.

## Authentication

### Server-side Authentication

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

### Client-side Authentication

Use the `CarFuseSecurity` object in JavaScript:

```javascript
// Check if user is authenticated
if (CarFuseSecurity.isAuthenticated()) {
    // User is logged in
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

## CSRF Protection

### Server-side CSRF Protection

The `SecurityMiddleware` automatically applies CSRF protection to all non-GET requests.

To manually validate a CSRF token:

```php
SecurityService::validateCsrf();
```

### Client-side CSRF Protection

CSRF tokens are automatically added to all forms when the page loads via the `security.js` script.

For fetch requests:

```javascript
// Use the security-enhanced fetch method
CarFuseSecurity.fetch('/api/endpoint', {
    method: 'POST',
    body: JSON.stringify(data)
})
.then(response => response.json())
.then(data => {
    // Handle response
});
```

For manually adding a CSRF token to a form:

```javascript
const form = document.getElementById('my-form');
CarFuseSecurity.addCsrfToForm(form);
```

## Role-Based Access Control

### Server-side Role Checks

```php
// Check for a specific role
if (SecurityService::hasRole('admin')) {
    // User is an admin
}

// Check for any of multiple roles
if (SecurityService::hasRole(['admin', 'manager'])) {
    // User is either an admin or manager
}

// Enforce a specific role
SecurityService::requireRole('admin');

// Enforce any of multiple roles
SecurityService::requireRole(['admin', 'manager']);
```

### Role Constants

Use the predefined constants for roles:

```php
use App\Services\SecurityService;

// Use the role constants
SecurityService::hasRole(SecurityService::ROLE_ADMIN);
SecurityService::hasRole(SecurityService::ROLE_MANAGER);
SecurityService::hasRole(SecurityService::ROLE_USER);
```

## Session Management

The session is automatically initialized and managed by the `SecurityService` class. 

To regenerate a session (e.g., after a privilege change):

```php
SecurityService::regenerateSession();
```

## Security Events Logging

Log security-related events:

```php
SecurityService::logSecurityEvent('event_type', 'Event details', $userId);
```

## Client-side Toast Notifications

To show a toast notification:

```javascript
window.dispatchEvent(new CustomEvent('show-toast', {
    detail: {
        title: 'Success',
        message: 'Operation completed successfully',
        type: 'success', // success, error, warning, info
        duration: 3000 // optional, defaults to 3000ms
    }
}));
```

## Alpine.js Security Integration

Use the `securityAwareComponent` for components that need security features:

```html
<div x-data="securityAwareComponent">
    <button x-show="hasRole('admin')" @click="doAdminThing()">Admin Action</button>
    
    <div x-show="!isAuthenticated()">
        <a href="/login">Please login</a>
    </div>
</div>
```

## Admin Page Template

For creating new admin pages, use the template:

```php
<?php
// Set page title and description
$pageTitle = 'My Admin Page';
$pageDescription = 'Description of my admin page';

// Set required role (optional, defaults to admin)
$requiredRole = SecurityService::ROLE_ADMIN;

// Include the admin page template
include BASE_PATH . '/app/templates/admin-page-template.php';
?>

<!-- Your page content here -->
<div class="bg-white shadow-md rounded-lg p-6">
    <h2>My Content</h2>
    <!-- ... -->
</div>

<?php
// Close the HTML structure
include BASE_PATH . '/app/templates/admin-page-footer.php';
?>
```
