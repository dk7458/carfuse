# Admin API Endpoints

## Overview

The Admin API provides system administration capabilities including user management, permission control, system configuration, and administrative operations. These endpoints are restricted to users with admin privileges.

## Authentication and Permissions

| Endpoint Pattern                | Required Role | Notes                                      |
|--------------------------------|---------------|-------------------------------------------|
| `GET /admin/users`              | Admin         | Get all users                             |
| `GET /admin/users/{id}`         | Admin         | Get user by ID                            |
| `POST /admin/users`             | Admin         | Create a new user                         |
| `PUT /admin/users/{id}`         | Admin         | Update user details                       |
| `POST /admin/users/{id}/status` | Admin         | Toggle user active status                 |
| `POST /admin/users/{id}/role`   | Admin         | Update user role                          |
| `DELETE /admin/users/{id}`      | Admin         | Delete a user (soft delete)               |
| `POST /admin/users/admin`       | Admin         | Create a new admin user                   |
| `GET /admin/users/page`         | Admin         | HTML format for user management           |

## Rate Limiting

Admin endpoints have stricter rate limiting:
- 30 requests per minute per admin user
- Bulk operations are limited to 10 per hour

---

## Get All Users

Retrieve a paginated list of all users with their roles and status.

### HTTP Request

`GET /admin/users`

### Authentication

Requires a valid admin authentication token.

### Query Parameters

| Parameter  | Type    | Required | Description            | Constraints                     |
|------------|---------|----------|------------------------|--------------------------------|
| `page`     | Integer | No       | Page number            | Default: 1, Min: 1              |
| `per_page` | Integer | No       | Items per page         | Default: 10, Max: 100           |
| `role`     | String  | No       | Filter by user role    | Values: user, admin, manager    |
| `status`   | String  | No       | Filter by user status  | Values: active, inactive        |
| `search`   | String  | No       | Search term            | Searches name, email, phone     |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "User list retrieved successfully",
  "data": [
    {
      "id": 123,
      "name": "John Doe",
      "surname": "Doe",
      "email": "john@example.com",
      "role": "user",
      "active": true,
      "created_at": "2023-04-01T10:20:30Z"
    },
    {
      "id": 124,
      "name": "Jane Smith",
      "surname": "Smith",
      "email": "jane@example.com",
      "role": "admin",
      "active": true,
      "created_at": "2023-04-02T11:20:30Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total_pages": 5,
    "total": 45,
    "per_page": 10
  }
}
```

For HTMX requests (with `HX-Request: true` header), returns HTML table rows.

### Error Codes

| Status Code | Error Code         | Description                                    |
|-------------|-------------------|------------------------------------------------|
| 401         | `UNAUTHORIZED`     | User not authenticated                         |
| 403         | `FORBIDDEN`        | User does not have admin privileges            |
| 500         | `SERVER_ERROR`     | Failed to retrieve users                       |

### Notes

- Sensitive user data like password hashes are never included
- User list access is logged for audit purposes
- Pagination headers are included for UI controls
- Supports both JSON and HTML responses for different UI approaches

---

## Get User by ID

Retrieve detailed information for a specific user.

### HTTP Request

`GET /admin/users/{id}`

### Authentication

Requires a valid admin authentication token.

### Path Parameters

| Parameter | Type    | Required | Description     | Constraints                     |
|-----------|---------|----------|-----------------|--------------------------------|
| `id`      | Integer | Yes      | User identifier | Must be a valid user ID         |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "User retrieved successfully",
  "data": {
    "id": 123,
    "name": "John",
    "surname": "Doe",
    "email": "john@example.com",
    "role": "user",
    "phone": "+1234567890",
    "address": "123 Main St, Anytown, USA",
    "active": true,
    "created_at": "2023-04-01T10:20:30Z",
    "updated_at": "2023-05-15T14:30:45Z",
    "last_login": "2023-06-01T08:22:10Z",
    "metadata": {
      "registration_source": "web",
      "referral_code": "REF123"
    }
  }
}
```

### Error Codes

| Status Code | Error Code           | Description                                    |
|-------------|---------------------|------------------------------------------------|
| 401         | `UNAUTHORIZED`       | User not authenticated                         |
| 403         | `FORBIDDEN`          | User does not have admin privileges            |
| 404         | `USER_NOT_FOUND`     | User not found                                 |
| 500         | `SERVER_ERROR`       | Failed to retrieve user                        |

### Notes

- More detailed user information than the list endpoint
- Viewing user details is logged for audit purposes
- Includes important but non-sensitive metadata

---

## Create User

Create a new user account.

### HTTP Request

`POST /admin/users`

### Authentication

Requires a valid admin authentication token.

### Request Body Parameters

