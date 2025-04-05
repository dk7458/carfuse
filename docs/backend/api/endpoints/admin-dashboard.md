# Admin Dashboard API Endpoints

## Overview

The Admin Dashboard API provides statistical data, metrics, and operational insights for administrators to monitor and manage the CarFuse platform. These endpoints deliver real-time and historical data for business intelligence and decision making.

## Authentication and Permissions

| Endpoint Pattern                | Required Role | Notes                                      |
|--------------------------------|---------------|-------------------------------------------|
| `GET /admin/dashboard`          | Admin         | Admin dashboard main view (HTML)           |
| `GET /admin/dashboard/data`     | Admin         | Get admin dashboard statistics via API     |
| `GET /admin/dashboard/bookings` | Admin         | Get booking statistics                     |
| `GET /admin/dashboard/users`    | Admin         | Get user statistics                        |
| `GET /admin/dashboard/revenue`  | Admin         | Get revenue statistics                     |
| `GET /admin/dashboard/metrics`  | Admin         | Get combined platform metrics              |

## Rate Limiting

Dashboard endpoints have the following rate limits:
- Standard admin tier: 120 requests per minute
- Dashboard data refresh: maximum once per 30 seconds

---

## Admin Dashboard Main View

Get the main admin dashboard HTML view.

### HTTP Request

`GET /admin/dashboard`

### Authentication

Requires a valid admin authentication token.

### Response

Status code: `200 OK`

Returns HTML content for the admin dashboard interface.

### Error Codes

| Status Code | Error Code         | Description                                    |
|-------------|-------------------|------------------------------------------------|
| 401         | `UNAUTHORIZED`     | User not authenticated                         |
| 403         | `FORBIDDEN`        | User does not have admin privileges            |
| 500         | `SERVER_ERROR`     | Failed to render dashboard                     |

### Notes

- This endpoint returns a full HTML page for direct browser rendering
- Dashboard access is logged for audit purposes
- Data in the dashboard is loaded via separate AJAX/HTMX calls

---

## Get Dashboard Data

Retrieve all dashboard metrics and statistics in a single call.

### HTTP Request

`GET /admin/dashboard/data`

### Authentication

Requires a valid admin authentication token.

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Dashboard data fetched",
  "data": {
    "metrics": {
      "total_users": 250,
      "active_users": 185,
      "total_bookings": 450,
      "completed_bookings": 375,
      "canceled_bookings": 25,
      "total_revenue": 12500.75,
      "total_refunds": 450.50,
      "net_revenue": 12050.25
    },
    "trends": {
      "user_growth": 5.2,
      "booking_growth": 7.8,
      "revenue_growth": 4.6
    },
    "recent_bookings": [
      {
        "id": 1001,
        "user_id": 123,
        "vehicle_id": 45,
        "status": "completed",
        "amount": 249.99,
        "created_at": "2023-05-15T14:30:22Z"
      },
      {
        "id": 1002,
        "user_id": 124,
        "vehicle_id": 46,
        "status": "pending",
        "amount": 349.99,
        "created_at": "2023-05-16T10:22:18Z"
      }
    ],
    "recent_users": [
      {
        "id": 150,
        "name": "Alice Johnson",
        "email": "alice@example.com",
        "created_at": "2023-05-15T09:20:15Z"
      },
      {
        "id": 151,
        "name": "Bob Wilson",
        "email": "bob@example.com",
        "created_at": "2023-05-15T14:45:30Z"
      }
    ]
  }
}
```

### Error Codes

| Status Code | Error Code           | Description                                      |
|-------------|---------------------|--------------------------------------------------|
| 401         | `UNAUTHORIZED`       | User not authenticated                           |
| 403         | `FORBIDDEN`          | User does not have admin privileges              |
| 500         | `SERVER_ERROR`       | Failed to retrieve dashboard data                |

### Notes

- Provides comprehensive system metrics in a single API call
- Data is cached for 30 seconds to reduce database load
- Includes summary data for recent platform activity
- Response size can be large depending on system activity
- For more detailed or specific metrics, use specialized endpoints

---

## Get Booking Statistics

Retrieve detailed booking statistics and trends.

### HTTP Request

`GET /admin/dashboard/bookings`

### Authentication

Requires a valid admin authentication token.

### Query Parameters

| Parameter       | Type    | Required | Description                          | Constraints                      |
|----------------|---------|----------|--------------------------------------|---------------------------------|
| `period`       | String  | No       | Time period for statistics            | Values: day, week, month, year; Default: month |
| `start_date`   | String  | No       | Start date for custom period          | ISO 8601 format                  |
| `end_date`     | String  | No       | End date for custom period            | ISO 8601 format                  |
| `group_by`     | String  | No       | How to group the statistics           | Values: day, week, month; Default: day |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Booking statistics fetched",
  "data": {
    "summary": {
      "total_bookings": 450,
      "pending_bookings": 50,
      "confirmed_bookings": 375,
      "canceled_bookings": 25,
      "average_duration": 3.5,
      "average_amount": 275.50
    },
    "trends": {
      "booking_growth": 7.8,
      "completion_rate": 83.3,
      "cancellation_rate": 5.6
    },
    "timeline": [
      {
        "period": "2023-05-01",
        "new_bookings": 15,
        "completed_bookings": 12,
        "canceled_bookings": 2,
        "revenue": 3299.97
      },
      {
        "period": "2023-05-02",
        "new_bookings": 18,
        "completed_bookings": 14,
        "canceled_bookings": 1,
        "revenue": 3499.86
      }
      // Additional timeline entries...
    ],
    "top_vehicles": [
      {
        "vehicle_id": 45,
        "make": "Toyota",
        "model": "Camry",
        "bookings_count": 28,
        "revenue": 6999.72
      },
      {
        "vehicle_id": 52,
        "make": "Honda",
        "model": "Civic",
        "bookings_count": 24,
        "revenue": 5279.76
      }
      // Additional top vehicles...
    ]
  }
}
```

