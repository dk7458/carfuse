# Security Best Practices

*Last updated: 2023-11-15*

This document outlines the security best practices for developing applications using the CarFuse framework.

## Table of Contents
- [Authentication Best Practices](#authentication-best-practices)
- [Authorization Best Practices](#authorization-best-practices)
- [Data Protection](#data-protection)
- [Input Validation](#input-validation)
- [Error Handling and Logging](#error-handling-and-logging)
- [Security Headers](#security-headers)
- [Dependency Management](#dependency-management)
- [Security Checklist](#security-checklist)

## Authentication Best Practices

### Password Handling

1. **Never store plaintext passwords**
   - CarFuse automatically hashes passwords using Argon2id
   
2. **Enforce strong password policies**
   ```php
   // In your validation rules
   'password' => 'required|min:12|complexity'
   ```
   
3. **Implement rate limiting for login attempts**
   ```php
   // Automatically handled by SecurityMiddleware
   SecurityMiddleware::rateLimitedEndpoint();
   ```

### Multi-Factor Authentication

1. **Enable MFA for sensitive operations**
   ```php
   // Check if MFA is required for this action
   if (SecurityService::requiresMfa($action)) {
       // Redirect to MFA verification
   }
   ```

2. **Verify MFA tokens securely**
   ```php
   $valid = SecurityService::verifyMfaToken($userId, $token);
   ```

### Session Management

1. **Set appropriate session timeouts**
   ```php
   // In config/security.php
   'session' => [
       'lifetime' => 3600, // 1 hour
       'idle_timeout' => 900 // 15 minutes
   ]
   ```

2. **Regenerate session IDs after authentication state changes**
   ```php
   SecurityService::regenerateSession();
   ```

## Authorization Best Practices

### Role-Based Access Control

1. **Use built-in RBAC functions instead of custom checks**
   ```php
   // Good - use the framework
   if (SecurityService::hasRole('admin')) {
       // Admin-only code
   }
   
   // Avoid - custom checks are error-prone
   if ($_SESSION['user_role'] === 'admin') {
       // Error-prone approach
   }
   ```

2. **Apply the principle of least privilege**
   - Assign the minimum necessary permissions
   - Regularly audit role assignments

3. **Define clear resource access mappings**
   ```php
   // In config/rbac.php
   'resources' => [
       'user-management' => ['admin'],
       'content-edit' => ['admin', 'editor'],
       'content-view' => ['admin', 'editor', 'viewer']
   ]
   ```

## Data Protection

### Sensitive Data Handling

1. **Use the `SensitiveData` class for PII**
   ```php
   $data = new SensitiveData($personalInfo);
   $data->setMaskingStrategy('email'); // Masks email addresses
   $maskedData = $data->getMasked(); // For logging
   ```

2. **Encrypt sensitive data at rest**
   ```php
   // Encrypt before storing
   $encrypted = SecurityService::encrypt($sensitiveData);
   
   // Decrypt when needed
   $decrypted = SecurityService::decrypt($encrypted);
   ```

## Input Validation

1. **Validate all user input**
   ```php
   // Use the validation service
   $validator = new Validator($request->all());
   $validator->rules([
       'name' => 'required|string|max:255',
       'email' => 'required|email',
       'age' => 'integer|min:18|max:120'
   ]);
   
   if (!$validator->passes()) {
       $errors = $validator->getErrors();
       // Handle validation errors
   }
   ```

2. **Use prepared statements for all database queries**
   ```php
   // Good - using prepared statement
   $users = DB::query()
       ->where('role', '=', $role)
       ->get();
   
   // Bad - vulnerable to injection
   $query = "SELECT * FROM users WHERE role = '$role'";
   ```

3. **Context-appropriate output encoding**
   ```php
   // HTML context
   echo htmlspecialchars($userInput);
   
   // JavaScript context
   echo "var data = " . json_encode($userInput) . ";";
   ```

## Error Handling and Logging

### Security Event Logging

1. **Log all security-relevant events**
   ```php
   SecurityService::logSecurityEvent('auth_failure', 'Invalid login attempt', [
       'username' => $maskedUsername,
       'ip_address' => $request->getIpAddress()
   ]);
   ```

2. **Available event types**
   - `auth_success` - Successful authentication
   - `auth_failure` - Failed authentication
   - `auth_logout` - User logout
   - `access_denied` - Authorization failure
   - `csrf_failure` - CSRF token validation failure
   - `account_locked` - Account locked due to failed attempts
   - `sensitive_action` - Any sensitive operation (deletion, etc.)

### Error Handling

1. **Never expose sensitive information in errors**
   ```php
   try {
       // Database operation
   } catch (Exception $e) {
       // Good - generic message to user
       $userMessage = "An error occurred while processing your request.";
       
       // Good - detailed log for developers
       Logger::error("Database error: " . $e->getMessage(), [
           'trace' => $e->getTraceAsString(),
           'user_id' => $userId
       ]);
       
       return new ErrorResponse($userMessage);
   }
   ```

2. **Use security-focused exception handling**
   ```php
   try {
       SecurityService::requirePermission('delete_user');
       // Delete user code
   } catch (PermissionException $e) {
       SecurityService::logSecurityEvent('access_denied', $e->getMessage());
       redirect('/access-denied');
   }
   ```

## Security Headers

CarFuse automatically adds security headers to all responses, but you can customize them:

```php
// In config/security.php
'headers' => [
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' https://trusted-cdn.com",
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
    'Referrer-Policy' => 'strict-origin-when-cross-origin'
]
```

## Dependency Management

1. **Regularly update dependencies**
   ```bash
   composer update --with-dependencies
   ```

2. **Run security audits**
   ```bash
   composer audit
   npm audit (for frontend dependencies)
   ```

3. **Set up automated vulnerability scanning**
   - Configure GitHub Dependabot or similar tools
   - Review security advisories regularly

## Security Checklist

Use this checklist before deploying applications:

- [ ] Authentication mechanisms are using framework-provided features
- [ ] Authorization checks are in place for all sensitive operations
- [ ] CSRF protection is enabled for all forms
- [ ] Input validation is implemented for all user inputs
- [ ] Sensitive data is encrypted at rest
- [ ] Security logs are being captured
- [ ] Security headers are properly configured
- [ ] Error messages don't leak sensitive information
- [ ] Latest security patches are applied
- [ ] Sessions are managed securely
- [ ] Password policies are enforced
- [ ] Rate limiting is in place for sensitive endpoints

## Related Documentation

- [Security Overview](overview.md)
- [Authentication Mechanisms](authentication.md)
- [CSRF Protection](csrf-protection.md)
- [Role-Based Access Control](../components/auth/rbac.md)
