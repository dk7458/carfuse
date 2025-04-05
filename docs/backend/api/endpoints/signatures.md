# Signatures API Endpoints

## Overview

The Signatures API enables secure electronic signature management for documents and contracts. These endpoints handle signature upload, verification, and retrieval for legal documents within the CarFuse platform.

## Authentication and Permissions

| Endpoint Pattern                | Required Role | Notes                                      |
|--------------------------------|---------------|-------------------------------------------|
| `POST /signatures/upload`       | User          | Upload a signature                         |
| `POST /signatures/verify`       | User          | Verify signature against document hash     |
| `GET /signatures/user/{userId}` | User          | Retrieve a user's signature                |
| `POST /signatures/document`     | User          | Sign a specific document                   |
| `GET /signatures/status/{documentId}` | User    | Check signature status for a document      |

## Rate Limiting

Signature endpoints have the following rate limits:
- Standard tier: 20 requests per minute
- Premium tier: 50 requests per minute
- Signature upload: 10 requests per hour per user
- Verification: 60 requests per hour per user

---

## Upload Signature

Upload a new signature for the authenticated user.

### HTTP Request

`POST /signatures/upload`

### Authentication

Requires a valid user authentication token.

### Request Body Parameters

This endpoint expects a multipart/form-data request with the following parameters:

| Parameter | Type    | Required | Description                  | Constraints                       |
|-----------|---------|----------|------------------------------|----------------------------------|
| `user_id` | Integer | Yes      | User ID                      | Must match authenticated user ID  |
| `file`    | File    | Yes      | Signature image or data file | PNG, JPG, JPEG; Max size: 2MB     |
| `type`    | String  | No       | Signature type               | Values: image, vector, drawn; Default: image |

### Response

Status code: `200 OK`

