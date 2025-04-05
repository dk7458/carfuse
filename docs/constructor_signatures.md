# Helper Class Constructor Signatures

This document provides a reference for all the constructor signatures of the helper classes after implementing dependency injection.

## ApiHelper

```php
/**
 * Constructor with dependency injection
 * 
 * @param LoggerInterface $logger Logger instance
 * @param string|null $logFile Path to API log file (optional)
 */
public function __construct(LoggerInterface $logger, ?string $logFile = null)
```

## DatabaseHelper

```php
/**
 * Constructor with dependency injection
 *
 * @param array $config Database configuration
 * @param LoggerInterface $logger Logger instance
 * @param ApiHelper $apiHelper API helper for JSON responses
 */
public function __construct(array $config, LoggerInterface $logger, ApiHelper $apiHelper)
```

## ExceptionHandler

```php
/**
 * Constructor with dependency injection
 *
 * @param LoggerInterface $dbLogger Logger for database operations
 * @param LoggerInterface $authLogger Logger for authentication events
 * @param LoggerInterface $systemLogger Logger for general system events
 */
public function __construct(
    LoggerInterface $dbLogger,
    LoggerInterface $authLogger,
    LoggerInterface $systemLogger
)
```

## LoggingHelper

```php
/**
 * Constructor with dependency injection
 * 
 * @param LoggerInterface $defaultLogger The default logger instance
 * @param array $categoryLoggers Optional array of category-specific loggers
 */
public function __construct(LoggerInterface $defaultLogger, array $categoryLoggers = [])
```

## LogLevelFilter

```php
/**
 * Constructor
 *
 * @param string $minLevel Minimum log level to process, defaults to 'debug' (process all)
 */
public function __construct(string $minLevel = 'debug')
```

## LogQueryBuilder

```php
/**
 * Constructor with dependency injection
 * 
 * @param SecurityHelper $securityHelper Security helper for input sanitization
 */
public function __construct(SecurityHelper $securityHelper)
```

## SecurityHelper

```php
/**
 * Constructor with dependency injection
 *
 * @param LoggerInterface $logger Logger instance
 * @param string $logFile Path to security log file
 */
public function __construct(LoggerInterface $logger, string $logFile = null)
```

## SetupHelper

```php
/**
 * Constructor
 * 
 * @param DatabaseHelper $dbHelper Database helper instance
 * @param LoggerInterface $logger PSR-3 Logger instance
 */
public function __construct(DatabaseHelper $dbHelper, LoggerInterface $logger)
```
