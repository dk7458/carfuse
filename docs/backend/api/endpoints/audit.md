# Audit API Endpoints

This document describes the audit endpoints available under the `/api/audit` route.

> **Note:** All endpoints require admin access. Unauthorized requests will receive a 403 Forbidden response.

---

## GET /api/audit

**Description:**  
Retrieves audit logs based on optional filter parameters.

**Authorization:** Admin access required

**Query Parameters:**
- `category` (string, optional): Filter by log category.
- `action` (string, optional): Filter by specific action.
- `user_id` (integer, optional): Filter by user ID.
- `booking_id` (integer, optional): Filter by booking ID.
- `start_date` (string, optional): Filter by start date (YYYY-MM-DD).
- `end_date` (string, optional): Filter by end date (YYYY-MM-DD).
- `page` (integer, optional): Page number for pagination. Defaults to 1.
- `per_page` (integer, optional): Items per page. Maximum 100, defaults to 10.
- `log_level` (string, optional): Filter by log level.

**Status Codes:**
- 200 OK: Successful retrieval
- 403 Forbidden: Insufficient permissions
- 500 Internal Server Error: Server error occurred

**Success Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 101,
      "action": "admin_dashboard_viewed",
      "message": "Admin dashboard viewed",
      "details": { "admin_id": 1 },
      "created_at": "2023-10-01 12:34:56"
    },
    // ... more logs ...
  ]
}
```

**Error Response:**
```json
{
  "status": "error",
  "message": "Admin access required"
}
```

---

## POST /api/audit/fetchLogs

**Description:**  
Fetches audit logs with the filters provided in the request body.

**Authorization:** Admin access required

**Payload Parameters:**  
- `category` (string, optional): Filter by log category.
- `action` (string, optional): Filter by specific action.
- `user_id` (integer, optional): Filter by user ID.
- `booking_id` (integer, optional): Filter by booking ID.
- `start_date` (string, optional): Filter by start date (YYYY-MM-DD).
- `end_date` (string, optional): Filter by end date (YYYY-MM-DD).
- `page` (integer, optional): Page number for pagination. Defaults to 1.
- `per_page` (integer, optional): Items per page. Maximum 100, defaults to 10.
- `log_level` (string, optional): Filter by log level.

**Status Codes:**
- 200 OK: Successful retrieval
- 403 Forbidden: Insufficient permissions
- 500 Internal Server Error: Server error occurred

**Success Response:**  
Same structure as the GET endpoint response.

**Error Response:**
```json
{
  "status": "error",
  "message": "Failed to fetch logs"
}
```

---

## GET /api/audit/{id}

**Description:**  
Retrieves detailed information for a single audit log entry identified by its ID.

**Authorization:** Admin access required

**URL Parameter:**
- `id` (integer): The unique identifier of the audit log entry.

**Status Codes:**
- 200 OK: Successful retrieval
- 403 Forbidden: Insufficient permissions
- 404 Not Found: Log not found
- 500 Internal Server Error: Server error occurred

**Success Response:**
```json
{
  "status": "success",
  "data": {
    "log": {
      "id": 101,
      "action": "admin_dashboard_viewed",
      "message": "Admin dashboard viewed",
      "details": { "admin_id": 1 },
      "created_at": "2023-10-01 12:34:56"
    }
  }
}
```

**Error Response:**
```json
{
  "status": "error",
  "message": "Log not found"
}
```

---

## POST /api/audit/exportLogs

**Description:**  
Exports audit logs matching the provided filters. Returns information about the generated export file.

**Authorization:** Admin access required

**Payload Parameters:**  
- `category` (string, optional): Filter by log category.
- `action` (string, optional): Filter by specific action.
- `user_id` (integer, optional): Filter by user ID.
- `booking_id` (integer, optional): Filter by booking ID.
- `start_date` (string, optional): Filter by start date (YYYY-MM-DD).
- `end_date` (string, optional): Filter by end date (YYYY-MM-DD).
- `log_level` (string, optional): Filter by log level.

**Status Codes:**
- 200 OK: Successful export
- 403 Forbidden: Insufficient permissions
- 500 Internal Server Error: Server error occurred

**Success Response:**
```json
{
  "status": "success",
  "data": {
    "export": {
      "file_path": "/tmp/secure_exports/audit_logs_export_20231001_123456_abcd1234.csv",
      "file_name": "audit_logs_export_20231001_123456_abcd1234.csv",
      "export_id": "20231001_123456_abcd1234",
      "row_count": 50,
      "expiry_time": 1700000000,
      "expiry_formatted": "2023-10-15 00:00:00"
    }
  }
}
```

**Error Response:**
```json
{
  "status": "error",
  "message": "Failed to export logs"
}
```
