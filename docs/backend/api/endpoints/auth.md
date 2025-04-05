# Authentication API Endpoints

## Overview

The authentication system in CarFuse employs a hybrid approach, combining JWT (JSON Web Tokens) with traditional PHP session management for maximum security and flexibility.

### Authentication Methods

- **JWT Authentication**: Used primarily for API requests, with tokens stored in secure HttpOnly cookies
- **Session Authentication**: Used for web UI access, with secure PHP native sessions
- **Hybrid Approach**: Some endpoints support both methods depending on context

### Key Components

- **SessionMiddleware**: Configures secure PHP sessions with protection against session fixation, hijacking, and CSRF
- **TokenValidationMiddleware**: Validates JWT tokens from cookies or Authorization headers
- **RequireAuthMiddleware**: Ensures a user is authenticated before accessing protected resources
- **SecurityHelper**: Provides session integrity validation, CSRF protection, and security logging

## Authentication Flow

1. **Login Process**:
   - User submits credentials to `/auth/login`
   - JWT and refresh tokens are set as HttpOnly cookies
   - Session is also established with security fingerprinting
   
2. **Authenticated Requests**:
   - API requests: Validated via TokenValidationMiddleware
   - Web UI requests: Validated via session checks
   - Session integrity verified by IP and user-agent fingerprinting
   
3. **Session Security**:
   - Sessions regenerated every 30 minutes to prevent fixation
   - Automatic timeout after 30 minutes of inactivity
   - CSRF tokens required for sensitive operations

## Authentication and Permissions

| Endpoint Pattern         | Required Role | Auth Method        | Notes                                       |
|--------------------------|---------------|--------------------|--------------------------------------------|
| `POST /auth/login`       | None          | -                  | Public endpoint to authenticate users        |
| `POST /auth/register`    | None          | -                  | Public endpoint in UserController            |
| `GET /auth/user`         | User          | JWT or Session     | Get authenticated user details              |
| `POST /auth/logout`      | User          | JWT or Session     | Invalidates both JWT and session            |
| `POST /auth/reset_request` | None        | -                  | Request password reset (no auth required)   |
| `POST /auth/reset`       | None          | Reset Token        | Reset password with single-use token        |
| `GET /user/profile`      | User          | JWT or Session     | Get user profile (in UserController)        |
| `POST /user/profile`     | User          | JWT or Session     | Update user profile (in UserController)     |

## Rate Limiting

Authentication endpoints employ strict rate limits to prevent brute force attacks:
- Login: 10 attempts per minute per IP address/email
- Registration: 5 attempts per hour per IP address
- Password reset request: 3 attempts per hour per email
- Password reset: 5 attempts per hour per token

---

## User Login

Authenticates a user and provides access tokens.

### HTTP Request

`POST /auth/login`

### Request Body Parameters

| Parameter | Type   | Required | Description              | Constraints                     |
|-----------|--------|----------|--------------------------|--------------------------------|
| `email`   | String | Yes      | User's email address     | Valid email format             |
| `password`| String | Yes      | User's password          | Min length: 6 characters       |

### Example Request

```json
{
  "email": "user@example.com",
  "password": "securePassword123"
}
```

### Response

Status code: `200 OK`

```json
{
  "message": "Login successful",
  "user_id": 123,
  "name": "John Doe"
}
```

JWT and refresh tokens are sent as secure HttpOnly cookies with the following properties:
- `jwt`: 1-hour expiration, Secure, HttpOnly, SameSite=Strict
- `refresh_token`: 7-day expiration, Secure, HttpOnly, SameSite=Strict

### Error Codes

| Status Code | Error Code          | Description                                      |
|-------------|---------------------|--------------------------------------------------|
| 400         | `INVALID_INPUT`     | Missing required fields                          |
| 401         | `UNAUTHORIZED`      | Invalid email or password                        |
| 429         | `TOO_MANY_ATTEMPTS` | Too many login attempts, try again later       |

### Implementation Notes

- Implemented in `AuthController::login()`
- Rate limiting is enforced by `RateLimiter` service
- Login failures are securely logged for anomaly detection

---

## User Registration

Register a new user account.

### HTTP Request

`POST /auth/register` (mapped to UserController::registerUser)

### Request Body Parameters

| Parameter         | Type   | Required | Description            | Constraints                     |
|-------------------|--------|----------|------------------------|--------------------------------|
| `name`            | String | Yes      | User's name            | Required string                 |
| `email`           | String | Yes      | User's email address   | Valid email format             |
| `password`        | String | Yes      | User's password        | Min length: 6 characters       |

### Example Request

```json
{
  "name": "John Doe",
  "email": "john.doe@example.com",
  "password": "securePassword123"
}
```

### Response

Status code: `201 Created`

```json
{
  "status": "success",
  "message": "User registered successfully",
  "user_id": 123
}
```

### Error Codes

| Status Code | Error Code      | Description                                      |
|-------------|-----------------|--------------------------------------------------|
| 400         | `error`         | Missing required fields or validation failed     |
| 400         | `error`         | Email already in use                            |
| 500         | `error`         | Registration failed due to server error          |

### Implementation Notes

- Implemented in `UserController::registerUser()`
- Password is securely hashed by the User model
- Default role 'user' is assigned to new registrations

---

## User Logout

Invalidates current user authentication.

### HTTP Request

`POST /auth/logout`

### Authentication

Requires valid JWT token or active session.

### Response

Status code: `200 OK`

```json
{
  "message": "Logout successful"
}
```

JWT and refresh token cookies are cleared, and PHP session is destroyed.

