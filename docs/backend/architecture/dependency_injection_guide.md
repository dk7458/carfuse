# Dependency Injection System Guide

## Overview

CarFuse uses PHP-DI as its dependency injection container to manage application components. The DI system handles:

- Service instantiation and lifecycle management
- Dependency resolution and injection
- Configuration management
- Environment-specific settings

## Initialization Sequence

The DI system initializes in this order:

1. **Logger Initialization** (`logger.php`)
   - Creates category-specific loggers

2. **Bootstrap Process** (`bootstrap.php`)
   - Loads environment variables
   - Loads configuration files
   - Initializes core services (ExceptionHandler, AuditService, etc.)
   - Creates DI container
   - Registers pre-initialized services

3. **Dependencies Configuration** (`dependencies.php`)
   - Registers helpers and utilities
   - Registers middleware components
   - Loads service configurations
   - Loads controller configurations

4. **Service Registration** (`svc_dep.php`)
   - Registers models
   - Registers services with dependencies
   - Configures service relationships

5. **Controller Registration** (`ctrl_dep.php`)
   - Registers controllers with their service dependencies

## Container Configuration

### Core Container Setup

```php
// Create container
$container = new \DI\Container();
$GLOBALS['container'] = $container;

// Register core services
$container->set(LoggerInterface::class, $logger);
$container->set(ExceptionHandler::class, $exceptionHandler);
$container->set(AuditService::class, $auditService);
```

## Registering Components

### 1. Registering a Service

Services are registered in `svc_dep.php`:

```php
$container->set(UserService::class, function($c) {
    return new UserService(
        $c->get('logger.auth') ?? $c->get(LoggerInterface::class),
        $c->get(DatabaseHelper::class),
        $c->get(ExceptionHandler::class),
        $c->get(AuditService::class),
        $c->get(User::class),
        $config['encryption']['jwtSecret'] ?? ''
    );
});
```

**Example: Adding a New Service**

1. Create your service class:
```php
namespace App\Services;

class EmailService {
    private $logger;
    private $config;
    
    public function __construct(LoggerInterface $logger, array $config) {
        $this->logger = $logger;
        $this->config = $config;
    }
    
    public function sendEmail($to, $subject, $body) {
        // Implementation
    }
}
```

2. Register in `svc_dep.php`:
```php
$container->set(EmailService::class, function($c) use ($config) {
    return new EmailService(
        $c->get('logger.notification') ?? $c->get(LoggerInterface::class),
        $config['email'] ?? []
    );
});
```

### 2. Registering a Controller

Controllers are registered in `ctrl_dep.php`:

```php
$container->set(UserController::class, function($c) use ($logger, $loggers) {
    return new UserController(
        $loggers['user'] ?? $loggers['api'] ?? $logger,
        $c->get(Validator::class),
        $c->get(TokenService::class),
        $c->get(ExceptionHandler::class),
        $c->get(AuthService::class),
        $c->get(AuditService::class),
        $c->get(User::class)
    );
});
```

**Example: Adding a New Controller**

1. Create your controller class:
```php
namespace App\Controllers;

class EmailController {
    private $logger;
    private $emailService;
    private $exceptionHandler;
    
    public function __construct(
        LoggerInterface $logger,
        EmailService $emailService,
        ExceptionHandler $exceptionHandler
    ) {
        $this->logger = $logger;
        $this->emailService = $emailService;
        $this->exceptionHandler = $exceptionHandler;
    }
    
    public function sendNewsletter($request, $response) {
        // Implementation
    }
}
```

2. Register in `ctrl_dep.php`:
```php
$container->set(EmailController::class, function($c) use ($logger, $loggers) {
    return new EmailController(
        $loggers['notification'] ?? $logger,
        $c->get(EmailService::class),
        $c->get(ExceptionHandler::class)
    );
});
```

3. Add route in `routes.php`:
```php
$emailController = $container->get(EmailController::class);
$router->addRoute(['POST'], '/api/email/newsletter', [$emailController, 'sendNewsletter']);
```

### 3. Registering Middleware

Middleware components are registered in `dependencies.php`:

```php
$container->set(AuthMiddleware::class, function($c) {
    return new AuthMiddleware(
        $c->get(TokenService::class),
        $c->get('logger.auth') ?? $c->get(LoggerInterface::class),
        $c->get(DatabaseHelper::class),
        false // Default not required
    );
});
```

**Example: Adding New Middleware**

1. Create your middleware class:
```php
namespace App\Middleware;

class CacheControlMiddleware {
    private $logger;
    
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }
    
    public function __invoke($request, $handler) {
        $response = $handler->handle($request);
        return $response->withHeader('Cache-Control', 'max-age=3600');
    }
}
```

2. Register in `dependencies.php`:
```php
$container->set(CacheControlMiddleware::class, function($c) {
    return new CacheControlMiddleware(
        $c->get('logger.system') ?? $c->get(LoggerInterface::class)
    );
});
```

## Accessing Services

### 1. Constructor Injection (Preferred)

```php
class BookingController {
    private BookingService $bookingService;
    private LoggerInterface $logger;
    
    public function __construct(
        LoggerInterface $logger, 
        BookingService $bookingService
    ) {
        $this->logger = $logger;
        $this->bookingService = $bookingService;
    }
}
```

### 2. Container Access (When Necessary)

```php
global $container;
$bookingService = $container->get(BookingService::class);
```

## Best Practices

1. **Use Constructor Injection**: Always prefer constructor injection over service location

2. **Use Category-Specific Loggers**: Utilize domain loggers with fallbacks
   ```php
   $logger = $c->get('logger.user') ?? $c->get(LoggerInterface::class);
   ```

3. **Provide Default Configurations**:
   ```php
   $serviceConfig = $config['service_name'] ?? [];
   ```

4. **Use Type Hinting**: Enables IDE auto-completion and better error detection

5. **Keep Services Focused**: Follow single responsibility principle

6. **Explicitly Declare Dependencies**: Don't rely on nested dependency resolution
