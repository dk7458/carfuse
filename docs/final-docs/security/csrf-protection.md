# CSRF Protection

*Last updated: 2023-11-15*

This document explains how Cross-Site Request Forgery (CSRF) protection is implemented in the CarFuse framework and provides guidance on using these protections correctly.

## Table of Contents
- [Understanding CSRF](#understanding-csrf)
- [CSRF Protection Architecture](#csrf-protection-architecture)
- [Server-Side CSRF Protection](#server-side-csrf-protection)
- [Client-Side CSRF Protection](#client-side-csrf-protection)
- [Testing CSRF Protection](#testing-csrf-protection)
- [Common Pitfalls](#common-pitfalls)

## Understanding CSRF

Cross-Site Request Forgery (CSRF) is an attack that forces an end user to execute unwanted actions on a web application in which they're currently authenticated. CSRF attacks specifically target state-changing requests, not theft of data, since the attacker has no way to see the response to the forged request.

CarFuse's CSRF protection works by:

1. Generating a unique token tied to the user's session
2. Including this token in all forms and AJAX requests
3. Validating the token on the server for all non-GET requests

## CSRF Protection Architecture

The CSRF protection system consists of these components:

1. **Server-side token generation** - Creating secure random tokens
2. **Server-side validation** - Verifying tokens on form submission
3. **Client-side integration** - Automatically adding tokens to forms and AJAX requests

## Server-Side CSRF Protection

The `SecurityMiddleware` automatically applies CSRF protection to all non-GET requests.

### Manual CSRF Validation

To manually validate a CSRF token:

```php
// Validate CSRF token
try {
    SecurityService::validateCsrf();
    
    // Token is valid, process the request
    // ...
} catch (CsrfException $e) {
    // Invalid token, handle the error
    http_response_code(403);
    echo "CSRF validation failed";
    exit;
}
```

### Excluding Routes from CSRF Protection

Some routes may need to be excluded from CSRF protection (e.g., webhook endpoints):

```php
// In config/security.php
return [
    'csrf' => [
        'enabled' => true,
        'exclude' => [
            '/api/webhooks/*',
            '/api/external-service-callback'
        ]
    ]
];
```

### Generating CSRF Tokens

To generate a CSRF token for custom use:

```php
$token = SecurityService::generateCsrfToken();
```

## Client-Side CSRF Protection

CSRF tokens are automatically added to all forms when the page loads via the `security.js` script.

### For Forms

Forms are automatically protected. The `security.js` script adds a hidden input with the CSRF token to every form:

```html
<form method="post" action="/submit">
    <!-- The CSRF token is automatically added here by security.js -->
    
    <!-- Form fields -->
    <input type="text" name="name">
    
    <button type="submit">Submit</button>
</form>
```

### For AJAX Requests

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

### For jQuery Ajax (if used)

```javascript
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': CarFuseSecurity.getCsrfToken()
    }
});

// Then make Ajax requests as usual
$.ajax({
    url: '/api/endpoint',
    method: 'POST',
    data: { key: 'value' }
});
```

### Manually Adding CSRF Token to a Form

```javascript
const form = document.getElementById('my-form');
CarFuseSecurity.addCsrfToForm(form);
```

## Testing CSRF Protection

### Testing Server-Side Validation

To test if CSRF protection is working properly:

1. Create a form without a CSRF token
2. Submit the form
3. Expect a 403 Forbidden response

### Testing Client-Side Integration

To test if client-side CSRF integration is working:

1. Inspect a form after page load
2. Verify that a hidden input with name `csrf_token` exists
3. Verify that AJAX requests include the `X-CSRF-TOKEN` header

## Common Pitfalls

### SPA Frameworks

When using SPA frameworks (React, Vue, etc.), you may need to:

1. Get the CSRF token from a meta tag or API endpoint
2. Set up AJAX interceptors to add the token to all requests

```javascript
// Example for axios
axios.interceptors.request.use(config => {
    config.headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
    return config;
});
```

### Form Generators

If using JavaScript to dynamically create forms, remember to add CSRF protection:

```javascript
function createDynamicForm() {
    const form = document.createElement('form');
    form.method = 'post';
    form.action = '/submit';
    
    // Add form fields
    // ...
    
    // Add CSRF protection
    CarFuseSecurity.addCsrfToForm(form);
    
    return form;
}
```

### CSRF and API Tokens

For API endpoints that use token-based authentication (not session-based), CSRF protection is typically not required, as these tokens cannot be used by a CSRF attack due to the same-origin policy.

## Related Documentation

- [Security Overview](overview.md)
- [Authentication Mechanisms](authentication.md)
- [Security Best Practices](best-practices.md)
