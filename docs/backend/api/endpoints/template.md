# [Resource Group] API Endpoints

## Overview

Brief description of this endpoint group, its purpose, and the resources it manages.

## Authentication and Permissions

| Endpoint Pattern     | Required Role | Notes                                      |
|----------------------|---------------|-------------------------------------------|
| `GET /resources`     | `viewer`      | List accessible resources                  |
| `GET /resources/:id` | `viewer`      | View individual resource details           |
| `POST /resources`    | `editor`      | Create new resources                       |
| `PUT /resources/:id` | `editor`      | Update existing resources                  |
| `DELETE /resources/:id` | `admin`    | Delete resources (soft delete)             |

## Rate Limiting

This endpoint group follows the standard API rate limits with the following specifics:
- Standard tier: 100 requests per minute
- Premium tier: 500 requests per minute
- Throttling applied individually to high-volume endpoints

---

## List Resources

Retrieves a paginated list of resources with optional filtering.

### HTTP Request

`GET /api/v1/resources`

### Query Parameters

| Parameter  | Type    | Required | Description                                    | Constraints                      |
|------------|---------|----------|------------------------------------------------|----------------------------------|
| `pageSize` | Integer | No       | Number of items per page                       | Default: 25, Max: 100            |
| `cursor`   | String  | No       | Pagination cursor                              | Obtained from previous responses |
| `status`   | String  | No       | Filter by resource status                      | Values: active, inactive, pending|
| `sort`     | String  | No       | Field to sort by                               | Format: fieldName or -fieldName for descending |
| `search`   | String  | No       | Search term for filtering results              | Min: 3 characters                |

### Response

Status code: `200 OK`

```json
{
  "data": [
    {
      "id": "res_12345",
      "type": "resource",
      "attributes": {
        "name": "Example Resource",
        "status": "active",
        "createdAt": "2023-11-15T08:30:45Z",
        "updatedAt": "2023-11-15T10:22:18Z"
      },
      "links": {
        "self": "/api/v1/resources/res_12345"
      }
    },
    // Additional resources...
  ],
  "meta": {
    "pagination": {
      "pageSize": 25,
      "totalCount": 243,
      "nextCursor": "WyIyMDIzLTExLTE1VDEwOjMwOjAwWiIsIDEwMDJd",
      "previousCursor": null,
      "hasNextPage": true,
      "hasPreviousPage": false
    }
  }
}
```

### Error Codes

| Status Code | Error Code           | Description                                                 |
|-------------|-----------------------|-------------------------------------------------------------|
| 400         | `INVALID_PARAMETER`   | One or more query parameters have invalid values            |
| 401         | `AUTHENTICATION_REQUIRED` | Authentication is required to access this endpoint      |
| 403         | `PERMISSION_DENIED`   | User does not have permission to list resources             |

---

## Get Resource Details

Retrieves detailed information about a specific resource.

### HTTP Request

`GET /api/v1/resources/:id`

### Path Parameters

| Parameter | Type   | Required | Description              | Constraints                   |
|-----------|--------|----------|--------------------------|-------------------------------|
| `id`      | String | Yes      | Unique resource ID       | Format: res_[a-zA-Z0-9]{5,}   |

### Response

Status code: `200 OK`

```json
{
  "data": {
    "id": "res_12345",
    "type": "resource",
    "attributes": {
      "name": "Example Resource",
      "description": "A detailed description of the resource",
      "status": "active",
      "category": "primary",
      "tags": ["example", "documentation"],
      "metadata": {
        "version": "1.0",
        "origin": "user_created"
      },
      "createdAt": "2023-11-15T08:30:45Z",
      "updatedAt": "2023-11-15T10:22:18Z"
    },
    "relationships": {
      "owner": {
        "id": "u-789",
        "type": "user"
      },
      "children": {
        "href": "/api/v1/resources/res_12345/children"
      }
    }
  },
  "meta": {
    "requestId": "a1b2c3d4-e5f6-7890-abcd-1234567890ab",
    "timestamp": "2023-11-15T08:30:45Z"
  }
}
```

