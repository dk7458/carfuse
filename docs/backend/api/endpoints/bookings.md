# Bookings API Endpoints

## Overview

The Bookings API allows users to manage vehicle bookings, including creating new bookings, viewing booking details, rescheduling, canceling, and accessing booking history. These endpoints handle all aspects of the vehicle reservation lifecycle.

## Authentication and Permissions

| Endpoint Pattern                | Required Role | Notes                                      |
|--------------------------------|---------------|-------------------------------------------|
| `GET /bookings/{id}`           | User          | User can view their own bookings           |
| `GET /bookings`                | User          | List user's bookings                       |
| `POST /bookings`               | User          | Create a new booking                       |
| `PUT /bookings/{id}/reschedule`| User          | Reschedule an existing booking            |
| `POST /bookings/{id}/cancel`   | User          | Cancel a booking                          |
| `GET /bookings/{id}/logs`      | User          | Get booking activity logs                 |
| `GET /bookings/list`           | User          | HTML format booking list (for HTMX)       |

## Rate Limiting

> **Note**: Rate limiting is planned for future implementation. The specific limits described below are not currently enforced.

---

## View Booking Details

Retrieve details for a specific booking.

### HTTP Request

`GET /bookings/{id}`

### Authentication

Requires a valid user authentication token.

### Path Parameters

| Parameter | Type    | Required | Description         | Constraints                     |
|-----------|---------|----------|---------------------|--------------------------------|
| `id`      | Integer | Yes      | Booking identifier  | Must be a valid booking ID     |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Booking details fetched",
  "data": {
    "booking": {
      "id": 456,
      "user_id": 123,
      "vehicle_id": 789,
      "pickup_date": "2023-07-15T10:00:00Z",
      "dropoff_date": "2023-07-18T10:00:00Z",
      "pickup_location": "New York Downtown",
      "dropoff_location": "New York Downtown",
      "status": "confirmed",
      "total_amount": 349.99,
      "created_at": "2023-06-01T14:30:45Z",
      "updated_at": "2023-06-01T14:30:45Z",
      "vehicle": {
        "make": "Toyota",
        "model": "Camry",
        "year": 2022,
        "type": "sedan",
        "registration_number": "ABC123"
      },
      "payment_status": "paid"
    }
  }
}
```

### Error Responses

| Status Code | Message                      | Description                                |
|-------------|------------------------------|--------------------------------------------|
| 401         | Unauthorized access          | User not authenticated                     |
| 403         | You do not have permission   | User does not have access to this booking  |
| 404         | Booking not found            | The specified booking cannot be found      |
| 500         | Failed to fetch booking details | Server error                           |

### Notes

- Users can only view their own bookings unless they have admin privileges
- Booking views are logged for audit purposes
- Vehicle details are included in the response to reduce additional API calls

---

## List User Bookings

Retrieve a paginated list of bookings for the authenticated user.

### HTTP Request

`GET /bookings`

### Authentication

Requires a valid user authentication token.

### Query Parameters

| Parameter | Type    | Required | Description                      | Constraints                      |
|-----------|---------|----------|----------------------------------|----------------------------------|
| `page`    | Integer | No       | Page number for pagination       | Default: 1, Min: 1               |
| `per_page`| Integer | No       | Number of items per page         | Default: 10                      |
| `status`  | String  | No       | Filter by booking status         | If not provided, returns all statuses |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "User bookings fetched successfully",
  "data": [
    {
      "id": 456,
      "vehicle_id": 789,
      "pickup_date": "2023-07-15T10:00:00Z",
      "dropoff_date": "2023-07-18T10:00:00Z",
      "status": "confirmed",
      "total_amount": 349.99,
      "vehicle": {
        "make": "Toyota",
        "model": "Camry",
        "year": 2022
      },
      "created_at": "2023-06-01T14:30:45Z"
    },
    {
      "id": 457,
      "vehicle_id": 790,
      "pickup_date": "2023-08-05T14:00:00Z",
      "dropoff_date": "2023-08-07T14:00:00Z",
      "status": "pending",
      "total_amount": 249.99,
      "vehicle": {
        "make": "Honda",
        "model": "Accord",
        "year": 2023
      },
      "created_at": "2023-06-05T09:22:18Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total_pages": 3,
    "total": 25,
    "per_page": 10
  }
}
```

### Error Responses

| Status Code | Message                      | Description                                |
|-------------|------------------------------|--------------------------------------------|
| 401         | Unauthorized access          | User not authenticated                     |
| 500         | Failed to fetch user bookings | Server error                             |

### Notes

- Results include pagination metadata for client-side pagination controls
- Limited vehicle details are included for each booking to reduce additional API calls

---

## Create Booking

Create a new vehicle booking.

### HTTP Request

`POST /bookings`

### Authentication

Requires a valid user authentication token.

### Request Body Parameters

| Parameter         | Type    | Required | Description                 | Constraints                        |
|-------------------|---------|----------|-----------------------------|-----------------------------------|
| `vehicle_id`      | Integer | Yes      | Vehicle to book             | Must be a valid vehicle ID        |
| `pickup_date`     | String  | Yes      | Pickup date and time        | ISO 8601 format, future date      |
| `dropoff_date`    | String  | Yes      | Drop-off date and time      | ISO 8601 format, after pickup date|
| `pickup_location` | String  | Yes      | Location for pickup         | Non-empty string                  |
| `dropoff_location`| String  | Yes      | Location for drop-off       | Non-empty string                  |
| `payment_method_id`| Integer| Yes      | Payment method to use       | Must be a valid payment method ID |

### Example Request

```json
{
  "vehicle_id": 789,
  "pickup_date": "2023-07-15T10:00:00Z",
  "dropoff_date": "2023-07-18T10:00:00Z",
  "pickup_location": "New York Downtown",
  "dropoff_location": "New York Downtown",
  "payment_method_id": 123
}
```

