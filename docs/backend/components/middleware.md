# Middleware

## Overview
Middleware components in Carfuse handle cross-cutting concerns like authentication, session management, and CSRF protection. They process requests before they reach route handlers and can modify both requests and responses.

## Middleware Components

### SessionMiddleware

- **Purpose**: Manages session initialization, security, and persistence. Configures secure session parameters and adds session data to request attributes.
- **Execution Priority**: Should be executed very early in the middleware stack (typically first or second) as other middleware components depend on session data.
- **Configuration Options**:
  - `name`: Session name (default: 'carfuse_session')
  - `lifetime`: Session lifetime in seconds (default: 7200 - 2 hours)
  - `path`: Cookie path (default: '/')
  - `domain`: Cookie domain (default: '')
  - `secure`: Cookie secure flag (default: true)
  - `httponly`: Cookie httpOnly flag (default: true)
  - `samesite`: Cookie SameSite policy (default: 'Lax')
  - `regenerate_interval`: Session regeneration interval (default: 1800 - 30 minutes)
- **Interactions**:
  - Provides session data for UserDataMiddleware
  - Provides CSRF token storage for CsrfMiddleware
  - Exposes helper methods `getSession()` and `setSession()` for other components

### AuthMiddleware

- **Purpose**: Authenticates users via JWT tokens from either Authorization headers or cookies. Fetches and attaches user data to request.
- **Execution Priority**: After SessionMiddleware but before protected route handlers.
- **Configuration Options**:
  - `required`: Boolean flag to specify if authentication is mandatory (returns 401 if true and auth fails)
- **Interactions**:
  - Works with TokenService to verify JWT tokens
  - Accesses database to fetch user data
  - Attaches user data to request for RequireAuthMiddleware

### UserDataMiddleware

- **Purpose**: Loads user data from session into request attributes. Ensures consistent user data access pattern.
- **Execution Priority**: After SessionMiddleware but before route handlers that need user data.
- **Configuration Options**:
  - `required`: Boolean flag indicating if user data is required (returns 401 if missing when true)
- **Interactions**:
  - Uses session data stored by SessionMiddleware
  - Provides helper methods `setUserData()` and `clearUserData()`
  - Works alongside AuthMiddleware as an alternative authentication method

### TokenValidationMiddleware

- **Purpose**: Validates authentication tokens for API requests, offering a stateless authentication option.
- **Execution Priority**: Early in API route middleware stack.
- **Configuration Options**: No specific configuration parameters.
- **Interactions**:
  - Works with TokenService for token validation
  - Works with AuthService for authentication logic
  - Returns 401 if token validation fails

### RequireAuthMiddleware

- **Purpose**: Ensures a user is authenticated before allowing access to protected routes.
- **Execution Priority**: After authentication middleware (AuthMiddleware or TokenValidationMiddleware).
- **Configuration Options**: No specific configuration parameters.
- **Interactions**:
  - Depends on user attribute being set by auth middleware
  - Returns 401 if user attribute is not present

### CsrfMiddleware

- **Purpose**: Prevents CSRF attacks by generating and validating tokens for state-changing operations.
- **Execution Priority**: After SessionMiddleware but before form-handling route handlers.
- **Configuration Options**:
  - `excludedPaths`: Array of path prefixes to exclude from CSRF validation
- **Interactions**:
  - Uses session from SessionMiddleware to store tokens
  - Provides special handling for HTMX requests

### EncryptionMiddleware

- **Purpose**: Encrypts sensitive data in requests and responses for endpoints handling confidential information.
- **Execution Priority**: Typically after parsing middleware but before business logic.
- **Configuration Options**:
  - Uses configuration files (`sensitive_fields.json`, `sensitive_endpoints.json`) to determine what to encrypt
- **Interactions**:
  - Works with EncryptionService to perform encryption/decryption
  - Can be selectively applied to specific routes

### HtmxMiddleware

- **Purpose**: Handles HTMX-specific request processing and response formatting for enhanced frontend interactions.
- **Execution Priority**: After core middleware but before route handlers.
- **Configuration Options**: No specific configuration parameters.
- **Interactions**:
  - Adds HTMX-specific attributes to request
  - Sets appropriate headers for HTMX responses
  - May interact with session for CSRF tokens

## Implementation and Usage

### Middleware Registration

Middleware can be registered globally or per-route:

```php
// Global middleware (applied to all routes)
$app->add(new SessionMiddleware($logger, $config));

// Route-specific middleware
$app->get('/protected', function ($request, $response) {
    // Handler code
})->add(new RequireAuthMiddleware($logger));

// Group middleware
$app->group('/admin', function (RouteCollectorProxy $group) {
    // Admin routes
})->add(new RequireAuthMiddleware($logger));
```

### Recommended Middleware Order

For most applications, the following order is recommended:

1. ErrorMiddleware (built-in Slim)
2. SessionMiddleware
3. CsrfMiddleware
4. AuthMiddleware / TokenValidationMiddleware
5. UserDataMiddleware
6. RequireAuthMiddleware (on protected routes)
7. HtmxMiddleware
8. EncryptionMiddleware (on sensitive endpoints)

### Helper Methods

Some middleware expose static helper methods that can be used in route handlers:

```php
// Managing session
$session = SessionMiddleware::getSession($request);
$request = SessionMiddleware::setSession($request, $updatedSession);

// Managing user data
$request = UserDataMiddleware::setUserData($request, $userData);
$request = UserDataMiddleware::clearUserData($request);
