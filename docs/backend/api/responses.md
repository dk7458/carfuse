# API Response Documentation

## Standard Response Format

All CarFuse API responses follow a consistent JSON structure:

```json
{
  "data": {
    // Resource data or array of resources
  },
  "meta": {
    "requestId": "a1b2c3d4-e5f6-7890-abcd-1234567890ab",
    "timestamp": "2023-11-15T08:30:45Z",
    "pagination": {
      // Pagination details (if applicable)
    }
  }
}
```

## Error Response Structure

Error responses provide detailed information to help diagnose issues:

```json
{
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "The requested vehicle could not be found",
    "details": [
      {
        "field": "vehicleId",
        "issue": "No vehicle exists with ID v-12345"
      }
    ],
    "documentation": "https://carfuse.example.com/docs/errors/RESOURCE_NOT_FOUND"
  },
  "meta": {
    "requestId": "a1b2c3d4-e5f6-7890-abcd-1234567890ab",
    "timestamp": "2023-11-15T08:30:45Z"
  }
}
```

Common error codes:
- `INVALID_REQUEST` - Request syntax or semantics invalid
- `AUTHENTICATION_REQUIRED` - Authentication credentials missing or invalid
- `PERMISSION_DENIED` - User lacks required permissions
- `RESOURCE_NOT_FOUND` - Requested resource does not exist
- `VALIDATION_FAILED` - Request parameters failed validation
- `RATE_LIMIT_EXCEEDED` - User has sent too many requests
- `INTERNAL_ERROR` - Server encountered an unexpected error
- `SERVICE_UNAVAILABLE` - Service temporarily unavailable

## Pagination Pattern

List endpoints return paginated results using cursor-based pagination:

```json
{
  "data": [
    // Array of resources
  ],
  "meta": {
    "pagination": {
      "pageSize": 25,
      "totalCount": 1358,
      "nextCursor": "WyIyMDIzLTExLTE1VDEwOjMwOjAwWiIsIDEwMDJd",
      "previousCursor": null,
      "hasNextPage": true,
      "hasPreviousPage": false
    },
    "requestId": "a1b2c3d4-e5f6-7890-abcd-1234567890ab",
    "timestamp": "2023-11-15T08:30:45Z"
  }
}
```

Pagination query parameters:
- `pageSize` - Number of items per page (default 25, max 100)
- `cursor` - Position in the dataset for pagination
- `sort` - Field to sort by (prefix with `-` for descending order)

## Data Envelope Format

### Single Resource

```json
{
  "data": {
    "id": "v-12345",
    "type": "vehicle",
    "attributes": {
      "make": "Toyota",
      "model": "Camry",
      "year": 2022,
      "vin": "1T7HT2B23CX361200",
      "color": "Silver",
      "lastUpdated": "2023-10-15T14:22:30Z"
    },
    "relationships": {
      "owner": {
        "id": "u-789",
        "type": "user"
      },
      "diagnostics": {
        "href": "/api/v1/vehicles/v-12345/diagnostics"
      }
    }
  },
  "meta": {
    "requestId": "a1b2c3d4-e5f6-7890-abcd-1234567890ab",
    "timestamp": "2023-11-15T08:30:45Z"
  }
}
```

### Resource Collection

```json
{
  "data": [
    {
      "id": "v-12345",
      "type": "vehicle",
      "attributes": {
        "make": "Toyota",
        "model": "Camry",
        "year": 2022
      },
      "links": {
        "self": "/api/v1/vehicles/v-12345"
      }
    },
    {
      "id": "v-67890",
      "type": "vehicle",
      "attributes": {
        "make": "Honda",
        "model": "Civic",
        "year": 2023
      },
      "links": {
        "self": "/api/v1/vehicles/v-67890"
      }
    }
  ],
  "meta": {
    "pagination": {
      "pageSize": 25,
      "totalCount": 1358,
      "nextCursor": "WyIyMDIzLTExLTE1VDEwOjMwOjAwWiIsIDEwMDJd",
      "previousCursor": null,
      "hasNextPage": true,
      "hasPreviousPage": false
    },
    "requestId": "a1b2c3d4-e5f6-7890-abcd-1234567890ab",
    "timestamp": "2023-11-15T08:30:45Z"
  }
}
```

## JSON Schema Examples

### Vehicle Resource

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "properties": {
    "id": {
      "type": "string",
      "description": "Unique identifier for the vehicle",
      "pattern": "^v-[a-zA-Z0-9]{5,}$"
    },
    "type": {
      "type": "string",
      "enum": ["vehicle"]
    },
    "attributes": {
      "type": "object",
      "properties": {
        "make": {
          "type": "string"
        },
        "model": {
          "type": "string"
        },
        "year": {
          "type": "integer",
          "minimum": 1900,
          "maximum": 2100
        },
        "vin": {
          "type": "string",
          "pattern": "^[A-HJ-NPR-Z0-9]{17}$"
        },
        "color": {
          "type": "string"
        },
        "lastUpdated": {
          "type": "string",
          "format": "date-time"
        }
      },
      "required": ["make", "model", "year"]
    }
  },
  "required": ["id", "type", "attributes"]
}
```

## Response Headers and Their Meanings

| Header                 | Description                                   | Example                              |
|------------------------|-----------------------------------------------|--------------------------------------|
| `Content-Type`         | Media type of the response                    | `application/json`                   |
| `X-Request-ID`         | Unique identifier for the request             | `a1b2c3d4-e5f6-7890-abcd-1234567890ab` |
| `X-RateLimit-Limit`    | Number of requests allowed in period          | `1000`                               |
| `X-RateLimit-Remaining`| Number of requests remaining in period        | `999`                                |
| `X-RateLimit-Reset`    | Time when rate limit resets (Unix timestamp)  | `1605434365`                         |
| `Cache-Control`        | Caching directives                            | `private, max-age=300`               |
| `ETag`                 | Version identifier for resource               | `"33a64df551425fcc55e4d42a148795d9f25f89d4"` |
| `X-Version`            | API version used for the request              | `v1`                                 |
| `X-Deprecation-Notice` | Warning about deprecated features (if any)    | `The 'color_hex' field is deprecated and will be removed in v2` |
