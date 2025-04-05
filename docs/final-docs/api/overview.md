# API Overview

*Last updated: 2023-11-15*

This document provides an overview of the CarFuse API architecture, conventions, and usage patterns.

## Table of Contents
- [API Architecture](#api-architecture)
- [API Endpoints](#api-endpoints)
- [Authentication](#authentication)
- [Request Format](#request-format)
- [Response Format](#response-format)
- [Error Handling](#error-handling)
- [Rate Limiting](#rate-limiting)
- [Versioning](#versioning)
- [Related Documentation](#related-documentation)

## API Architecture

The CarFuse API follows a RESTful architecture with these key characteristics:

1. **Resource-Based**: API endpoints are organized around resources
2. **Standard HTTP Methods**: Uses standard HTTP methods (GET, POST, PUT, DELETE)
3. **JSON Responses**: All responses are in JSON format
4. **Stateless**: Each request contains all necessary information
5. **Authentication**: Token-based authentication for secure access

## API Endpoints

The CarFuse API is organized into the following endpoint categories:

| Category | Base Path | Description | Documentation |
|----------|-----------|-------------|---------------|
| Authentication | `/api/auth` | User authentication and session management | [Auth Endpoints](endpoints/auth.md) |
| Users | `/api/users` | User account management | [User Endpoints](endpoints/users.md) |
| Bookings | `/api/bookings` | Vehicle booking management | [Booking Endpoints](endpoints/bookings.md) |
| Vehicles | `/api/vehicles` | Vehicle inventory and details | [Vehicle Endpoints](endpoints/vehicles.md) |
| Payments | `/api/payments` | Payment processing | [Payment Endpoints](endpoints/payments.md) |
| Documents | `/api/documents` | Document management | [Document Endpoints](endpoints/documents.md) |
| Notifications | `/api/notifications` | User notifications | [Notification Endpoints](endpoints/notifications.md) |

## Authentication

The API supports multiple authentication methods:

### Token-based Authentication

Most API requests require a Bearer token:

```
Authorization: Bearer {token}
```

To obtain a token:

1. Send credentials to `/api/auth/login`
2. Store the returned token
3. Include the token in subsequent requests

Example login request:

```javascript
fetch('/api/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        email: 'user@example.com',
        password: 'password'
    })
})
.then(response => response.json())
.then(data => {
    // Store the token
    localStorage.setItem('auth_token', data.token);
});
```

Example authenticated request:

```javascript
fetch('/api/users/profile', {
    headers: {
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
    }
})
.then(response => response.json())
.then(data => {
    // Handle user data
});
```

### Session-based Authentication

For web applications, session-based authentication is also supported. The API will check for an active session cookie in addition to Bearer tokens.

## Request Format

### Query Parameters

For filtering, sorting, and pagination:

```
GET /api/vehicles?status=available&sort=price:asc&page=2&limit=20
```

| Parameter | Description | Example |
|-----------|-------------|---------|
| `filter[field]` | Field-specific filter | `filter[make]=Toyota` |
| `sort` | Field to sort by, with direction | `sort=price:asc` |
| `page` | Page number for pagination | `page=2` |
| `limit` | Items per page | `limit=20` |
| `include` | Related resources to include | `include=features,location` |

### Request Bodies

For POST, PUT, and PATCH requests, send JSON request bodies:

```javascript
fetch('/api/bookings', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
        vehicleId: 123,
        startDate: '2023-12-01',
        endDate: '2023-12-05',
        paymentMethodId: 456
    })
});
```

## Response Format

All API responses follow a consistent format:

### Successful Responses

```json
{
    "success": true,
    "data": {
        "id": 123,
        "attribute1": "value1",
        "attribute2": "value2"
    },
    "meta": {
        "timestamp": "2023-11-15T12:00:00Z"
    }
}
```

For collections:

```json
{
    "success": true,
    "data": [
        { "id": 1, "name": "Item 1" },
        { "id": 2, "name": "Item 2" }
    ],
    "pagination": {
        "total": 50,
        "perPage": 10,
        "currentPage": 1,
        "lastPage": 5
    },
    "meta": {
        "timestamp": "2023-11-15T12:00:00Z"
    }
}
```

### Error Responses

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The provided data was invalid.",
        "details": {
            "email": ["Please provide a valid email address."]
        }
    },
    "meta": {
        "timestamp": "2023-11-15T12:00:00Z"
    }
}
```

## Error Handling

The API uses standard HTTP status codes:

| Status Code | Description | Example |
|-------------|-------------|---------|
| 200 | Success | Successful request |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Missing or invalid authentication |
| 403 | Forbidden | Authenticated but insufficient permissions |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation error |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Server Error | Internal server error |

## Rate Limiting

API requests are subject to rate limiting:

- **Standard Users**: 60 requests per minute
- **Partner API Users**: 300 requests per minute
- **Internal Services**: 1000 requests per minute

Rate limit information is included in response headers:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1605005400
```

## Versioning

The API uses a versioning system to ensure backward compatibility:

```
/api/v1/users
```

Major versions (v1, v2) indicate potentially breaking changes. The current API version is v1.

## Related Documentation

- [Authentication Endpoints](endpoints/auth.md)
- [Data Models](models/user.md)
- [Security Best Practices](../security/best-practices.md)
- [Error Handling Guide](../development/guides/api-error-handling.md)
- [API Testing Guide](../development/guides/api-testing.md)