### Error Codes

| Status Code | Error Code     | Description                                      |
|-------------|----------------|--------------------------------------------------|
| 500         | `error`        | Logout failed due to server error                |

### Implementation Notes

- Implemented in `AuthController::logout()`
- Clears both JWT cookies and PHP session data
- Can be accessed via API or web interface

---

## Get Authenticated User

Get details of the currently authenticated user.

### HTTP Request

`GET /auth/user`

### Authentication

Requires valid JWT token or active session.

### Response

Status code: `200 OK`

```json
{
  "user": {
    "id": 123,
    "name": "John Doe",
    "email": "john.doe@example.com",
    "role": "user",
    "created_at": "2023-01-15T08:30:45Z"
  }
}
```

### Error Codes

| Status Code | Error Code     | Description                                      |
|-------------|----------------|--------------------------------------------------|
| 401         | `error`        | Authentication required                          |
| 500         | `error`        | Failed to get user details                       |

### Implementation Notes

- Implemented in `AuthController::userDetails()`
- Relies on RequireAuthMiddleware for authentication
- Removes sensitive fields like password_hash from response

---

## Request Password Reset

Request a password reset link via email.

### HTTP Request

`POST /auth/reset_request`

### Request Body Parameters

| Parameter | Type   | Required | Description              | Constraints                     |
|-----------|--------|----------|--------------------------|--------------------------------|
| `email`   | String | Yes      | User's email address     | Valid email format             |

### Example Request

```json
{
  "email": "user@example.com"
}
```

### Response

Status code: `200 OK`

```json
{
  "message": "Password reset email sent"
}
```

### Error Codes

| Status Code | Error Code     | Description                                      |
|-------------|----------------|--------------------------------------------------|
| 400         | `error`        | Missing or invalid email                         |
| 500         | `error`        | Password reset request failed                    |

### Implementation Notes

- Implemented in both `AuthController::resetPasswordRequest()` and `UserController::requestPasswordReset()`
- For security, returns success even if email doesn't exist
- Generates a secure token that expires in 1 hour
- Records IP address of requester for security monitoring

---

## Reset Password

Reset password using a token received via email.

### HTTP Request

`POST /auth/reset`

### Request Body Parameters

| Parameter         | Type   | Required | Description              | Constraints                     |
|-------------------|--------|----------|--------------------------|--------------------------------|
| `token`           | String | Yes      | Password reset token     | Valid reset token              |
| `password`        | String | Yes      | New password             | Min length: 6 characters       |
| `confirm_password`| String | Yes      | Confirm new password     | Must match `password`          |

### Example Request

```json
{
  "token": "a1b2c3d4e5...",
  "password": "newSecurePassword123",
  "confirm_password": "newSecurePassword123"
}
```

### Response

Status code: `200 OK`

```json
{
  "message": "Password has been reset successfully"
}
```

### Error Codes

| Status Code | Error Code     | Description                                      |
|-------------|----------------|--------------------------------------------------|
| 400         | `error`        | Missing required fields or passwords don't match |
| 400         | `error`        | Invalid or expired token                         |
| 500         | `error`        | Password reset failed                            |

### Implementation Notes

- Implemented in both `AuthController::resetPassword()` and `UserController::resetPassword()`
- Validates token expiration and single-use status
- Updates password with secure hashing
- Invalidates previous sessions for security

---

## User Profile Management

Profile management endpoints are handled by UserController.

### HTTP Request

`GET /user/profile`

### Authentication

Requires valid JWT token or active session.

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "User profile retrieved",
  "data": {
    "id": 123,
    "name": "John Doe",
    "email": "john@example.com",
    "bio": "Example bio",
    "location": "New York"
  }
}
```

### Implementation Notes

- Implemented in `UserController::getUserProfile()`
- Updates are handled by `UserController::updateUserProfile()`
- All profile operations require authentication

---

## Applying Middleware to Routes

### Example Middleware Stack

```php
// Protected route requiring authentication
$app->get('/api/secure-resource', 'ResourceController:getResource')
    ->add(RequireAuthMiddleware::class)
    ->add(TokenValidationMiddleware::class)
    ->add(SessionMiddleware::class);

// Public route with optional authentication
$app->get('/api/public-resource', 'ResourceController:getPublicResource')
    ->add(TokenValidationMiddleware::class)
    ->add(SessionMiddleware::class);
```

### Middleware Processing Flow

1. `SessionMiddleware` initializes secure session
2. `TokenValidationMiddleware` extracts and validates JWT token
3. `RequireAuthMiddleware` ensures user is authenticated

### Authentication Error Responses

```json
// 401 Unauthorized from TokenValidationMiddleware
{
  "status": "error",
  "message": "Unauthorized"
}

// 401 Unauthorized from RequireAuthMiddleware
{
  "error": "Authentication required",
  "status": 401
}
```

## Security Implementation Details

### Session Security (SecurityHelper)

- **Session Integrity**: IP and User-Agent fingerprinting
- **Session Regeneration**: IDs regenerated every 30 minutes
- **Expiry Enforcement**: Automatic timeout after 30 minutes of inactivity
- **CSRF Protection**: Token generation and validation for form submissions

### Token Validation Process

1. Extract token from HTTP-only cookie or Authorization header
2. Validate token signature and expiration
3. Retrieve and populate user information
4. Add user data to request attributes for controllers

### Rate Limiting Configuration

Rate limiting is applied to sensitive authentication endpoints:
- Login attempts are limited by both email and IP address
- Password reset requests are limited by email address
- All limits are configured in the RateLimiter service
