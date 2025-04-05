# Helpers

## Overview
Helper classes provide commonly used functionality that simplifies code across the application. These utilities handle cross-cutting concerns like database operations, API responses, error handling, and environment configuration.

## Available Helpers

| Helper | Purpose |
|--------|---------|
| ApiHelper | Standardizes API responses and JWT handling |
| DatabaseHelper | Provides safe database operations with comprehensive logging |
| ExceptionHandler | Centralizes exception handling with consistent logging and responses |
| LogQueryBuilder | Generates SQL queries for audit log operations |
| SetupHelper | Sets up and verifies application environment configuration |

## Helper Classes

### ApiHelper

Primary purpose: Standardize API responses and JWT extraction.

#### Key Methods

```php
// Send standardized JSON response
public function sendJsonResponse(
    $status,               // 'success' or 'error'
    $message,              // Human-readable message
    $data = [],            // Data payload
    $httpCode = 200        // HTTP status code
): void

// Extract JWT token from Authorization header or cookie
public function getJWT(): ?string

// Log API-related events
public function logApiEvent($message): void
```

#### Usage Example

```php
$apiHelper = new ApiHelper($logger);

// Log API event
$apiHelper->logApiEvent("User {$userId} requested resource");

// Send success response
$apiHelper->sendJsonResponse('success', 'User created successfully', ['user_id' => $userId], 201);

// Send error response
$apiHelper->sendJsonResponse('error', 'Invalid parameters', ['errors' => $errors], 400);

// Extract JWT
$token = $apiHelper->getJWT();
```

### DatabaseHelper

Primary purpose: Provide safe database operations with error handling and logging.

#### Key Methods

```php
// Get PDO instance
public function getPdo(): PDO

// Execute a safe database query with logging
public function safeQuery(
    callable $query,               // Function containing the query to execute
    string $queryDescription = '', // Description for logging
    array $context = []            // Additional context information
): mixed

// Insert data into a table
public function insert(
    string $table,                // Table name
    array $data,                  // Data to insert (column => value)
    array $context = []           // Additional logging context
): string                         // Returns last insert ID

// Update data in a table
public function update(
    string $table,                // Table name
    array $data,                  // Data to update (column => value)
    array $where,                 // Where conditions (column => value)
    array $context = []           // Additional logging context
): int                            // Returns number of affected rows

// Delete data from a table
public function delete(
    string $table,                // Table name 
    array $where,                 // Where conditions (column => value)
    bool $softDelete = false,     // Whether to perform a soft delete
    array $context = []           // Additional logging context
): int                            // Returns number of affected rows

// Select data from the database
public function select(
    string $query,                // SQL query
    array $params = [],           // Query parameters
    array $context = []           // Additional logging context
): array                          // Returns query results

// Execute a raw query
public function rawQuery(
    string $query,                // SQL query
    array $params = [],           // Query parameters 
    array $context = []           // Additional logging context
): mixed                          // Returns query results or affected rows
```

#### Usage Example

```php
$dbHelper = new DatabaseHelper($config, $logger, $apiHelper);

// Insert
$userId = $dbHelper->insert('users', [
    'email' => 'user@example.com',
    'name' => 'John Doe',
    'created_at' => date('Y-m-d H:i:s')
]);

// Update
$affected = $dbHelper->update('users', 
    ['name' => 'Jane Doe'],   // data to update
    ['id' => $userId]         // where condition
);

// Select with prepared statement
$users = $dbHelper->select(
    "SELECT * FROM users WHERE email LIKE ?", 
    ['%example.com']
);

// Delete
$deleted = $dbHelper->delete('temp_logs', ['created_at' => '<= DATE_SUB(NOW(), INTERVAL 30 DAY)']);

// Soft delete (if table has deleted_at column)
$softDeleted = $dbHelper->delete('users', ['id' => $userId], true);
```

### ExceptionHandler

Primary purpose: Handle exceptions centrally with consistent logging and standardized responses.

#### Key Methods

```php
// Handle exceptions with appropriate logging and responses
public function handleException(Exception $e): void
```

#### Usage Example

```php
$exceptionHandler = new ExceptionHandler($dbLogger, $authLogger, $systemLogger);

try {
    // Application code
} catch (Exception $e) {
    $exceptionHandler->handleException($e);
}

// Global exception handling in index.php
set_exception_handler([$exceptionHandler, 'handleException']);
```

### LogQueryBuilder

Primary purpose: Generate standardized SQL queries for audit log operations.

#### Key Methods

```php
// Build WHERE clause and parameters for audit log queries
public function buildWhereClause(array $filters): array  // [whereClause, params]

// Build SQL queries for log retrieval with pagination
public function buildSelectQuery(array $filters): array  // Query parts including SQL and parameters

// Build SQL query for direct export to CSV file
public function buildExportQuery(
    array $filters,               // Filters to apply
    ?string $filepath = null      // Optional file path for direct export
): array                          // Export query information

// Build SQL and params for deleting logs
public function buildDeleteQuery(
    array $filters,               // Filters to determine which logs to delete
    bool $forceBulkDelete = false // Whether to allow bulk deletion without ID restrictions
): array                          // Delete query information
```

#### Usage Example

```php
$logQueryBuilder = new LogQueryBuilder($securityHelper);

// Build and execute a filtered query
$queryInfo = $logQueryBuilder->buildSelectQuery([
    'start_date' => '2023-01-01',
    'end_date' => '2023-01-31',
    'log_level' => 'error',
    'user_id' => 123,
    'page' => 1,
    'per_page' => 20
]);

$logs = $db->select($queryInfo['mainSql'], $queryInfo['params']);
$total = $db->select($queryInfo['countSql'], $queryInfo['params'])[0]['total'];

// Export logs to CSV
$exportInfo = $logQueryBuilder->buildExportQuery([
    'log_levels' => ['error', 'critical'],
    'relative_date' => 'last_30_days'
], '/tmp/error_logs.csv');

$db->rawQuery($exportInfo['sql'], $exportInfo['params']);
```

### SetupHelper

Primary purpose: Setup and verify the application environment.

#### Key Methods

```php
// Add required indexes to database tables if they don't exist
public function ensureIndexes(): void

// Verify that the application is running in a secure environment
public function verifySecureEnvironment(): array  // Returns array of security issues
```

#### Usage Example

```php
$setupHelper = new SetupHelper($dbHelper, $logger);

// During application boot
try {
    // Ensure database indexes exist
    $setupHelper->ensureIndexes();
    
    // Check for security issues
    $securityIssues = $setupHelper->verifySecureEnvironment();
    if (!empty($securityIssues)) {
        foreach ($securityIssues as $issue) {
            $logger->warning("Security issue: {$issue}");
        }
    }
} catch (Exception $e) {
    // Handle setup errors
    $logger->critical("Setup failed: " . $e->getMessage());
}
```

## Best Practices

1. **Dependency Injection**: Always inject dependencies into helpers rather than creating them inside
2. **Error Handling**: Use try-catch blocks when calling helper methods that might throw exceptions
3. **Logging**: Provide context in logs to help with troubleshooting
4. **Security**: Never log sensitive information like passwords or tokens
5. **Configuration**: Use environment variables for configuration rather than hardcoding values

## Creating New Helpers

When creating new helpers:

1. Place them in the `App\Helpers` namespace
2. Follow single responsibility principle - each helper should have a clear purpose
3. Use dependency injection for dependencies
4. Add comprehensive PHPDoc comments
5. Log important operations and errors
6. Include type hints for parameters and return values
7. Add the new helper to this documentation
