# System Architecture Overview

This document provides a comprehensive overview of how CarFuse's core architectural components interact, focusing on the request routing system, dependency injection container, and logging infrastructure.

## Core Components

### 1. FastRoute Router (routes.php)
The routing system uses [FastRoute](https://github.com/nikic/FastRoute) to map HTTP requests to the appropriate controller actions.

### 2. Dependency Injection Container
The DI container is initialized and configured across several files:
- **bootstrap.php**: Application initialization and core service setup
- **dependencies.php**: Registration of helpers and middleware components
- **svc_dep.php**: Service class registration and configuration
- **ctrl_dep.php**: Controller registration with required dependencies

### 3. Logger System (logger.php)
A centralized logging system using Monolog that provides category-specific loggers for different parts of the application.

## Request Flow Diagram

```
┌─────────────┐      ┌─────────────┐      ┌─────────────────────┐
│ HTTP        │      │ index.php   │      │ bootstrap.php       │
│ Request     ├─────►│ Entry Point ├─────►│ Load Config & Init  │
└─────────────┘      └─────────────┘      │ Core Services       │
                                          └──────────┬──────────┘
                                                     │
                                                     ▼
┌─────────────┐      ┌─────────────┐      ┌─────────────────────┐
│ HTTP        │      │ Controller  │      │ FastRoute (routes.php)
│ Response    │◄─────┤ Action      │◄─────┤ Determine Handler   │
└─────────────┘      └─────────────┘      └──────────┬──────────┘
      ▲                     ▲                        │
      │                     │                        │
      │                     │                        ▼
┌─────┴───────┐    ┌───────┴───────┐     ┌─────────────────────┐
│ Middleware  │    │ Services      │     │ DI Container        │
│ Processing  │    │ Business Logic│◄────┤ Resolve Dependencies│
└─────────────┘    └───────────────┘     └──────────┬──────────┘
      ▲                     ▲                       │
      │                     │                       │
      │                     │                       ▼
┌─────┴───────┐    ┌───────┴───────┐     ┌─────────────────────┐
│ Logger      │    │ Models        │     │ dependencies.php    │
│ (logger.php)│◄───┤ Data Access   │◄────┤ svc_dep.php        │
└─────────────┘    └───────────────┘     │ ctrl_dep.php       │
                                          └─────────────────────┘
```

## Initialization Sequence

1. **Application Bootstrap**:
   - `bootstrap.php` is loaded first
   - Environment variables are configured
   - Configuration files are loaded
   - Logger system is initialized from `logger.php`
   - Core services are set up in correct dependency order
   - Database connections are verified

2. **DI Container Configuration**:
   - `dependencies.php` registers helpers, middleware components
   - `svc_dep.php` registers service classes with their dependencies
   - `ctrl_dep.php` registers controllers with their service dependencies
   - Required dependencies are validated

3. **Request Routing**:
   - FastRoute processes the incoming URL path
   - The router (configured in `routes.php`) determines which controller and method should handle it
   - The DI container resolves the controller and all its dependencies

## Component Interactions

### Router and DI Container Interaction

The router (`routes.php`) uses the DI container to resolve controller instances:

```php
return simpleDispatcher(function (RouteCollector $router) use ($container) {
    // Get controller instances from the container
    $authController = $container->get(AuthController::class);
    $userController = $container->get(UserController::class);
    
    // Define routes with resolved controllers
    $router->addRoute(['POST'], '/api/auth/login', [$authController, 'login']);
    $router->addRoute(['GET'], '/api/users/profile', [$userController, 'getUserProfile']);
    // ...
});
```

When a route is matched, the pre-resolved controller instance (with all its dependencies injected) handles the request.

### DI Container and Logger Integration

The logger system is integrated with the DI container in `dependencies.php`:

```php
// Register loggers in the container
$container->set(LoggerInterface::class, function() use ($logger) {
    return $logger;
});

// Register category-specific loggers
foreach ($loggers as $category => $categoryLogger) {
    $container->set("logger.{$category}", function() use ($categoryLogger) {
        return $categoryLogger;
    });
}
```

This makes loggers available as dependencies for any service or controller:

```php
$container->set(UserController::class, function($c) use ($logger, $loggers) {
    return new UserController(
        $loggers['user'] ?? $loggers['api'] ?? $logger,
        // other dependencies...
    );
});
```

### Service Dependency Resolution

The DI container resolves nested dependencies automatically. For example, when a controller requires multiple services:

```php
$container->set(BookingController::class, function($c) {
    return new BookingController(
        $c->get('logger.booking'),
        $c->get(BookingService::class),    // BookingService and its dependencies are resolved
        $c->get(PaymentService::class),    // PaymentService and its dependencies are resolved
        $c->get(Validator::class),
        $c->get(AuditService::class),
        // other dependencies...
    );
});
```

## Logger System Details

The logging system (`logger.php`) provides specialized loggers for different application areas:

1. **Initialization**:
   ```php
   $logDir = __DIR__ . '/logs';
   $formatter = new LineFormatter($output, $dateFormat, true, true);
   ```

2. **Category-Specific Loggers**:
   ```php
   $logCategories = ['application', 'auth', 'user', 'db', 'api', /* ... */];
   
   foreach ($logCategories as $category) {
       $logFile = "{$logDir}/{$category}.log";
       $loggers[$category] = createLogger($category, $logFile);
   }
   ```

3. **Global Access**:
   ```php
   $GLOBALS['logger'] = $logger;
   $GLOBALS['loggers'] = $loggers;
   ```

Each component can use the appropriate logger for its domain, ensuring that logs are properly categorized and searchable.

## Bootstrap Process

The bootstrap process (`bootstrap.php`) initializes the application in a specific sequence:

1. Load the logger configuration first
2. Initialize environment variables from `.env`
3. Load configuration files dynamically
4. Initialize exception handler
5. Configure database connections
6. Initialize core services in correct dependency order
7. Set up the DI container
8. Verify database connection
9. Validate encryption key
10. Check required dependencies
11. Run initial setup tasks

This careful orchestration ensures that dependencies are available when needed and services are initialized in the correct order.

## Relationship Between Configuration Files

```
bootstrap.php
   │
   ├── logger.php (Initialize logging)
   │
   ├── Load .env variables
   │
   ├── Load config/* files
   │
   ├── Initialize core services
   │    │
   │    └── AuditService, LogManagementService, etc.
   │
   ├── Initialize DI container
   │    │
   │    ├── dependencies.php (Register helpers, middleware)
   │    │    │
   │    │    ├── svc_dep.php (Register services)
   │    │    │
   │    │    └── ctrl_dep.php (Register controllers)
   │    │
   │    └── routes.php (Configure routing)
   │
   └── Validate critical components
```

## Middleware Pipeline

Requests pass through a middleware pipeline before reaching the controller:

1. **Security Headers**: Adds security-related HTTP headers
2. **Session Middleware**: Handles session creation/validation
3. **Auth Middleware**: Extracts and validates authentication tokens
4. **CSRF Protection**: Validates CSRF tokens for state-changing requests
5. **Rate Limiting**: Prevents abuse of sensitive endpoints
6. **Encryption**: Handles encryption/decryption of sensitive data
7. **Logging**: Records request information for audit and debugging

Each middleware component is registered in the DI container and can be assigned to specific routes or applied globally.

## Error Handling

The ExceptionHandler provides centralized error handling:

```php
$exceptionHandler = new ExceptionHandler(
    $loggers['db'],
    $loggers['auth'],
    $logger
);
```

Controllers and services use this handler to ensure consistent error responses and logging:

```php
try {
    // Operation that might fail
    $result = $this->someService->riskyOperation();
} catch (Exception $e) {
    $this->exceptionHandler->handleException($e);
    return $this->error('Operation failed', [], 500);
}
```

## Conclusion

The CarFuse architecture employs a well-structured approach to handling HTTP requests through the integration of FastRoute for routing, a robust dependency injection container for service management, and a comprehensive logging system. This architecture ensures clean separation of concerns, maintainability, and improved testability while providing detailed insights into system operation through categorized logging.
