# CarFuse Dependency Injection System

This document provides a comprehensive overview of the dependency injection (DI) system used in the CarFuse application. The DI system is built using PHP-DI and follows a structured initialization and service registration approach.

## 1. Container Initialization Process

The dependency injection container is initialized during the application bootstrap process through a sequence of carefully orchestrated steps:

### 1.1. Bootstrap Sequence

1. **Logger Initialization**: 
   ```php
   $logger = require_once __DIR__ . '/logger.php';
   global $loggers;
   ```
   The system first initializes the logging system, making both the main logger and category-specific loggers available globally.

2. **Environment Loading**:
   ```php
   $dotenv = Dotenv::createImmutable($dotenvPath);
   $dotenv->load();
   ```
   Environment variables are loaded from the `.env` file using the Dotenv library.

3. **Configuration Loading**:
   ```php
   $config = [];
   $configDir = __DIR__ . '/config';
   $requiredConfigs = ['database', 'encryption', 'app', 'filestorage', 'keymanager', 'documents'];
   // Load required configs first, then additional configs...
   ```
   Configuration files are loaded dynamically, with required configurations checked first.

4. **Core Service Initialization**:
   ```php
   $exceptionHandler = new ExceptionHandler($loggers['db'], $loggers['auth'], $logger);
   // Additional core services initialized...
   ```
   Critical services like ExceptionHandler, LogLevelFilter, and AuditService are pre-initialized in a specific order to resolve dependency chains.

5. **Container Creation**:
   ```php
   $container = new \DI\Container();
   $GLOBALS['container'] = $container; // Make container available globally
   ```
   The DI container is created and made available globally.

### 1.2. Container Configuration

In `dependencies.php`, the container is either reused from the bootstrap process or created with production optimizations:

```php
if (isset($container) && $container instanceof Container) {
    $logger->info("Using pre-configured container from bootstrap");
} else {
    $containerBuilder = new ContainerBuilder();
    
    // Enable container compilation for production
    if (isset($config['environment']) && $config['environment'] === 'production') {
        $cacheDir = __DIR__ . '/../var/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $containerBuilder->enableCompilation($cacheDir);
        $containerBuilder->writeProxiesToFile(true, $cacheDir . '/proxies');
    }
    
    $container = $containerBuilder->build();
}
```

For production environments, the container is configured with compilation enabled to optimize performance:
- Compiled container definitions are stored in a cache directory
- Proxy classes are written to files for faster instantiation
- This significantly improves service resolution speed

## 2. Service Registration and Autowiring

The CarFuse DI system uses three primary methods for registering and autowiring services:

### 2.1. Pre-initialized Service Registration

Core services are initialized during bootstrap and then registered directly:

```php
// Register pre-initialized services in bootstrap.php
$container->set(App\Helpers\ExceptionHandler::class, $coreServices['exceptionHandler']);
$container->set(App\Services\AuditService::class, $coreServices['auditService']);
$container->set(DatabaseHelper::class, $database);
```

### 2.2. Factory Function Registration

Most services are registered using factory functions that define how to create the service and its dependencies:

```php
// From dependencies.php
$container->set(DatabaseHelper::class, function(Container $c) use ($config) {
    $logger = $c->get('logger.db') ?? $c->get(LoggerInterface::class);
    $apiHelper = $c->get(ApiHelper::class);
    return new DatabaseHelper($config['database'] ?? [], $logger, $apiHelper);
});
```

### 2.3. Service Dependency Resolution

Dependencies are resolved by requesting them from the container within the factory function:

```php
// From svc_dep.php
$container->set(AuthService::class, function($c) use ($config) {
    return new AuthService(
        $c->get(DatabaseHelper::class),
        $c->get(TokenService::class),
        $c->get(ExceptionHandler::class),
        $c->get('logger.auth'),
        $c->get(AuditService::class),
        $config['encryption'] ?? [],
        $c->get(Validator::class),
        $c->get(User::class)
    );
});
```

### 2.4. Fallback Mechanism for Loggers

The system implements a fallback mechanism for logger services, ensuring that even if a category-specific logger isn't available, a default logger is used:

```php
$logger = $c->get('logger.auth') ?? $c->get(LoggerInterface::class);
```

### 2.5. Service Organization

Services are organized into logical groups across multiple files:

- **dependencies.php**: Registers helpers and middleware
- **svc_dep.php**: Registers business services and models
- **ctrl_dep.php**: Registers controllers with their dependencies

## 3. Service Lifetime Management

The CarFuse DI system manages service lifetimes in several ways:

### 3.1. Default Singleton Behavior

By default, the PHP-DI container treats services as singletons, creating one instance per request:

```php
// Once resolved, subsequent calls to $container->get(DatabaseHelper::class) 
// will return the same instance
$container->set(DatabaseHelper::class, function(Container $c) use ($config) {
    // ...implementation...
});
```

### 3.2. Explicit Caching in Production

For production environments, container compilation enhances performance by generating optimized code:

