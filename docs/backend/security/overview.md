# Security Overview

## Security Architecture Overview
The backend security architecture implements a multi-layered defense approach through specialized middleware components and services. Authentication is primarily handled via JWT tokens (using AuthMiddleware and TokenService), while data protection is ensured through CSRF protection (CsrfMiddleware) and selective encryption (EncryptionMiddleware). Sessions are secured with HTTP-only cookies, secure flags, and automatic regeneration to prevent fixation attacks. The system incorporates robust token validation, rate limiting on authentication endpoints, comprehensive security logging, and user data protection through careful attribute management in the request lifecycle.

## Key Security Measures Implemented
- JWT-based authentication with secure token management (generation, validation, refresh)
- Session protection with secure cookies, automatic ID regeneration, and strict configuration
- CSRF protection with token validation for state-changing requests
- Request/response encryption for sensitive endpoints and data fields
- Rate limiting on authentication endpoints to prevent brute force attacks
- Secure cookie attributes (HttpOnly, SameSite, Secure flags)
- Session fingerprinting to detect hijacking attempts
- Input sanitization to prevent XSS attacks
- Comprehensive security audit logging
- Token revocation capabilities and expired token cleanup
- Different authentication flows (header-based, cookie-based)
- Special security handling for HTMX requests

## Security Responsibilities by Component

| Component               | Responsibility                                                       |
|-------------------------|----------------------------------------------------------------------|
| AuthMiddleware          | Extracts and validates JWT tokens, attaches user data to requests    |
| CsrfMiddleware          | Generates and validates CSRF tokens for state-changing operations    |
| EncryptionMiddleware    | Encrypts/decrypts sensitive request and response data               |
| TokenService            | Manages JWT tokens (creation, verification, refresh, revocation)     |
| SessionMiddleware       | Secures sessions with regeneration, secure cookies, and fingerprinting |
| RequireAuthMiddleware   | Enforces authentication requirements on protected routes             |
| UserDataMiddleware      | Manages user data in session and request attributes                 |
| SecurityHelper          | Provides security utilities (sanitization, session management, CSRF) |
| TokenValidationMiddleware| Validates tokens and attaches user data to request                  |
| RateLimiter             | Prevents brute force attacks on authentication endpoints            |

## Integration Points between Security Systems

1. **Auth Flow Integration**:
   - AuthMiddleware extracts tokens → TokenService validates → User data attached to request
   - RequireAuthMiddleware checks request attributes set by AuthMiddleware
   - AuthController uses TokenService and RateLimiter to secure authentication endpoints

2. **Session and CSRF Integration**:
   - SessionMiddleware secures session → CsrfMiddleware uses session to store/validate tokens
   - HtmxMiddleware adds CSRF tokens to response headers for AJAX requests
   - UserDataMiddleware loads user data from session secured by SessionMiddleware

3. **Data Protection Chain**:
   - EncryptionMiddleware protects sensitive data based on endpoint configuration
   - SecurityHelper sanitizes input that may be processed by other components
   - TokenService integrates with AuditService to log security events

4. **Token Lifecycle Management**:
   - AuthService issues tokens → TokenService manages → AuthMiddleware validates
   - RefreshToken model tracks token status → TokenService coordinates revocation

## Security Flow Diagram

```
                    ┌──────────────────┐
                    │  Client Request  │
                    └────────┬─────────┘
                             │
                             ▼
┌────────────────────────────────────────────────┐
│               Session Handling                 │
│  ┌──────────────┐        ┌──────────────────┐  │
│  │SessionMiddle-│───────▶│Session Integrity │  │
│  │    ware      │        │     Check        │  │
│  └──────────────┘        └──────────────────┘  │
└────────────────────┬───────────────────────────┘
                     │
                     ▼
┌────────────────────────────────────────────────┐
│                CSRF Protection                 │
│  ┌──────────────┐        ┌──────────────────┐  │
│  │CsrfMiddleware│───────▶│Token Validation  │  │
│  └──────────────┘        │                  │  │
│                          └──────────────────┘  │
└────────────────────┬───────────────────────────┘
                     │
                     ▼
┌────────────────────────────────────────────────┐
│             Authentication Flow                │
│  ┌──────────────┐        ┌──────────────────┐  │
│  │AuthMiddleware│───────▶│  TokenService    │  │
│  └──────────────┘        │ Token Validation │  │
│          │               └──────────────────┘  │
│          ▼                                     │
│  ┌──────────────┐                             │
│  │RequireAuth   │                             │
│  │ Middleware   │                             │
│  └──────────────┘                             │
└────────────────────┬───────────────────────────┘
                     │
                     ▼
┌────────────────────────────────────────────────┐
│               Data Protection                  │
│  ┌──────────────┐        ┌──────────────────┐  │
│  │Encryption    │───────▶│Sensitive Data    │  │
│  │Middleware    │        │Processing        │  │
│  └──────────────┘        └──────────────────┘  │
└────────────────────┬───────────────────────────┘
                     │
                     ▼
         ┌──────────────────────┐
         │  Application Logic   │
         │  (Controllers)       │
         └──────────────────────┘
```

## Security Best Practices Implemented

- Tokens are stored using HTTP-only cookies to prevent JavaScript access
- Rate limiting prevents brute force attacks on authentication endpoints
- Session IDs are regenerated periodically to prevent session fixation
- Sensitive data is encrypted when stored and transmitted
- CSRF tokens protect against cross-site request forgery
- Input sanitization defends against XSS attacks
- Comprehensive security logging provides audit trail for security events
- Multiple validation layers ensure defense in depth
