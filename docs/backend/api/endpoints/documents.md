# Documents API Endpoints

## Overview

The Documents API provides endpoints for managing document templates, generating contracts, invoices, and terms & conditions. These endpoints enable secure creation, management, and retrieval of important legal and financial documents.

## Authentication and Permissions

| Endpoint Pattern                     | Required Role | Notes                                      |
|-------------------------------------|---------------|-------------------------------------------|
| `POST /documents/templates`          | Admin         | Upload document template                   |
| `POST /documents/contracts/{bookingId}/{userId}` | User | Generate contract for booking         |
| `POST /documents/terms`             | Admin         | Upload terms & conditions                  |
| `POST /documents/invoices/{bookingId}` | User       | Generate invoice for booking               |
| `DELETE /documents/{id}`            | Admin         | Delete a document                          |
| `GET /documents/templates`          | Admin         | Get all templates                          |
| `GET /documents/templates/{id}`     | Admin         | Get specific template                      |

## Rate Limiting

Document endpoints have the following rate limits:
- Standard tier: 20 requests per minute
- Premium tier: 50 requests per minute
- Template operations (admin): 100 requests per hour
- Document generation: 30 requests per hour per user

---

## Upload Document Template

Upload a new document template.

### HTTP Request

`POST /documents/templates`

### Authentication

Requires admin privileges.

### Request Body Parameters

| Parameter  | Type   | Required | Description                | Constraints                     |
|-----------|--------|----------|----------------------------|--------------------------------|
| `name`    | String | Yes      | Template name              | Max: 255 characters              |
| `content` | String | Yes      | Template content           | HTML/Markdown with placeholders  |
| `description` | String | No   | Template description       | Max: 1000 characters            |

### Example Request

```json
{
  "name": "booking_contract",
  "content": "<h1>Vehicle Rental Agreement</h1><p>This agreement is between {{company_name}} and {{customer_name}}...</p>",
  "description": "Standard vehicle rental agreement template"
}
```

### Response

Status code: `201 Created`

```json
{
  "status": "success",
  "message": "Template uploaded successfully",
  "data": {
    "template_id": 123,
    "name": "booking_contract",
    "created_at": "2023-06-15T14:30:00Z"
  }
}
```

### Error Codes

| Status Code | Error Code           | Description                                      |
|-------------|---------------------|--------------------------------------------------|
| 400         | `VALIDATION_ERROR`   | Missing required fields or validation failed     |
| 401         | `UNAUTHORIZED`       | User not authenticated                           |
| 403         | `FORBIDDEN`          | User does not have admin privileges              |
| 409         | `TEMPLATE_EXISTS`    | Template with same name already exists           |
| 500         | `UPLOAD_FAILED`      | Failed to upload template                        |

### Notes

- Templates can contain placeholders using {{variable}} syntax
- Templates are validated for proper HTML/markdown structure
- System templates (default) cannot be overwritten

---

## Generate Contract

Generate a contract for a specific booking.

### HTTP Request

`POST /documents/contracts/{bookingId}/{userId}`

### Authentication

Requires a valid user authentication token.

### Path Parameters