### Error Codes

| Status Code | Error Code              | Description                                      |
|-------------|------------------------|--------------------------------------------------|
| 400         | `INVALID_PARAMETERS`    | Invalid date range or parameters                 |
| 401         | `UNAUTHORIZED`          | User not authenticated                           |
| 403         | `FORBIDDEN`             | User does not have admin privileges              |
| 500         | `SERVER_ERROR`          | Failed to retrieve booking statistics            |

### Notes

- Statistics accuracy depends on the completeness of historical data
- For large date ranges, data may be aggregated at a higher level
- Custom date ranges limited to maximum of 366 days
- Booking amounts include any refunds processed
- Timeline data is paginated based on selected period and grouping

---

## Get User Statistics

Retrieve detailed user statistics and trends.

### HTTP Request

`GET /admin/dashboard/users`

### Authentication

Requires a valid admin authentication token.

### Query Parameters

| Parameter       | Type    | Required | Description                          | Constraints                      |
|----------------|---------|----------|--------------------------------------|---------------------------------|
| `period`       | String  | No       | Time period for statistics            | Values: day, week, month, year; Default: month |
| `start_date`   | String  | No       | Start date for custom period          | ISO 8601 format                  |
| `end_date`     | String  | No       | End date for custom period            | ISO 8601 format                  |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "User statistics fetched",
  "data": {
    "summary": {
      "total_users": 250,
      "active_users": 185,
      "inactive_users": 65,
      "new_users_current_period": 28,
      "new_users_previous_period": 22
    },
    "trends": {
      "user_growth": 5.2,
      "activity_rate": 74.0,
      "retention_rate": 92.5
    },
    "timeline": [
      {
        "period": "2023-05-01",
        "new_users": 3,
        "active_users": 178
      },
      {
        "period": "2023-05-02",
        "new_users": 5,
        "active_users": 180
      }
      // Additional timeline entries...
    ],
    "demographics": {
      "top_locations": [
        {"name": "New York", "count": 45},
        {"name": "Los Angeles", "count": 32},
        {"name": "Chicago", "count": 28}
      ],
      "device_breakdown": {
        "mobile": 65,
        "desktop": 30,
        "tablet": 5
      },
      "registration_sources": {
        "direct": 40,
        "google": 25,
        "facebook": 15,
        "referral": 10,
        "other": 10
      }
    }
  }
}
```

### Error Codes

| Status Code | Error Code              | Description                                      |
|-------------|------------------------|--------------------------------------------------|
| 400         | `INVALID_PARAMETERS`    | Invalid date range or parameters                 |
| 401         | `UNAUTHORIZED`          | User not authenticated                           |
| 403         | `FORBIDDEN`             | User does not have admin privileges              |
| 500         | `SERVER_ERROR`          | Failed to retrieve user statistics               |

### Notes

- Active users defined as users who logged in during the selected period
- Demographics data is anonymized and aggregated
- Location data is based on user profile information or IP geolocation
- Device breakdown based on last login device
- User retention calculated based on returning users in the period

---

## Get Revenue Statistics

Retrieve detailed revenue statistics and financial trends.

### HTTP Request

`GET /admin/dashboard/revenue`

### Authentication

Requires a valid admin authentication token.

### Query Parameters

| Parameter       | Type    | Required | Description                          | Constraints                      |
|----------------|---------|----------|--------------------------------------|---------------------------------|
| `period`       | String  | No       | Time period for statistics            | Values: day, week, month, year; Default: month |
| `start_date`   | String  | No       | Start date for custom period          | ISO 8601 format                  |
| `end_date`     | String  | No       | End date for custom period            | ISO 8601 format                  |
| `currency`     | String  | No       | Currency for amounts                  | Default: USD                     |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Revenue statistics fetched",
  "data": {
    "summary": {
      "total_revenue": 12500.75,
      "total_refunds": 450.50,
      "net_revenue": 12050.25,
      "average_booking_value": 275.50,
      "projected_monthly_revenue": 14800.00
    },
    "trends": {
      "revenue_growth": 4.6,
      "refund_rate": 3.6,
      "average_value_change": 2.1
    },
    "timeline": [
      {
        "period": "2023-05-01",
        "gross_revenue": 599.97,
        "refunds": 49.99,
        "net_revenue": 549.98,
        "bookings": 2
      },
      {
        "period": "2023-05-02",
        "gross_revenue": 749.98,
        "refunds": 0,
        "net_revenue": 749.98,
        "bookings": 3
      }
      // Additional timeline entries...
    ],
    "payment_methods": {
      "credit_card": {
        "count": 350,
        "amount": 9625.50
      },
      "paypal": {
        "count": 75,
        "amount": 1875.25
      },
      "bank_transfer": {
        "count": 25,
        "amount": 1000.00
      }
    },
    "top_revenue_vehicles": [
      {
        "vehicle_id": 45,
        "make": "Toyota",
        "model": "Camry",
        "revenue": 6999.72,
        "bookings": 28
      },
      {
        "vehicle_id": 52,
        "make": "Honda",
        "model": "Civic",
        "revenue": 5279.76,
        "bookings": 24
      }
    ]
  }
}
```