### Response

Status code: `201 Created`

```json
{
  "status": "success",
  "message": "Booking created successfully",
  "data": {
    "booking_id": 456
  }
}
```

### Error Responses

| Status Code | Message                      | Description                                      |
|-------------|------------------------------|--------------------------------------------------|
| 400         | Validation error             | Request contains invalid or missing fields       |
| 401         | Invalid token                | User not authenticated                           |
| 500         | Failed to create booking     | Server error                                    |

### Notes

- Vehicle availability is checked before booking is created
- Creates audit log entries for booking creation
- Sends notification to user after successful booking
- The booking service handles all validation and business logic

---

## Reschedule Booking

Change the dates of an existing booking.

### HTTP Request

`PUT /bookings/{id}/reschedule`

### Authentication

Requires a valid user authentication token.

### Path Parameters

| Parameter | Type    | Required | Description         | Constraints                     |
|-----------|---------|----------|---------------------|--------------------------------|
| `id`      | Integer | Yes      | Booking identifier  | Must be a valid booking ID     |

### Request Body Parameters

| Parameter     | Type   | Required | Description            | Constraints                        |
|---------------|--------|----------|------------------------|-----------------------------------|
| `pickup_date` | String | Yes      | New pickup date/time   | ISO 8601 format, future date      |
| `dropoff_date`| String | Yes      | New drop-off date/time | ISO 8601 format, after pickup date|

### Example Request

```json
{
  "pickup_date": "2023-07-20T10:00:00Z",
  "dropoff_date": "2023-07-23T10:00:00Z"
}
```

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Booking rescheduled successfully"
}
```

### Error Responses

| Status Code | Message                      | Description                                      |
|-------------|------------------------------|--------------------------------------------------|
| 400         | Validation error             | Invalid dates                                     |
| 401         | Unauthorized access          | User not authenticated                            |
| 500         | Failed to reschedule booking | Server error                                      |

### Notes

- Vehicle availability is checked before rescheduling is confirmed
- Audit logs are created for reschedule events
- Notification is sent about rescheduled booking

---

## Cancel Booking

Cancel an existing booking and process any applicable refund.

### HTTP Request

`POST /bookings/{id}/cancel`

### Authentication

Requires a valid user authentication token.

### Path Parameters

| Parameter | Type    | Required | Description         | Constraints                     |
|-----------|---------|----------|---------------------|--------------------------------|
| `id`      | Integer | Yes      | Booking identifier  | Must be a valid booking ID     |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Booking canceled successfully",
  "data": {
    "refund_amount": 349.99
  }
}
```

### Error Responses

| Status Code | Message                      | Description                                      |
|-------------|------------------------------|--------------------------------------------------|
| 400         | Error message from service   | Various booking cancellation errors              |
| 401         | Unauthorized access          | User not authenticated                            |
| 500         | Failed to cancel booking     | Server error                                      |

### Notes

- Cancellation policy determines refund amount based on time until pickup
- Refund is automatically processed if applicable through the payment service
- Audit logs are created for cancellation and refund events
- Notification is sent about canceled booking

---

## Get Booking Logs

Retrieve activity logs for a specific booking.

### HTTP Request

`GET /bookings/{id}/logs`

### Authentication

Requires a valid user authentication token.

### Path Parameters

| Parameter | Type    | Required | Description         | Constraints                     |
|-----------|---------|----------|---------------------|--------------------------------|
| `id`      | Integer | Yes      | Booking identifier  | Must be a valid booking ID     |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Booking logs fetched successfully",
  "data": {
    "logs": [
      {
        "id": 501,
        "event_type": "booking_created",
        "message": "Booking created",
        "user_id": 123,
        "timestamp": "2023-06-01T14:30:45Z",
        "details": {
          "vehicle_id": 789,
          "pickup_date": "2023-07-15T10:00:00Z",
          "dropoff_date": "2023-07-18T10:00:00Z"
        }
      },
      {
        "id": 502,
        "event_type": "payment_processed",
        "message": "Payment processed for booking",
        "user_id": 123,
        "timestamp": "2023-06-01T14:31:22Z",
        "details": {
          "amount": 349.99,
          "payment_method": "credit_card",
          "status": "completed"
        }
      }
    ]
  }
}
```

### Error Responses

| Status Code | Message                      | Description                                |
|-------------|------------------------------|--------------------------------------------|
| 401         | Unauthorized access          | User not authenticated                     |
| 403         | You do not have permission to view this booking | User doesn't own booking |
| 500         | Failed to fetch booking logs | Server error                              |

### Notes

- Admin users can access logs for any booking
- Regular users can only access logs for their own bookings
- Accessing booking logs is recorded in the audit trail

---

## Get Booking List (HTMX)

Retrieve an HTML formatted list of bookings for HTMX integration.

### HTTP Request

`GET /bookings/list`

### Authentication

Requires a valid session with user_id.

### Query Parameters

| Parameter | Type    | Required | Description                      | Constraints                      |
|-----------|---------|----------|----------------------------------|----------------------------------|
| `page`    | Integer | No       | Page number for pagination       | Default: 0                       |
| `per_page`| Integer | No       | Number of items per page         | Default: 5                       |
| `status`  | String  | No       | Filter by booking status         | Default: 'all'                  |

### Response

Status code: `200 OK`

Returns HTML content directly, not JSON. The HTML contains booking list items as defined in the `/public/views/partials/booking-list-item.php` template.

### Error Responses

HTML error messages are directly returned in case of failure.

### Notes

- Uses session authentication rather than token authentication
- Returns HTML directly for HTMX integration
- Creates audit log entries for booking list access
