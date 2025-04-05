# User Management API Endpoints

## Overview

The User Management API provides endpoints for managing user accounts, profiles, and account settings. This documentation distinguishes between API endpoints (JSON-based) and UI endpoints (HTML/form-based).

## Authentication and Permissions

| Endpoint Pattern              | Required Role | Authentication Method | Notes                                     |
|-------------------------------|---------------|----------------------|-------------------------------------------|
| `POST /users/register`        | None          | None                 | Public JSON endpoint to register users    |
| `GET /users/profile`          | User          | Token                | JSON API for profile retrieval            |
| `PUT /users/profile`          | User          | Token                | JSON API for profile updates              |
| `POST /users/password/reset-request` | None   | None                 | Public JSON endpoint for password reset   |
| `POST /users/password/reset`  | None          | None                 | Reset password with token (JSON)          |
| `GET /users/dashboard`        | User          | Token                | JSON API for dashboard data               |
| `GET /users/profile/page`     | User          | Session              | HTML page for user profile                |
| `POST /users/profile/update`  | User          | Session              | Form-based profile update (supports files)|
| `POST /users/password/change` | User          | Session              | Form-based password change                |

## Rate Limiting

The following rate limits apply to user management endpoints:
- Registration: 5 attempts per hour per IP address
- Password operations: 10 attempts per hour per user or IP address
- Profile updates: 30 requests per hour per user

---

## JSON API Endpoints

### Register User

Create a new user account in the system.

#### HTTP Request

`POST /users/register`

#### Content-Type

`application/json`

#### Request Body Parameters

| Parameter  | Type   | Required | Description           | Constraints                    |
|------------|--------|----------|-----------------------|-------------------------------|
| `email`    | String | Yes      | User's email address  | Valid email format            |
| `password` | String | Yes      | User's password       | Min: 6 characters             |
| `name`     | String | Yes      | User's name           | Non-empty string              |

#### Example Request

```json
{
  "email": "new.user@example.com",
  "password": "securePassword123",
  "name": "Jane Smith"
}
```

#### Response

Status code: `201 Created`

```json
{
  "status": "success",
  "message": "User registered successfully",
  "data": {
    "user_id": "123"
  }
}
```

#### Error Codes

| Status Code | Error Code           | Description                                    |
|-------------|----------------------|------------------------------------------------|
| 400         | `VALIDATION_ERROR`   | Missing required fields or validation failed   |
| 400         | `EMAIL_IN_USE`       | Email address is already registered            |
| 500         | `REGISTRATION_FAILED`| Server failed to process registration          |

#### Notes

- Users are assigned the default "user" role automatically
- Passwords are securely hashed before storage
- New user accounts are logged in the audit trail

---

### Get User Profile

Retrieve the profile information of the authenticated user.

#### HTTP Request

`GET /users/profile`

#### Authentication

Requires a valid authentication token in the Authorization header.

#### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "User profile retrieved",
  "data": {
    "id": "123",
    "name": "Jane Smith",
    "email": "jane.smith@example.com",
    "bio": "Software developer",
    "location": "New York",
    "avatar_url": "/uploads/avatars/123.jpg",
    "created_at": "2023-05-01T14:30:45Z",
    "updated_at": "2023-06-15T09:22:18Z"
  }
}
```

#### Error Codes

| Status Code | Error Code           | Description                                  |
|-------------|----------------------|----------------------------------------------|
| 401         | `UNAUTHORIZED`       | User not authenticated                       |
| 404         | `USER_NOT_FOUND`     | User account not found                       |
| 500         | `SERVER_ERROR`       | Failed to retrieve profile                   |

#### Notes

- Profile views are logged for security auditing
- Sensitive data is excluded from the response

---

### Update User Profile (JSON API)

Update the profile information of the authenticated user.

#### HTTP Request

`PUT /users/profile`

#### Authentication

Requires a valid authentication token in the Authorization header.

#### Content-Type

`application/json`

#### Request Body Parameters

| Parameter   | Type   | Required | Description            | Constraints                     |
|-------------|--------|----------|------------------------|--------------------------------|
| `name`      | String | No       | User's name            | Max: 100 characters            |
| `bio`       | String | No       | User biography         | Max: 500 characters            |
| `location`  | String | No       | User location          | Max: 100 characters            |
| `avatar_url`| String | No       | URL to user avatar     | Valid URL format, Max: 255 chars|

#### Example Request

```json
{
  "name": "Jane Smith",
  "bio": "Senior Software Developer with 10 years experience",
  "location": "New York, USA"
}
```

#### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Profile updated successfully",
  "data": {
    "id": "123",
    "name": "Jane Smith",
    "email": "jane.smith@example.com",
    "bio": "Senior Software Developer with 10 years experience",
    "location": "New York, USA",
    "avatar_url": "/uploads/avatars/123.jpg",
    "updated_at": "2023-06-15T10:22:18Z"
  }
}
```