```php
if ($config['environment'] === 'production') {
    $containerBuilder->enableCompilation($cacheDir);
    $containerBuilder->writeProxiesToFile(true, $cacheDir . '/proxies');
}
```

### 3.3. Pre-initialized Services

Some critical services are pre-initialized during bootstrap and shared throughout the application lifetime:

```php
// These services are created once during bootstrap
$exceptionHandler = new ExceptionHandler(/*...*/);
$auditService = new AuditService(/*...*/);

// And registered in the container
$container->set(App\Helpers\ExceptionHandler::class, $exceptionHandler);
$container->set(App\Services\AuditService::class, $auditService);
```

### 3.4. Service Verification

The system verifies that critical services are properly loaded:

```php
$requiredServices = [
    DatabaseHelper::class,
    ExceptionHandler::class,
    SecurityHelper::class,
    AuditService::class,
    // ...
];

foreach ($requiredServices as $service) {
    try {
        $container->get($service);
        // Service loaded successfully
    } catch (\Exception $e) {
        // Service failed to load
        $failedServices[] = $errorMsg;
    }
}
```

## 4. Examples of Defining Injectable Services

### 4.1. Simple Service with Basic Dependencies

```php
$container->set(Validator::class, function($c) {
    return new Validator(
        $c->get('logger.api') ?? $c->get(LoggerInterface::class),
        $c->get(DatabaseHelper::class),
        $c->get(ExceptionHandler::class)
    );
});
```

### 4.2. Service with Configuration Dependencies

```php
$container->set(EncryptionService::class, function($c) use ($config) {
    return new EncryptionService(
        $c->get('logger.security') ?? $c->get(LoggerInterface::class),
        $c->get(ExceptionHandler::class),
        $config['encryption']['key']
    );
});
```

### 4.3. Controller with Multiple Service Dependencies

```php
$container->set(BookingController::class, function($c) use ($logger, $loggers) {
    return new BookingController(
        $loggers['booking'] ?? $logger,
        $c->get(BookingService::class),
        $c->get(PaymentService::class),
        $c->get(Validator::class),
        $c->get(AuditService::class),
        $c->get(NotificationService::class),
        $c->get(ResponseFactoryInterface::class),
        $c->get(TokenService::class),
        $c->get(ExceptionHandler::class)
    );
});
```

### 4.4. Service with Conditional Registration

```php
// Only register if not already registered
if (!$container->has(AuditService::class)) {
    $container->set(AuditService::class, function($c) use ($loggers, $logger, $config) {
        // Implementation...
    });
}
```

### 4.5. Model with Database Dependencies

```php
$container->set(User::class, function($c) {
    return new User(
        $c->get(DatabaseHelper::class),
        $c->get('logger.api') ?? $c->get('logger.db') ?? $c->get(LoggerInterface::class),
        $c->get(AuditService::class)
    );
});
```

## 5. Best Practices for Adding New Services

When adding new services to the CarFuse application, follow these best practices:

1. **Determine the correct file for registration**:
   - `svc_dep.php` for business logic services and models
   - `ctrl_dep.php` for controllers
   - `dependencies.php` for helpers and middleware

2. **Use consistent registration patterns**:
   ```php
   $container->set(YourNewService::class, function($c) use ($config, $logger, $loggers) {
       return new YourNewService(
           $loggers['appropriate_category'] ?? $logger,
           $c->get(RequiredDependency::class),
           // Additional dependencies...
           $config['relevant_config'] ?? []
       );
   });
   ```

3. **Follow the logger fallback pattern**:
   ```php
   $logger = $c->get('logger.your_category') ?? $c->get(LoggerInterface::class);
   ```

4. **Check for existing instances before registration** (for services that might be pre-initialized):
   ```php
   if (!$container->has(YourService::class)) {
       $container->set(YourService::class, function($c) {
           // Implementation...
       });
   }
   ```

5. **Register service implementations rather than interfaces** unless you need to swap implementations:
   ```php
   $container->set(ConcreteImplementation::class, function($c) {
       // Implementation...
   });
   ```

## 6. Debugging Dependency Issues

When experiencing dependency resolution issues:

1. Check the application logs for container initialization errors
2. Verify that all required services are registered
3. Look for circular dependencies in service definitions
4. Ensure the service constructor matches the dependencies being provided
5. Check for typos in service class names or namespaces

The system automatically logs service initialization failures:

```php
foreach ($requiredServices as $service) {
    try {
        $container->get($service);
        $container->get('logger.dependencies')->debug("Service loaded successfully: {$service}");
    } catch (\Exception $e) {
        $errorMsg = "Service failed to load: {$service}: " . $e->getMessage();
        $container->get('logger.dependencies')->critical($errorMsg);
        $failedServices[] = $errorMsg;
    }
}
```

## Conclusion

The CarFuse dependency injection system provides a robust foundation for managing service dependencies throughout the application. By leveraging PHP-DI with a structured initialization and registration process, the system ensures that services are properly wired together, while allowing for optimization in production environments.

The combination of pre-initialized critical services, factory-based registration, and proper service lifetime management creates a flexible and maintainable dependency injection system that can easily accommodate new services as the application grows.