```json
{
  "status": "success", 
  "message": "Signature uploaded successfully", 
  "data": {
    "signature_id": 123,
    "path": "/uploads/signatures/user_123_timestamp.png",
    "created_at": "2023-06-15T14:30:00Z"
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `INVALID_FILE_FORMAT`    | File format not supported                        |
| 400         | `FILE_TOO_LARGE`         | File exceeds maximum size limit                  |
| 400         | `USER_MISMATCH`          | User ID does not match authenticated user        |
| 401         | `UNAUTHORIZED`           | User not authenticated                           |
| 500         | `UPLOAD_FAILED`          | Failed to upload signature                       |

### Notes

- Uploaded signatures are processed to ensure consistent formatting
- Previous signatures for the user are preserved but marked as inactive
- Signature upload is logged for audit and legal purposes
- Transparent background is automatically applied to image signatures

---

## Verify Signature

Verify the authenticity of a signature against a document hash.

### HTTP Request

`POST /signatures/verify`

### Authentication

Requires a valid user authentication token.

### Request Body Parameters

| Parameter        | Type   | Required | Description                  | Constraints                       |
|------------------|--------|----------|------------------------------|----------------------------------|
| `signature_id`   | Integer| Yes      | Signature identifier         | Must be a valid signature ID      |
| `document_hash`  | String | Yes      | Document hash for verification | SHA-256 hash of document content |
| `document_id`    | Integer| Yes      | Document identifier          | Must be a valid document ID       |

### Example Request

```json
{
  "signature_id": 123,
  "document_hash": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
  "document_id": 456
}
```

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "verified": true,
  "data": {
    "signature_info": {
      "id": 123,
      "user_id": 789,
      "created_at": "2023-06-15T14:30:00Z"
    },
    "document_info": {
      "id": 456,
      "name": "Vehicle Rental Agreement",
      "signed_at": "2023-06-15T14:35:22Z"
    }
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `MISSING_PARAMETERS`     | Required parameters are missing                   |
| 400         | `INVALID_HASH_FORMAT`    | Document hash format is invalid                   |
| 401         | `UNAUTHORIZED`           | User not authenticated                           |
| 403         | `PERMISSION_DENIED`      | User does not have permission for this signature |
| 404         | `SIGNATURE_NOT_FOUND`    | Signature not found                              |
| 404         | `DOCUMENT_NOT_FOUND`     | Document not found                               |
| 500         | `VERIFICATION_FAILED`    | Failed to verify signature                       |

### Notes

- Successful verification confirms signature was applied to document
- Verification results are logged for audit and compliance purposes
- Digital signatures include timestamp and user identification
- Failed verifications may indicate tampering or document changes

---

## Retrieve User Signature

Retrieve the signature for a specific user.

### HTTP Request

`GET /signatures/user/{userId}`

### Authentication

Requires a valid user authentication token.

### Path Parameters

| Parameter | Type    | Required | Description         | Constraints                     |
|-----------|---------|----------|---------------------|--------------------------------|
| `userId`  | Integer | Yes      | User identifier     | Must be a valid user ID         |

### Query Parameters

| Parameter | Type    | Required | Description                  | Constraints                       |
|-----------|---------|----------|------------------------------|----------------------------------|
| `format`  | String  | No       | Format of signature to return| Values: base64, url; Default: url |

### Response

Status code: `200 OK`

If format is url (default):
```json
{
  "status": "success",
  "data": {
    "signature": {
      "id": 123,
      "user_id": 789,
      "path": "/uploads/signatures/user_789_timestamp.png",
      "type": "image",
      "created_at": "2023-06-15T14:30:00Z"
    }
  }
}
```

If format is base64:
```json
{
  "status": "success",
  "data": {
    "signature": {
      "id": 123,
      "user_id": 789,
      "data": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...",
      "type": "image",
      "created_at": "2023-06-15T14:30:00Z"
    }
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `INVALID_USER_ID`        | User ID is invalid                               |
| 401         | `UNAUTHORIZED`           | User not authenticated                           |
| 403         | `PERMISSION_DENIED`      | User does not have permission to access this signature |
| 404         | `SIGNATURE_NOT_FOUND`    | No signature found for user                      |
| 404         | `USER_NOT_FOUND`         | User not found                                   |
| 500         | `SERVER_ERROR`           | Failed to retrieve signature                     |

### Notes

- Users can only access their own signatures unless they are admins
- Most recent active signature is returned if user has multiple signatures
- Signature retrieval is logged for audit purposes
- Access to signatures is strictly controlled for security and legal reasons

---

## Sign Document

Apply a user's signature to a specific document.

### HTTP Request

`POST /signatures/document`

### Authentication

Requires a valid user authentication token.

### Request Body Parameters

| Parameter        | Type    | Required | Description                  | Constraints                       |
|------------------|---------|----------|------------------------------|----------------------------------|
| `document_id`    | Integer | Yes      | Document identifier          | Must be a valid document ID       |
| `signature_id`   | Integer | Yes      | Signature identifier         | Must be a valid signature ID      |
| `position_x`     | Integer | No       | X-coordinate on document     | Default based on document type    |
| `position_y`     | Integer | No       | Y-coordinate on document     | Default based on document type    |
| `page`           | Integer | No       | Document page to sign        | Default: 1                        |
| `scale`          | Float   | No       | Signature scale factor       | Range: 0.5-2.0; Default: 1.0      |

### Example Request

```json
{
  "document_id": 456,
  "signature_id": 123,
  "page": 3,
  "position_x": 350,
  "position_y": 500,
  "scale": 0.8
}
```

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Document signed successfully",
  "data": {
    "signature_id": 123,
    "document_id": 456,
    "signed_at": "2023-06-15T14:35:22Z",
    "download_url": "/documents/signed/456.pdf"
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `MISSING_PARAMETERS`     | Required parameters are missing                   |
| 400         | `INVALID_POSITION`       | Position coordinates are invalid                  |
| 401         | `UNAUTHORIZED`           | User not authenticated                           |
| 403         | `PERMISSION_DENIED`      | User does not have permission to sign this document |
| 404         | `DOCUMENT_NOT_FOUND`     | Document not found                               |
| 404         | `SIGNATURE_NOT_FOUND`    | Signature not found                              |
| 500         | `SIGNING_FAILED`         | Failed to sign document                          |

### Notes

- Document signing creates a legally binding electronic signature
- Document hash is calculated and stored for verification
- A new signed version of the document is created, preserving the original
- Digital certificate is applied to ensure document integrity
- Document signing is logged with user details and timestamp for legal compliance

---

## Check Signature Status

Check the signature status of a document.

### HTTP Request

`GET /signatures/status/{documentId}`

### Authentication

Requires a valid user authentication token.

### Path Parameters

| Parameter    | Type    | Required | Description         | Constraints                     |
|-------------|---------|----------|---------------------|--------------------------------|
| `documentId`| Integer | Yes      | Document identifier | Must be a valid document ID     |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "data": {
    "document_id": 456,
    "document_name": "Vehicle Rental Agreement",
    "is_signed": true,
    "signatures": [
      {
        "user_id": 789,
        "user_name": "John Doe",
        "signed_at": "2023-06-15T14:35:22Z",
        "signature_id": 123,
        "position": {"page": 3, "x": 350, "y": 500}
      },
      {
        "user_id": 790,
        "user_name": "Jane Smith",
        "signed_at": "2023-06-15T16:42:10Z",
        "signature_id": 124,
        "position": {"page": 3, "x": 350, "y": 600}
      }
    ],
    "pending_signatures": [
      {
        "user_id": 791,
        "user_name": "Robert Johnson",
        "status": "pending"
      }
    ],
    "completed": false
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `INVALID_DOCUMENT_ID`    | Document ID is invalid                           |
| 401         | `UNAUTHORIZED`           | User not authenticated                           |
| 403         | `PERMISSION_DENIED`      | User does not have permission to access this document |
| 404         | `DOCUMENT_NOT_FOUND`     | Document not found                               |
| 500         | `SERVER_ERROR`           | Failed to check signature status                 |

### Notes

- Returns all signatures applied to the document
- Includes list of pending signatures if multi-party signing is required
- Document is considered fully signed only when all required parties have signed
- Status check is read-only and does not modify the document
