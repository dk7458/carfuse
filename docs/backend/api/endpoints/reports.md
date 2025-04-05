# Reports API

This document outlines the available report endpoints for generating and accessing system reports.

## Endpoints Overview

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | /admin/reports | Admin | Report dashboard view for administrators |
| POST | /admin/reports/generate | Admin | Generate reports for administrators |
| GET | /reports/user | User | User report dashboard view |
| POST | /reports/user/generate | User/Admin | Generate user-specific reports |
| GET | /reports/download/{filePath} | User/Admin | Download a generated report |

## Admin Reports

### GET /admin/reports
Displays the admin report dashboard interface.

**Authentication Required**: Admin role
**Response**: HTML dashboard view

**Example Response**:
```json
{
  "status": "success",
  "message": "Report dashboard loaded",
  "data": {
    "view": "admin/reports"
  }
}
```

**Common Errors**:
- 401 Unauthorized - If user is not authenticated or lacks admin privileges

### POST /admin/reports/generate
Generate a report based on specified parameters.

**Authentication Required**: Admin role

**Required Parameters**:
- `date_range` (object): Date range for report data
  - `start` (string): Start date in YYYY-MM-DD format
  - `end` (string): End date in YYYY-MM-DD format
- `format` (string): Report format (e.g., 'pdf', 'csv', 'xlsx')
- `report_type` (string): Type of report to generate

**Optional Parameters**:
- `filters` (object): Additional filters specific to report type

**Example Request**:
```json
{
  "date_range": {
    "start": "2023-01-01",
    "end": "2023-12-31"
  },
  "format": "pdf",
  "report_type": "sales",
  "filters": {
    "product_category": "vehicles",
    "min_amount": 1000
  }
}
```

**Response**: Binary file download

**Common Errors**:
- 400 Bad Request - Missing required parameters
- 401 Unauthorized - If user is not authenticated or lacks admin privileges
- 500 Internal Server Error - Report generation failed

## User Reports

### GET /reports/user
Displays the user report dashboard interface.

**Authentication Required**: User account

**Response**: HTML dashboard view

**Example Response**:
```json
{
  "status": "success",
  "message": "User report dashboard loaded",
  "data": {
    "view": "user/reports"
  }
}
```

### POST /reports/user/generate
Generate a user-specific report.

**Authentication Required**: User account (for own reports) or Admin (for any user)

**Required Parameters**:
- `user_id` (integer): ID of user for whom to generate report
- `date_range` (object): Date range for report data
  - `start` (string): Start date in YYYY-MM-DD format
  - `end` (string): End date in YYYY-MM-DD format
- `format` (string): Report format (e.g., 'pdf', 'csv', 'xlsx')
- `report_type` (string): Type of report to generate

**Example Request**:
```json
{
  "user_id": 123,
  "date_range": {
    "start": "2023-01-01",
    "end": "2023-12-31"
  },
  "format": "pdf",
  "report_type": "activity"
}
```

**Response**: Binary file download

**Common Errors**:
- 400 Bad Request - Missing required parameters
- 401 Unauthorized - If user is not authenticated
- 403 Forbidden - If trying to access another user's data without admin privileges
- 500 Internal Server Error - Report generation failed

## Report Download

### GET /reports/download/{filePath}
Download a previously generated report file.

**Authentication Required**: User account or Admin role

**Path Parameters**:
- `filePath` (string): Path to the generated report file

**Response**: Binary file download with appropriate headers:
- Content-Type: application/octet-stream
- Content-Disposition: attachment; filename=reportname.ext

**Common Errors**:
- 404 Not Found - If report file does not exist
- 401 Unauthorized - If user is not authenticated
- 403 Forbidden - If trying to access another user's report without admin privileges

## Available Report Types

The system supports the following report types:

1. **Sales Reports** (`sales`)
   - Revenue metrics
   - Transaction summaries
   - Payment method distribution

2. **User Activity Reports** (`activity`)
   - Login frequency
   - Feature usage
   - Engagement metrics

3. **Booking Reports** (`bookings`)
   - Reservation statistics
   - Cancellation rates
   - Seasonal trends

4. **System Performance Reports** (`performance`)
   - Response times
   - Error rates
   - API usage statistics

## Report Formats

Reports can be generated in the following formats:

- PDF (`pdf`): Formatted document suitable for printing
- CSV (`csv`): Comma-separated values for data analysis
- Excel (`xlsx`): Microsoft Excel format for advanced analysis
- JSON (`json`): Machine-readable format for API integrations