### Error Codes

| Status Code | Error Code               | Description                                                 |
|-------------|--------------------------|-------------------------------------------------------------|
| 400         | `INVALID_RESOURCE_ID`    | The resource ID format is invalid                           |
| 401         | `AUTHENTICATION_REQUIRED`| Authentication is required to access this endpoint          |
| 403         | `PERMISSION_DENIED`      | User does not have permission to view this resource         |
| 404         | `RESOURCE_NOT_FOUND`     | No resource exists with the specified ID                    |

---

## Create Resource

Creates a new resource with the provided attributes.

### HTTP Request

`POST /api/v1/resources`

### Request Body Parameters

| Parameter     | Type    | Required | Description                          | Constraints                      |
|---------------|---------|----------|--------------------------------------|----------------------------------|
| `name`        | String  | Yes      | Name of the resource                 | Min: 3, Max: 100 characters      |
| `description` | String  | No       | Detailed description of the resource | Max: 1000 characters             |
| `status`      | String  | No       | Initial status of the resource       | Default: "pending"               |
| `category`    | String  | Yes      | Category classification              | Values: primary, secondary, other|
| `tags`        | Array   | No       | Associated tags                      | Max: 10 tags                     |
| `metadata`    | Object  | No       | Additional resource metadata         | Max size: 5KB                    |

### Example Request

```json
{
  "name": "New Resource",
  "description": "This is a newly created resource for demonstration",
  "category": "primary",
  "tags": ["new", "example"],
  "metadata": {
    "source": "api_documentation",
    "priority": "high"
  }
}
```

### Response

Status code: `201 Created`

```json
{
  "data": {
    "id": "res_67890",
    "type": "resource",
    "attributes": {
      "name": "New Resource",
      "description": "This is a newly created resource for demonstration",
      "status": "pending",
      "category": "primary",
      "tags": ["new", "example"],
      "metadata": {
        "source": "api_documentation",
        "priority": "high"
      },
      "createdAt": "2023-11-15T14:22:36Z",
      "updatedAt": "2023-11-15T14:22:36Z"
    },
    "links": {
      "self": "/api/v1/resources/res_67890"
    }
  },
  "meta": {
    "requestId": "b2c3d4e5-f6a7-8901-bcde-2345678901fa",
    "timestamp": "2023-11-15T14:22:36Z"
  }
}
```

### Error Codes

| Status Code | Error Code               | Description                                                 |
|-------------|--------------------------|-------------------------------------------------------------|
| 400         | `INVALID_REQUEST`        | The request body is malformed or invalid                    |
| 400         | `VALIDATION_FAILED`      | One or more fields failed validation                        |
| 401         | `AUTHENTICATION_REQUIRED`| Authentication is required to access this endpoint          |
| 403         | `PERMISSION_DENIED`      | User does not have permission to create resources           |
| 409         | `RESOURCE_ALREADY_EXISTS`| A resource with the same unique identifier already exists   |
| 422         | `UNPROCESSABLE_ENTITY`   | The request is valid but cannot be processed                |

### Notes on Idempotency

- This endpoint supports idempotency using the `Idempotency-Key` header
- Providing the same idempotency key with identical request bodies will not create duplicate resources
- Idempotency keys expire after 24 hours

---

## Update Resource

Updates an existing resource with the provided attributes.

### HTTP Request

`PUT /api/v1/resources/:id`

### Path Parameters

| Parameter | Type   | Required | Description              | Constraints                   |
|-----------|--------|----------|--------------------------|-------------------------------|
| `id`      | String | Yes      | Unique resource ID       | Format: res_[a-zA-Z0-9]{5,}   |

### Request Body Parameters

