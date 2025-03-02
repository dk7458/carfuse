# Token Validation Changes

## TokenValidator Deprecation

The `TokenValidator` class has been deprecated in favor of the more robust `TokenService` class. This change centralizes all token-related functionality into a single service class, making the codebase more maintainable and testable.

## Migration Guide

### Before

```php
use App\Helpers\TokenValidator;

// In controllers
$user = TokenValidator::validateToken($request->getHeader('Authorization'));
if (!$user) {
    // Handle unauthorized access
}
```

### After

```php
use App\Services\Auth\TokenService;

// In constructor
private TokenService $tokenService;

public function __construct(TokenService $tokenService, /* other dependencies */) {
    $this->tokenService = $tokenService;
    // ...
}

// In controller methods
$user = $this->tokenService->validateTokenFromHeader($request->getHeader('Authorization')[0] ?? null);
// or
$user = $this->tokenService->validateRequest($request);

if (!$user) {
    // Handle unauthorized access
}
```

## New TokenService Methods

- `validateTokenFromHeader($tokenHeader)` - Validates a token from an Authorization header
- `extractToken($request)` - Extracts a token from various request formats
- `validateRequest($request)` - Validates a token and returns user data in one step
- `verifyToken($token)` - Verifies JWT token and returns decoded payload

## Benefits

1. **Centralized Logic**: All token operations are now in one service
2. **Dependency Injection**: The service can be properly injected, allowing for easier testing
3. **Consistent Error Handling**: All token errors are handled consistently
4. **Audit Logging**: Token validations are now logged in the audit trail

## Automatic Compatibility

To ease migration, a compatibility layer has been added to the old `TokenValidator` class that delegates to `TokenService`. However, you should update your code to use `TokenService` directly, as the compatibility layer will be removed in a future release.