### Error Codes

| Status Code | Error Code              | Description                                      |
|-------------|------------------------|--------------------------------------------------|
| 400         | `INVALID_PARAMETERS`    | Invalid date range or parameters                 |
| 401         | `UNAUTHORIZED`          | User not authenticated                           |
| 403         | `FORBIDDEN`             | User does not have admin privileges              |
| 500         | `SERVER_ERROR`          | Failed to retrieve revenue statistics            |

### Notes

- All monetary values are provided in the requested currency
- Projected revenue based on current period trends
- Revenue calculations include all completed bookings
- Refunds are tracked separately for accurate financial reporting
- Payment method breakdown shows transaction count and total amounts
- Financial data access is restricted to admin users with financial permissions

---

## Get Platform Metrics

Retrieve combined platform metrics including activity, performance, and system health.

### HTTP Request

`GET /admin/dashboard/metrics`

### Authentication

Requires a valid admin authentication token.

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Platform metrics fetched",
  "data": {
    "system_health": {
      "api_response_time": 235,
      "database_query_time": 85,
      "server_load": 0.45,
      "memory_usage": 68.5,
      "storage_usage": 42.3
    },
    "activity": {
      "active_users_now": 32,
      "logins_today": 175,
      "api_requests_today": 12450,
      "page_views_today": 3250
    },
    "performance": {
      "average_page_load": 1.2,
      "average_api_response": 0.25,
      "error_rate": 0.02
    },
    "fleet_status": {
      "total_vehicles": 85,
      "available_vehicles": 42,
      "booked_vehicles": 38,
      "maintenance_vehicles": 5
    },
    "business_metrics": {
      "conversion_rate": 8.5,
      "average_session_duration": 145,
      "booking_abandonment_rate": 12.3
    }
  }
}
```

### Error Codes

| Status Code | Error Code           | Description                                      |
|-------------|---------------------|--------------------------------------------------|
| 401         | `UNAUTHORIZED`       | User not authenticated                           |
| 403         | `FORBIDDEN`          | User does not have admin privileges              |
| 500         | `SERVER_ERROR`       | Failed to retrieve platform metrics              |

### Notes

- Response times are in milliseconds
- Server load is normalized between 0 and 1
- Memory and storage usage are percentages
- These metrics are real-time snapshots of system performance
- Fleet status provides an overview of current vehicle availability
- Business metrics help track platform effectiveness
- Data may be cached for up to 1 minute to reduce system load