| Parameter     | Type    | Required | Description                          | Constraints                      |
|---------------|---------|----------|--------------------------------------|----------------------------------|
| `name`        | String  | No       | Name of the resource                 | Min: 3, Max: 100 characters      |
| `description` | String  | No       | Detailed description of the resource | Max: 1000 characters             |
| `status`      | String  | No       | Status of the resource               | Values: active, inactive, pending|
| `category`    | String  | No       | Category classification              | Values: primary, secondary, other|
| `tags`        | Array   | No       | Associated tags                      | Max: 10 tags                     |
| `metadata`    | Object  | No       | Additional resource metadata         | Max size: 5KB                    |

### Example Request

```json
{
  "name": "Updated Resource Name",
  "status": "active",
  "tags": ["updated", "example", "documentation"]
}
```

### Response

Status code: `200 OK`

```json
{
  "data": {
    "id": "res_12345",
    "type": "resource",
    "attributes": {
      "name": "Updated Resource Name",
      "description": "A detailed description of the resource",
      "status": "active",
      "category": "primary",
      "tags": ["updated", "example", "documentation"],
      "metadata": {
        "version": "1.0",
        "origin": "user_created"
      },
      "createdAt": "2023-11-15T08:30:45Z",
      "updatedAt": "2023-11-15T15:10:22Z"
    },
    "links": {
      "self": "/api/v1/resources/res_12345"
    }
  },
  "meta": {
    "requestId": "c3d4e5f6-a7b8-9012-cdef-34567890abcd",
    "timestamp": "2023-11-15T15:10:22Z"
  }
}
```

### Error Codes

| Status Code | Error Code               | Description                                                 |
|-------------|--------------------------|-------------------------------------------------------------|
| 400         | `INVALID_REQUEST`        | The request body is malformed or invalid                    |
| 400         | `INVALID_RESOURCE_ID`    | The resource ID format is invalid                           |
| 400         | `VALIDATION_FAILED`      | One or more fields failed validation                        |
| 401         | `AUTHENTICATION_REQUIRED`| Authentication is required to access this endpoint          |
| 403         | `PERMISSION_DENIED`      | User does not have permission to update this resource       |
| 404         | `RESOURCE_NOT_FOUND`     | No resource exists with the specified ID                    |
| 409         | `RESOURCE_CONFLICT`      | The update conflicts with the current resource state        |
| 422         | `UNPROCESSABLE_ENTITY`   | The request is valid but cannot be processed                |

### Notes on Idempotency

- This operation is naturally idempotent - calling it multiple times with the same parameters will not cause additional side effects beyond the first successful call
- Optimistic concurrency control is available using the `If-Match` header with the resource's ETag

---

## Delete Resource

Deletes an existing resource. This is a soft delete operation.

### HTTP Request

`DELETE /api/v1/resources/:id`

### Path Parameters

| Parameter | Type   | Required | Description              | Constraints                   |
|-----------|--------|----------|--------------------------|-------------------------------|
| `id`      | String | Yes      | Unique resource ID       | Format: res_[a-zA-Z0-9]{5,}   |

### Query Parameters

| Parameter     | Type    | Required | Description                          | Constraints                      |
|---------------|---------|----------|--------------------------------------|----------------------------------|
| `permanent`   | Boolean | No       | Whether to perform a permanent delete | Default: false                   |

### Response

Status code: `204 No Content`

No response body is returned for successful deletion operations.

### Error Codes

| Status Code | Error Code               | Description                                                 |
|-------------|--------------------------|-------------------------------------------------------------|
| 400         | `INVALID_RESOURCE_ID`    | The resource ID format is invalid                           |
| 401         | `AUTHENTICATION_REQUIRED`| Authentication is required to access this endpoint          |
| 403         | `PERMISSION_DENIED`      | User does not have permission to delete this resource       |
| 404         | `RESOURCE_NOT_FOUND`     | No resource exists with the specified ID                    |
| 409         | `RESOURCE_IN_USE`        | The resource cannot be deleted because it is in use         |

### Notes on Side Effects

- Soft deletion marks the resource as deleted but retains the data
- Associated child resources are not automatically deleted
- Permanently deleted resources cannot be recovered
- Only administrators can perform permanent deletions