| Parameter   | Type    | Required | Description              | Constraints                     |
|-------------|---------|----------|--------------------------|--------------------------------|
| `bookingId` | Integer | Yes      | Booking identifier       | Must be a valid booking ID      |
| `userId`    | Integer | Yes      | User identifier          | Must be a valid user ID         |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Contract generated successfully",
  "data": {
    "contract_path": "/documents/contracts/booking_456_user_123.pdf",
    "contract_id": 789,
    "created_at": "2023-06-15T14:30:00Z"
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `INVALID_BOOKING_ID`     | Invalid booking ID                               |
| 400         | `INVALID_USER_ID`        | Invalid user ID                                  |
| 401         | `UNAUTHORIZED`           | User not authenticated                           |
| 403         | `ACCESS_DENIED`          | User does not have access to this booking        |
| 404         | `BOOKING_NOT_FOUND`      | Booking not found                                |
| 404         | `USER_NOT_FOUND`         | User not found                                   |
| 404         | `TEMPLATE_NOT_FOUND`     | Contract template not found                      |
| 500         | `GENERATION_FAILED`      | Failed to generate contract                      |

### Notes

- User must be the owner of the booking or an admin
- Generated contract includes booking and vehicle details
- Contract is securely stored with limited access
- Contract generation is logged for audit purposes

---

## Upload Terms & Conditions

Upload a new terms & conditions document.

### HTTP Request

`POST /documents/terms`

### Authentication

Requires admin privileges.

### Request Body Parameters

| Parameter  | Type   | Required | Description                | Constraints                     |
|-----------|--------|----------|----------------------------|--------------------------------|
| `content` | String | Yes      | T&C document content       | HTML/Markdown format            |
| `version` | String | Yes      | Version identifier         | Semantic versioning recommended |
| `effective_date` | String | Yes | Date when T&C becomes effective | ISO 8601 date format     |

### Example Request

```json
{
  "content": "<h1>Terms & Conditions</h1><p>These terms and conditions govern your use of the CarFuse vehicle rental platform...</p>",
  "version": "1.2.0",
  "effective_date": "2023-07-01T00:00:00Z"
}
```

### Response

Status code: `201 Created`

```json
{
  "status": "success",
  "message": "T&C document uploaded successfully",
  "data": {
    "terms_id": 123,
    "version": "1.2.0",
    "effective_date": "2023-07-01T00:00:00Z",
    "created_at": "2023-06-15T14:30:00Z"
  }
}
```

### Error Codes

| Status Code | Error Code           | Description                                      |
|-------------|---------------------|--------------------------------------------------|
| 400         | `VALIDATION_ERROR`   | Missing required content                         |
| 401         | `UNAUTHORIZED`       | User not authenticated                           |
| 403         | `FORBIDDEN`          | User does not have admin privileges              |
| 409         | `VERSION_EXISTS`     | Version already exists                           |
| 500         | `UPLOAD_FAILED`      | Failed to upload T&C document                    |

### Notes

- Previous versions are preserved for reference
- Users may need to accept new terms upon login after effective date
- Change logs between versions are automatically generated
- HTML is sanitized before storage to prevent security issues

---

## Generate Invoice

Generate an invoice for a specific booking.

### HTTP Request

`POST /documents/invoices/{bookingId}`

### Authentication

Requires a valid user authentication token.

### Path Parameters

| Parameter   | Type    | Required | Description              | Constraints                     |
|-------------|---------|----------|--------------------------|--------------------------------|
| `bookingId` | Integer | Yes      | Booking identifier       | Must be a valid booking ID      |

### Query Parameters

| Parameter   | Type    | Required | Description                | Constraints                     |
|-------------|---------|----------|----------------------------|--------------------------------|
| `format`    | String  | No       | Output format              | Values: pdf, html; Default: pdf |

### Response

Status code: `200 OK`

If format is PDF (default):
- Content-Type: `application/pdf`
- Content-Disposition: `attachment; filename="invoice_booking_456.pdf"`
- Binary PDF data

If format is HTML:
```json
{
  "status": "success",
  "message": "Invoice generated successfully",
  "data": {
    "html": "<!DOCTYPE html><html><head>...</head><body>...</body></html>",
    "invoice_id": 789,
    "created_at": "2023-06-15T14:30:00Z"
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `INVALID_BOOKING_ID`     | Invalid booking ID                               |
| 400         | `INVALID_FORMAT`         | Invalid format specified                         |
| 401         | `UNAUTHORIZED`           | User not authenticated                           |
| 403         | `ACCESS_DENIED`          | User does not have access to this booking        |
| 404         | `BOOKING_NOT_FOUND`      | Booking not found                                |
| 404         | `PAYMENT_REQUIRED`       | Booking has no associated payment                |
| 500         | `GENERATION_FAILED`      | Failed to generate invoice                       |

### Notes

- User must be the owner of the booking or an admin
- Invoices include legally required fiscal information
- Invoice generation is logged for audit purposes
- Invoices are only available for bookings with completed payments

---

## Delete Document

Delete a document from the system.

### HTTP Request

`DELETE /documents/{id}`

### Authentication

Requires admin privileges.

### Path Parameters

| Parameter | Type    | Required | Description         | Constraints                     |
|-----------|---------|----------|---------------------|--------------------------------|
| `id`      | Integer | Yes      | Document identifier | Must be a valid document ID     |

### Response

Status code: `204 No Content`

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `INVALID_DOCUMENT_ID`    | Invalid document ID                              |
| 401         | `UNAUTHORIZED`           | User not authenticated                           |
| 403         | `FORBIDDEN`              | User does not have admin privileges              |
| 404         | `DOCUMENT_NOT_FOUND`     | Document not found                               |
| 409         | `DOCUMENT_IN_USE`        | Document is in use and cannot be deleted         |
| 500         | `DELETE_FAILED`          | Failed to delete document                        |

### Notes

- System templates and required documents cannot be deleted
- Deletion is logged for audit purposes
- Document deletion may be a soft delete depending on document type

---

## Get All Templates

Retrieve all document templates.

### HTTP Request

`GET /documents/templates`

### Authentication

Requires admin privileges.

### Query Parameters

| Parameter   | Type    | Required | Description                | Constraints                     |
|-------------|---------|----------|----------------------------|--------------------------------|
| `page`      | Integer | No       | Page number                | Default: 1, Min: 1              |
| `per_page`  | Integer | No       | Items per page             | Default: 20, Max: 100           |
| `type`      | String  | No       | Filter by template type    | e.g., contract, invoice, terms  |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "data": {
    "templates": [
      {
        "id": 123,
        "name": "booking_contract",
        "description": "Standard vehicle rental agreement template",
        "type": "contract",
        "created_at": "2023-06-01T14:30:45Z",
        "updated_at": "2023-06-01T14:30:45Z"
      },
      {
        "id": 124,
        "name": "standard_invoice",
        "description": "Standard invoice template",
        "type": "invoice",
        "created_at": "2023-06-02T10:22:18Z",
        "updated_at": "2023-06-02T10:22:18Z"
      }
    ]
  },
  "meta": {
    "current_page": 1,
    "total_pages": 1,
    "total_items": 2,
    "per_page": 20
  }
}
```

### Error Codes

| Status Code | Error Code           | Description                                      |
|-------------|---------------------|--------------------------------------------------|
| 401         | `UNAUTHORIZED`       | User not authenticated                           |
| 403         | `FORBIDDEN`          | User does not have admin privileges              |
| 500         | `SERVER_ERROR`       | Failed to retrieve templates                     |

### Notes

- Templates are sorted by creation date (newest first) by default
- Only metadata is returned, not the full template content

---

## Get Template Details

Retrieve details for a specific template.

### HTTP Request

`GET /documents/templates/{id}`

### Authentication

Requires admin privileges.

### Path Parameters

| Parameter | Type    | Required | Description          | Constraints                     |
|-----------|---------|----------|----------------------|--------------------------------|
| `id`      | Integer | Yes      | Template identifier  | Must be a valid template ID     |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "data": {
    "template": {
      "id": 123,
      "name": "booking_contract",
      "description": "Standard vehicle rental agreement template",
      "type": "contract",
      "content": "<h1>Vehicle Rental Agreement</h1><p>This agreement is between {{company_name}} and {{customer_name}}...</p>",
      "variables": ["company_name", "customer_name", "vehicle_details", "rental_period", "rate", "terms"],
      "created_at": "2023-06-01T14:30:45Z",
      "updated_at": "2023-06-01T14:30:45Z"
    }
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `INVALID_TEMPLATE_ID`    | Invalid template ID                              |
| 401         | `UNAUTHORIZED`           | User not authenticated                           |
| 403         | `FORBIDDEN`              | User does not have admin privileges              |
| 404         | `TEMPLATE_NOT_FOUND`     | Template not found                               |
| 500         | `SERVER_ERROR`           | Failed to retrieve template                      |

### Notes

- Full template content is included in the response
- Available template variables are automatically extracted and listed
- Template content may be large for complex documents
