# Exceptions

## Overview
The CarFuse exception system provides standardized error handling across the application. Custom exception types ensure consistency in error reporting, logging, and API responses. Each exception type can include contextual data to aid in troubleshooting while maintaining clean separation between business logic and error handling.

## Custom Exception Types

### PaymentException
A specialized exception for handling payment-related errors. Includes detailed context data and standardized error codes.

```php
// Example of throwing a payment validation exception
throw new PaymentException(
    "Invalid credit card number", 
    PaymentException::PAYMENT_VALIDATION_ERROR,
    ['cardNumber' => $maskedCardNumber]
);
```

**Error Code Constants:**
| Constant | Code | Purpose |
|----------|------|---------|
| PAYMENT_VALIDATION_ERROR | 100 | Input validation failures (invalid card, expired dates) |
| PAYMENT_PROCESSING_ERROR | 200 | Errors during payment processing |
| PAYMENT_GATEWAY_ERROR | 300 | External payment gateway communication issues |
| REFUND_ERROR | 400 | Problems during refund operations |
| FRAUD_DETECTION_ERROR | 500 | Potential fraudulent activity detected |
| DATA_ERROR | 600 | Payment data inconsistencies |

### Other Common Exception Types

| Exception Class | Purpose | When to Use |
|-----------------|---------|-------------|
| ValidationException | Data validation errors | When user input fails validation rules |
| AuthorizationException | Permission errors | When a user lacks required permissions |
| NotFoundException | Resource not found | When requested data doesn't exist |
| ServiceException | External service errors | When integrated services fail |
| DatabaseException | Database operation errors | When database operations fail |

## Exception Handling Patterns

### Try/Catch Blocks
Use try/catch blocks to handle exceptions at the appropriate level:

```php
try {
    // Payment processing code
    $paymentProcessor->processPayment($paymentData);
} catch (PaymentException $e) {
    // Handle payment-specific exceptions
    $logger->error('Payment failed', [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'context' => $e->getContext() // Using the custom context method
    ]);
    
    // Take recovery action or re-throw
} catch (\Exception $e) {
    // Handle other exceptions
    $logger->error('Unexpected error', [
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
```

### Exception Middleware
In web applications, use middleware to catch unhandled exceptions and convert them to appropriate responses:

```php
// Example exception middleware pseudocode
public function handle($request, $next)
{
    try {
        return $next($request);
    } catch (ValidationException $e) {
        return response()->json(['errors' => $e->getErrors()], 422);
    } catch (PaymentException $e) {
        if ($e->getCode() == PaymentException::PAYMENT_VALIDATION_ERROR) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
        return response()->json(['error' => 'Payment processing error'], 500);
    } catch (\Exception $e) {
        // Log and return generic error
        return response()->json(['error' => 'An unexpected error occurred'], 500);
    }
}
```

## Mapping Exceptions to API Responses

The following table shows how exception types should map to HTTP status codes:

| Exception Type | HTTP Status | Response Format |
|----------------|-------------|-----------------|
| ValidationException | 422 | `{"errors": {"field": ["error messages"]}}` |
| AuthorizationException | 403 | `{"error": "Permission denied message"}` |
| NotFoundException | 404 | `{"error": "Resource not found message"}` |
| PaymentException (validation) | 400 | `{"error": "Payment validation message"}` |
| PaymentException (processing/gateway) | 502 | `{"error": "Payment processing error"}` |
| PaymentException (fraud) | 400 | `{"error": "Payment declined message"}` |
| Unhandled exceptions | 500 | `{"error": "Internal server error"}` |

## Best Practices

### Throwing Exceptions

1. **Use specific exception types** that match the error domain
   ```php
   // Good
   throw new PaymentException("Card declined", PaymentException::PAYMENT_GATEWAY_ERROR);
   
   // Avoid
   throw new \Exception("Card declined");
   ```

2. **Include contextual information** that aids troubleshooting
   ```php
   throw new PaymentException(
       "Payment declined", 
       PaymentException::PAYMENT_GATEWAY_ERROR,
       [
           'transactionId' => $transaction->id,
           'gateway' => $gateway->name,
           'response' => $gatewayResponse
       ]
   );
   ```

3. **Use consistent error codes** defined as constants

4. **Write clear error messages** that describe the problem
   ```php
   // Good
   throw new ValidationException("Card expiration date must be in the future");
   
   // Avoid
   throw new ValidationException("Invalid input");
   ```

### Catching Exceptions

1. **Catch exceptions at the appropriate level** - don't catch too early or too late
   
2. **Catch specific exceptions before general ones**
   ```php
   try {
       // Code that might throw exceptions
   } catch (PaymentException $e) {
       // Handle payment exceptions specifically
   } catch (ValidationException $e) {
       // Handle validation exceptions
   } catch (\Exception $e) {
       // Handle other exceptions
   }
   ```

3. **Log exceptions with context** to aid troubleshooting
   ```php
   try {
       // Code that might throw exceptions
   } catch (PaymentException $e) {
       $logger->error('Payment processing failed', [
           'message' => $e->getMessage(),
           'code' => $e->getCode(),
           'context' => $e->getContext(),
           // Don't include sensitive data like full card numbers
       ]);
   }
   ```

4. **Don't catch exceptions you can't handle** - let them bubble up
   
5. **Avoid empty catch blocks** that silently swallow errors

6. **Re-throw with context** when appropriate
   ```php
   try {
       $gateway->processPayment($payment);
   } catch (\Exception $e) {
       throw new PaymentException(
           "Payment gateway error", 
           PaymentException::PAYMENT_GATEWAY_ERROR,
           ['originalError' => $e->getMessage()],
           $e  // Pass original exception as previous
       );
   }
   ```