| Parameter | Type   | Required | Description           | Constraints                    |
|-----------|--------|----------|-----------------------|-------------------------------|
| `name`    | String | Yes      | User's first name     | Max: 50 characters            |
| `surname` | String | Yes      | User's last name      | Max: 50 characters            |
| `email`   | String | Yes      | User's email address  | Valid email format            |
| `password`| String | Yes      | User's password       | Min: 8 characters             |
| `role`    | String | No       | User's role           | Default: "user"               |
| `phone`   | String | No       | User's phone number   | Valid phone number format     |
| `active`  | Boolean| No       | User's status         | Default: true                 |

### Example Request

```json
{
  "name": "Jane",
  "surname": "Smith",
  "email": "jane@example.com",
  "password": "securePassword123",
  "role": "user",
  "phone": "+1987654321",
  "active": true
}
```

### Response

Status code: `201 Created`

```json
{
  "status": "success",
  "message": "User created successfully",
  "data": {
    "id": 125,
    "name": "Jane",
    "surname": "Smith",
    "email": "jane@example.com",
    "role": "user",
    "active": true,
    "created_at": "2023-06-15T14:30:00Z"
  }
}
```

### Error Codes

| Status Code | Error Code           | Description                                    |
|-------------|---------------------|------------------------------------------------|
| 400         | `VALIDATION_ERROR`   | Invalid or missing required fields             |
| 400         | `EMAIL_EXISTS`       | Email address is already in use                |
| 401         | `UNAUTHORIZED`       | User not authenticated                         |
| 403         | `FORBIDDEN`          | User does not have admin privileges            |
| 500         | `USER_CREATION_FAILED`| Failed to create user                        |

### Notes

- Password is automatically hashed before storage
- User creation is logged for audit purposes
- Email verification may be required depending on settings
- Admins can create users with specific roles

---

## Update User Details

Update details for an existing user.

### HTTP Request

`PUT /admin/users/{id}`

### Authentication

Requires a valid admin authentication token.

### Path Parameters

| Parameter | Type    | Required | Description     | Constraints                     |
|-----------|---------|----------|-----------------|--------------------------------|
| `id`      | Integer | Yes      | User identifier | Must be a valid user ID         |

### Request Body Parameters

| Parameter | Type   | Required | Description           | Constraints                    |
|-----------|--------|----------|-----------------------|-------------------------------|
| `name`    | String | No       | User's first name     | Max: 50 characters            |
| `surname` | String | No       | User's last name      | Max: 50 characters            |
| `phone`   | String | No       | User's phone number   | Valid phone number format     |
| `address` | String | No       | User's address        | Max: 200 characters           |
| `metadata`| Object | No       | User metadata         | Valid JSON object             |

### Example Request

```json
{
  "name": "Jane",
  "surname": "Smith-Johnson",
  "phone": "+1987654321",
  "address": "456 Oak St, Newtown, USA"
}
```

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "User updated successfully",
  "data": {
    "id": 125,
    "name": "Jane",
    "surname": "Smith-Johnson",
    "email": "jane@example.com",
    "role": "user",
    "phone": "+1987654321",
    "address": "456 Oak St, Newtown, USA",
    "active": true,
    "updated_at": "2023-06-15T15:22:12Z"
  }
}
```

### Error Codes

| Status Code | Error Code           | Description                                    |
|-------------|---------------------|------------------------------------------------|
| 400         | `VALIDATION_ERROR`   | Invalid field values                           |
| 401         | `UNAUTHORIZED`       | User not authenticated                         |
| 403         | `FORBIDDEN`          | User does not have admin privileges            |
| 404         | `USER_NOT_FOUND`     | User not found                                 |
| 500         | `UPDATE_FAILED`      | Failed to update user                          |

### Notes

- Email and password updates are handled by separate endpoints for security
- User update is logged for audit purposes
- Partial updates are supported - only provide fields that need changes

---

## Toggle User Status

Enable or disable a user account.

### HTTP Request

`POST /admin/users/{id}/status`

### Authentication

Requires a valid admin authentication token.

### Path Parameters

| Parameter | Type    | Required | Description     | Constraints                     |
|-----------|---------|----------|-----------------|--------------------------------|
| `id`      | Integer | Yes      | User identifier | Must be a valid user ID         |

### Request Body Parameters

| Parameter | Type    | Required | Description        | Constraints                     |
|-----------|---------|----------|--------------------|--------------------------------|
| `active`  | Boolean | Yes      | New status value   | true or false                   |

### Example Request

```json
{
  "active": false
}
```

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "User status changed successfully",
  "data": {
    "active": false
  }
}
```

For HTMX requests, returns updated status component HTML.

### Error Codes