#### Error Codes

| Status Code | Error Code           | Description                                  |
|-------------|----------------------|----------------------------------------------|
| 400         | `VALIDATION_ERROR`   | Invalid field values                         |
| 401         | `UNAUTHORIZED`       | User not authenticated                       |
| 500         | `UPDATE_FAILED`      | Failed to update profile                     |

#### Notes

- Profile updates are logged in the audit trail
- Only fields that need to be updated should be included in the request

---

### Request Password Reset

Request a password reset link via email.

#### HTTP Request

`POST /users/password/reset-request`

#### Content-Type

`application/json`

#### Request Body Parameters

| Parameter | Type   | Required | Description           | Constraints           |
|-----------|--------|----------|----------------------|----------------------|
| `email`   | String | Yes      | User's email address | Valid email format    |

#### Example Request

```json
{
  "email": "user@example.com"
}
```

#### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "If your email is in our system, you will receive reset instructions shortly"
}
```

#### Error Codes

| Status Code | Error Code           | Description                                  |
|-------------|----------------------|----------------------------------------------|
| 400         | `INVALID_EMAIL`      | Invalid email format                         |
| 500         | `REQUEST_FAILED`     | Failed to process reset request              |

#### Notes

- For security reasons, success response is returned even if email is not found
- IP address of the request is logged
- Reset tokens expire after 1 hour
- Reset requests are logged in the audit trail

---

### Reset Password with Token

Reset a user's password using a valid reset token.

#### HTTP Request

`POST /users/password/reset`

#### Content-Type

`application/json`

#### Request Body Parameters

| Parameter | Type   | Required | Description           | Constraints                    |
|-----------|--------|----------|-----------------------|-------------------------------|
| `token`   | String | Yes      | Password reset token  | Valid non-expired token       |
| `password`| String | Yes      | New password          | Min: 6 characters             |

#### Example Request

```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "password": "newSecurePassword456"
}
```

#### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Password has been reset successfully"
}
```

#### Error Codes

| Status Code | Error Code           | Description                                  |
|-------------|----------------------|----------------------------------------------|
| 400         | `INVALID_INPUT`      | Missing token or password                    |
| 400         | `PASSWORD_TOO_SHORT` | Password does not meet minimum requirements  |
| 400         | `INVALID_TOKEN`      | Token is invalid or expired                  |
| 500         | `RESET_FAILED`       | Failed to reset password                     |

#### Notes

- Tokens are single-use and expire after 1 hour
- Password resets are logged in the audit trail
- Passwords are securely hashed before storage

---

### User Dashboard

Retrieve user's dashboard data including account information and recent activities.

#### HTTP Request

`GET /users/dashboard`

#### Authentication

Requires a valid authentication token in the Authorization header.

#### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "User Dashboard",
  "data": {
    "user": {
      "id": "123",
      "name": "Jane Smith",
      "email": "jane.smith@example.com",
      "avatar_url": "/uploads/avatars/123.jpg"
    },
    "recent_activity": [
      {
        "id": 1,
        "type": "profile_updated",
        "description": "Updated profile information",
        "timestamp": "2023-06-10T14:30:45Z",
        "ip_address": "192.168.1.1"
      },
      {
        "id": 2,
        "type": "login",
        "description": "Successful login",
        "timestamp": "2023-06-09T08:15:22Z",
        "ip_address": "192.168.1.1"
      }
    ]
  }
}
```

#### Error Codes

| Status Code | Error Code           | Description                                  |
|-------------|----------------------|----------------------------------------------|
| 401         | `UNAUTHORIZED`       | User not authenticated                       |
| 404         | `USER_NOT_FOUND`     | User account not found                       |
| 500         | `SERVER_ERROR`       | Failed to retrieve dashboard data            |

#### Notes

- Dashboard access is logged in the audit trail
- Recent activity is limited to the 5 most recent events

---

## HTML/Form-Based Endpoints

### View Profile Page

View the user profile page in HTML format.

#### HTTP Request

`GET /users/profile/page`

#### Authentication

Requires an active user session.

#### Response

Returns an HTML page with the user's profile information.

#### Error Handling

- If not authenticated, redirects to login page
- If server error occurs, redirects to error page

#### Notes

- The profile view contains user details including:
  - Name, email, bio, phone
  - Location, avatar image
  - Registration date
  - User preferences
- Profile page views are logged in the audit trail

---

### Update Profile (Form-based)

Update the user profile using a form submission.

#### HTTP Request

`POST /users/profile/update`

#### Authentication

Requires an active user session.

#### Content-Type

`multipart/form-data` (for file uploads) or `application/json`

#### Request Parameters

| Parameter     | Type   | Required | Description               | Constraints                     |
|---------------|--------|----------|---------------------------|--------------------------------|
| `name`        | String | Yes      | User's name               | Max: 100 characters            |
| `bio`         | String | No       | User biography            | Max: 500 characters            |
| `phone`       | String | No       | Phone number              | Max: 20 characters             |
| `location`    | String | No       | User location             | Max: 100 characters            |
| `avatar`      | File   | No       | Profile picture           | JPG, PNG, or GIF; Max: 2MB     |
| `remove_avatar` | String | No     | Remove profile picture    | Value: "1" to remove           |
| `preferences` | Array  | No       | User preferences          | Array of key-value pairs       |

#### Response

```json
{
  "status": "success",
  "message": "Profil został zaktualizowany pomyślnie",
  "data": {
    "user": {
      "id": "123",
      "name": "Jane Smith",
      "email": "jane.smith@example.com",
      "bio": "Senior Developer",
      "phone": "123456789",
      "location": "New York",
      "avatar_url": "/uploads/avatars/avatar_123_1623760938.jpg"
    }
  }
}
```

#### Error Codes

| Status Code | Error Code           | Description                                  |
|-------------|----------------------|----------------------------------------------|
| 400         | `VALIDATION_ERROR`   | Form validation errors                       |
| 401         | `UNAUTHORIZED`       | User not authenticated                       |
| 500         | `AVATAR_UPLOAD_FAILED` | Failed to upload profile picture           |
| 500         | `UPDATE_FAILED`      | Failed to update profile                     |

#### Notes

- Supports both form submissions and JSON requests
- Avatar uploads have size and type restrictions
- Profile updates are logged in the audit trail

---

### Change Password (Form-based)

Change the password for the authenticated user.

#### HTTP Request

`POST /users/password/change`

#### Authentication

Requires an active user session.

#### Request Parameters

| Parameter         | Type   | Required | Description             | Constraints                    |
|-------------------|--------|----------|-------------------------|-------------------------------|
| `current_password`| String | Yes      | Current user password   | Must match current password   |
| `new_password`    | String | Yes      | New password            | Min: 6 characters             |
| `confirm_password`| String | Yes      | Confirm new password    | Must match new_password       |

#### Response

```json
{
  "status": "success",
  "message": "Hasło zostało zmienione pomyślnie"
}
```

#### Error Codes

| Status Code | Error Code             | Description                                  |
|-------------|------------------------|----------------------------------------------|
| 400         | `MISSING_FIELDS`       | Required fields not provided                 |
| 400         | `PASSWORDS_NOT_MATCH`  | New password and confirmation don't match    |
| 400         | `PASSWORD_TOO_SHORT`   | Password too short (minimum 6 characters)    |
| 400         | `INCORRECT_PASSWORD`   | Current password is incorrect                |
| 401         | `UNAUTHORIZED`         | User not authenticated                       |
| 500         | `CHANGE_FAILED`        | Failed to change password                    |

#### Notes

- Password changes are logged in the audit trail
- Passwords are securely hashed before storage
