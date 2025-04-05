# CarFuse Routing System Documentation

This document provides a comprehensive overview of the routing systems used in the CarFuse application, comparing the modern FastRoute implementation with the legacy routing approach.

## 1. Route Definition and Organization

### Modern Routing (FastRoute)

The modern routing system uses FastRoute, a fast, simple router for PHP. Routes are defined in `config/routes.php` where:

- Routes are organized by functionality using route grouping
- HTTP methods are explicitly defined for each route
- Route parameters use pattern matching (e.g., `{id:\d+}`)
- Routes are mapped directly to controller methods

Example:
```php
// Route with pattern matching for ID parameter
$router->addRoute(['GET'], '/api/bookings/{id:\d+}', [$bookingController, 'viewBooking']);

// Route group for related endpoints
$router->addGroup('/api/documents', function (RouteCollector $r) use ($documentController) {
    $r->addRoute(['POST'], '/templates', [$documentController, 'uploadTemplate']);
    $r->addRoute(['GET'], '/templates', [$documentController, 'getTemplates']);
    // Additional routes...
});
```

### Legacy Routing

The legacy system in `App/api.php` uses hardcoded arrays to map URL paths to PHP files:

- Routes are defined in arrays (`$publicApiRoutes` and `$protectedApiRoutes`)
- No explicit HTTP method constraints
- Routes point directly to PHP files rather than controller methods
- Simple string matching for paths with no parameter pattern validation

Example:
```php
$publicApiRoutes = [
    'auth/login' => '/../public/api/auth/login.php',
    'auth/register' => '/../public/api/auth/register.php',
    // Additional routes...
];
```

## 2. Controller Mapping

### Modern Routing (FastRoute)

- Controllers are proper classes following object-oriented design
- Controller instances are retrieved from a dependency injection container
- Routes are mapped to specific controller methods
- Parameters can be type-hinted and automatically injected

Example:
```php
// Get controller instance from container
$bookingController = $container->get(BookingController::class);

// Map route to controller method
$router->addRoute(['GET'], '/api/bookings/{id:\d+}', [$bookingController, 'viewBooking']);
```

### Legacy Routing

- No proper controller classes
- Routes map directly to PHP files that handle the entire request
- Logic is not clearly separated from routing
- Parameters must be manually extracted from the request

Example:
```php
// Route maps to a PHP file
$publicApiRoutes = [
    'auth/login' => '/../public/api/auth/login.php',
];

// File is included directly
if (file_exists($apiFile)) {
    require_once $apiFile;
}
```

## 3. Middleware Application

### Modern Routing (FastRoute)

While not explicitly shown in the provided code, modern frameworks typically implement middleware through:

- Middleware classes that can be attached to routes or groups
- Pre and post-request processing
- Standardized request/response objects

The current implementation likely uses middleware for:
- Authentication (JWT verification)
- Rate limiting
- CORS handling
- Input validation

### Legacy Routing

Middleware concepts exist but are implemented directly within the routing script:

- JWT validation is built directly into the router
- CORS headers are applied globally
- Authentication checks are hardcoded based on route arrays
- No standardized middleware interface or chain

Example:
```php
// Authentication middleware directly in router
if (isset($protectedApiRoutes[$apiPath])) {
    validateToken(); // Acts as middleware
    $apiFile = __DIR__ . $protectedApiRoutes[$apiPath];
}

// CORS middleware directly in router
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
```

## 4. Migration Path

To migrate from the legacy routing system to the modern FastRoute implementation:

### Step 1: Identify All Current Routes

1. Document all routes in the legacy system (`$publicApiRoutes` and `$protectedApiRoutes`)
2. Map the functionality of each PHP file to potential controller methods

### Step 2: Create Controller Classes

1. For each group of related routes, create a controller class
2. Move the logic from individual PHP files into controller methods
3. Implement dependency injection for database connections and services

Example:
```php
// Before: individual file handling login
// /public/api/auth/login.php

// After: AuthController class with login method
class AuthController {
    public function login() {
        // Login logic moved here
    }
}
```

### Step 3: Define Routes Using FastRoute

1. Add new routes to the FastRoute configuration
2. Ensure route patterns match the legacy URL structure for backward compatibility
3. Map routes to the new controller methods

### Step 4: Implement Middleware

1. Extract authentication logic into dedicated middleware classes
2. Create middleware for rate limiting, CORS, etc.
3. Apply middleware to routes or groups as needed

### Step 5: Update Client Code

1. Ensure all client-side API calls work with the new routing system
2. Test thoroughly to ensure no functionality is broken
3. Consider versioning the API to support both systems during migration

### Step 6: Deprecate Legacy Router

1. Add logging to track which legacy routes are still being used
2. Set a timeline for full migration
3. Eventually remove the legacy routing system

## Conclusion

The FastRoute implementation provides a more maintainable, structured, and scalable approach to routing compared to the legacy system. The object-oriented design with proper controller classes and method mapping makes the codebase easier to understand and extend. The migration process should be gradual to ensure minimal disruption to existing functionality.

While the legacy system served its purpose, the modern routing approach aligns better with current PHP development practices and provides a foundation for future enhancements to the CarFuse application.