| Status Code | Error Code           | Description                                    |
|-------------|---------------------|------------------------------------------------|
| 400         | `MISSING_STATUS`     | Active status not specified                     |
| 401         | `UNAUTHORIZED`       | User not authenticated                         |
| 403         | `FORBIDDEN`          | User does not have admin privileges            |
| 404         | `USER_NOT_FOUND`     | User not found                                 |
| 500         | `STATUS_CHANGE_FAILED`| Failed to change user status                   |

### Notes

- Inactive users cannot log in to the system
- Existing sessions for deactivated users are invalidated
- Status changes are logged for audit purposes
- Cannot deactivate your own admin account

---

## Update User Role

Change a user's role in the system.

### HTTP Request

`POST /admin/users/{id}/role`

### Authentication

Requires a valid admin authentication token.

### Path Parameters

| Parameter | Type    | Required | Description     | Constraints                     |
|-----------|---------|----------|-----------------|--------------------------------|
| `id`      | Integer | Yes      | User identifier | Must be a valid user ID         |

### Request Body Parameters

| Parameter | Type   | Required | Description      | Constraints                     |
|-----------|--------|----------|------------------|--------------------------------|
| `role`    | String | Yes      | New role to assign| Values: user, admin, manager    |

### Example Request

```json
{
  "role": "manager"
}
```

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "User role updated successfully"
}
```

For HTMX requests, returns updated role component HTML.

### Error Codes

| Status Code | Error Code           | Description                                    |
|-------------|---------------------|------------------------------------------------|
| 400         | `INVALID_ROLE`       | Role is invalid                                |
| 401         | `UNAUTHORIZED`       | User not authenticated                         |
| 403         | `FORBIDDEN`          | User does not have admin privileges            |
| 404         | `USER_NOT_FOUND`     | User not found                                 |
| 500         | `ROLE_UPDATE_FAILED` | Failed to update user role                     |

### Notes

- Role changes may affect user permissions immediately
- Role changes are logged for audit purposes
- Cannot downgrade your own admin role
- Role hierarchy enforcement prevents privilege escalation

---

## Delete User

Soft-delete a user from the system.

### HTTP Request

`DELETE /admin/users/{id}`

### Authentication

Requires a valid admin authentication token.

### Path Parameters

| Parameter | Type    | Required | Description     | Constraints                     |
|-----------|---------|----------|-----------------|--------------------------------|
| `id`      | Integer | Yes      | User identifier | Must be a valid user ID         |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "User deleted successfully"
}
```

### Error Codes

| Status Code | Error Code           | Description                                    |
|-------------|---------------------|------------------------------------------------|
| 401         | `UNAUTHORIZED`       | User not authenticated                         |
| 403         | `FORBIDDEN`          | User does not have admin privileges            |
| 403         | `CANNOT_DELETE_SELF` | Cannot delete your own account                 |
| 404         | `USER_NOT_FOUND`     | User not found                                 |
| 500         | `DELETE_FAILED`      | Failed to delete user                          |

### Notes

- This is a soft delete - user data remains in the database but is marked as deleted
- Deleted users cannot log in or use the system
- User delete is logged for audit purposes
- Associated data may be anonymized but preserved for integrity
- Cannot delete your own admin account

---

## Create Admin User

Create a new user with admin privileges.

### HTTP Request

`POST /admin/users/admin`

### Authentication

Requires a valid admin authentication token.

### Request Body Parameters

| Parameter | Type   | Required | Description           | Constraints                    |
|-----------|--------|----------|-----------------------|-------------------------------|
| `name`    | String | Yes      | Admin's first name    | Max: 50 characters            |
| `email`   | String | Yes      | Admin's email address | Valid email format            |
| `password`| String | Yes      | Admin's password      | Min: 8 characters             |
| `role`    | String | No       | Admin role level      | Default: "admin"              |

### Example Request

```json
{
  "name": "Robert",
  "email": "robert@example.com",
  "password": "secureAdminPassword123",
  "role": "admin"
}
```

### Response

Status code: `201 Created`

```json
{
  "status": "success",
  "message": "Admin created successfully",
  "data": {
    "id": 126,
    "name": "Robert",
    "email": "robert@example.com",
    "role": "admin",
    "created_at": "2023-06-15T16:45:22Z"
  }
}
```

### Error Codes

| Status Code | Error Code           | Description                                    |
|-------------|---------------------|------------------------------------------------|
| 400         | `VALIDATION_ERROR`   | Invalid or missing required fields             |
| 400         | `EMAIL_EXISTS`       | Email address is already in use                |
| 401         | `UNAUTHORIZED`       | User not authenticated                         |
| 403         | `FORBIDDEN`          | User does not have sufficient admin privileges |
| 500         | `ADMIN_CREATION_FAILED`| Failed to create admin user                 |

### Notes

- Creating admin users requires elevated permissions
- Admin creation is logged for audit purposes with detailed context
- Enhanced security measures like mandatory 2FA may apply for admin accounts
- Admin users cannot be created through the standard user registration flow
