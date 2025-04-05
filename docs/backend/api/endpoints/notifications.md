# Notifications API Endpoints

## Overview

The Notifications API enables management of user notifications including viewing, marking as read, deleting, and sending notifications. These endpoints support multiple notification channels (in-app, email, SMS) and provide real-time updates to users about booking status, payments, and system events.

## Authentication and Permissions

| Endpoint Pattern                    | Required Role | Notes                                      |
|------------------------------------|---------------|-------------------------------------------|
| `GET /notifications`                | User          | View user notifications (HTML response)    |
| `GET /notifications/user`           | User          | Get user notifications (JSON response)     |
| `GET /notifications/unread`         | User          | Fetch unread notifications                |
| `POST /notifications/mark-read`     | User          | Mark notification as read                 |
| `POST /notifications/delete`        | User          | Delete a notification                     |
| `POST /notifications/send`          | Admin         | Send a notification (admin only)          |
| `GET /notifications/preferences`    | User          | Get user notification preferences         |
| `PUT /notifications/preferences`    | User          | Update user notification preferences      |

## Rate Limiting

Notification endpoints have the following rate limits:
- Standard tier: 60 requests per minute
- Premium tier: 120 requests per minute
- Sending notifications (admin): 100 requests per minute

---

## View User Notifications

Get a rendered HTML view of user notifications for UI integration.

### HTTP Request

`GET /notifications`

### Authentication

Requires a valid user authentication token.

### Query Parameters

| Parameter  | Type    | Required | Description               | Constraints                     |
|------------|---------|----------|---------------------------|--------------------------------|
| `page`     | Integer | No       | Page number               | Default: 1, Min: 1              |
| `per_page` | Integer | No       | Items per page            | Default: 10, Max: 50            |

### Response

Status code: `200 OK`

Returns HTML content for rendering in the UI. Typical response is a rendered HTML fragment with notification items.

### Error Codes

| Status Code | Error Code           | Description                                      |
|-------------|---------------------|--------------------------------------------------|
| 401         | `UNAUTHORIZED`       | User not authenticated                           |
| 500         | `SERVER_ERROR`       | Failed to retrieve notifications                 |

### Notes

- Optimized for HTMX integration
- All notifications viewed will be marked as seen (but not read)
- Supports infinite scrolling via pagination

---

## Get User Notifications 

Get a JSON list of notifications for the authenticated user.

### HTTP Request

`GET /notifications/user`

### Authentication

Requires a valid user authentication token.

### Query Parameters

| Parameter  | Type    | Required | Description               | Constraints                     |
|------------|---------|----------|---------------------------|--------------------------------|
| `page`     | Integer | No       | Page number               | Default: 1, Min: 1              |
| `per_page` | Integer | No       | Items per page            | Default: 20, Max: 100           |
| `read`     | Boolean | No       | Filter by read status     | true/false                      |
| `type`     | String  | No       | Filter by notification type| e.g., booking, payment, system  |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Notifications retrieved successfully",
  "data": {
    "notifications": [
      {
        "id": 123,
        "type": "booking_confirmation",
        "message": "Your booking #456 has been confirmed",
        "read": false,
        "data": {
          "booking_id": 456,
          "vehicle": "Toyota Camry"
        },
        "created_at": "2023-06-15T14:30:00Z"
      },
      {
        "id": 124,
        "type": "payment_processed",
        "message": "Your payment of $349.99 has been processed",
        "read": true,
        "data": {
          "payment_id": 789,
          "booking_id": 456,
          "amount": 349.99
        },
        "created_at": "2023-06-15T14:31:22Z"
      }
    ]
  },
  "meta": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 92,
    "per_page": 20,
    "unread_count": 7
  }
}
```

### Error Codes

| Status Code | Error Code           | Description                                      |
|-------------|---------------------|--------------------------------------------------|
| 401         | `UNAUTHORIZED`       | User not authenticated                           |
| 500         | `SERVER_ERROR`       | Failed to retrieve notifications                 |

### Notes

- Notifications are sorted by creation date (newest first)
- Response includes total unread count for badge display
- Notification viewing is logged for audit purposes

---

## Fetch Unread Notifications

Get only unread notifications for the authenticated user.

### HTTP Request

`GET /notifications/unread`

### Authentication

Requires a valid user authentication token.

### Query Parameters

| Parameter  | Type    | Required | Description               | Constraints                     |
|------------|---------|----------|---------------------------|--------------------------------|
| `limit`    | Integer | No       | Maximum items to return   | Default: 10, Max: 50            |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Unread notifications retrieved successfully",
  "data": {
    "notifications": [
      {
        "id": 123,
        "type": "booking_confirmation",
        "message": "Your booking #456 has been confirmed",
        "data": {
          "booking_id": 456,
          "vehicle": "Toyota Camry"
        },
        "created_at": "2023-06-15T14:30:00Z"
      }
    ]
  },
  "meta": {
    "total_unread": 7
  }
}
```

### Error Codes

| Status Code | Error Code           | Description                                      |
|-------------|---------------------|--------------------------------------------------|
| 401         | `UNAUTHORIZED`       | User not authenticated                           |
| 500         | `SERVER_ERROR`       | Failed to retrieve notifications                 |

### Notes

- Optimized for real-time notification display
- Suitable for notification badge counts
- Supports polling for new notifications

---

## Mark Notification as Read

Mark a specific notification as read.

### HTTP Request

`POST /notifications/mark-read`

### Authentication

Requires a valid user authentication token.

### Request Body Parameters

| Parameter         | Type    | Required | Description                  | Constraints                       |
|-------------------|---------|----------|------------------------------|----------------------------------|
| `notification_id` | Integer | Yes      | ID of notification to mark   | Must be a valid notification ID   |

### Example Request

```json
{
  "notification_id": 123
}
```

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Notification marked as read"
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `MISSING_NOTIFICATION_ID`| Notification ID is missing                        |
| 401         | `UNAUTHORIZED`           | User not authenticated                            |
| 403         | `FORBIDDEN`              | User does not own this notification               |
| 404         | `NOTIFICATION_NOT_FOUND` | Notification not found                            |
| 500         | `SERVER_ERROR`           | Failed to mark notification as read               |

### Notes

- Only the notification owner can mark it as read
- Operation is idempotent - marking already read notifications is allowed
- Notification read status is logged for audit purposes

---

## Delete Notification

Delete a specific notification.

### HTTP Request

`POST /notifications/delete`

### Authentication

Requires a valid user authentication token.

### Request Body Parameters

| Parameter         | Type    | Required | Description                  | Constraints                       |
|-------------------|---------|----------|------------------------------|----------------------------------|
| `notification_id` | Integer | Yes      | ID of notification to delete | Must be a valid notification ID   |

### Example Request

```json
{
  "notification_id": 123
}
```

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Notification deleted successfully"
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `MISSING_NOTIFICATION_ID`| Notification ID is missing                        |
| 401         | `UNAUTHORIZED`           | User not authenticated                            |
| 403         | `FORBIDDEN`              | User does not own this notification               |
| 404         | `NOTIFICATION_NOT_FOUND` | Notification not found                            |
| 500         | `SERVER_ERROR`           | Failed to delete notification                     |

### Notes

- Only the notification owner can delete it
- System critical notifications might be undeletable
- Deletion is logged for audit purposes
- Notification might be soft-deleted for retention policies

---

## Send Notification

Send a notification to a specific user or group of users (admin only).

### HTTP Request

`POST /notifications/send`

### Authentication

Requires admin privileges.

### Request Body Parameters

| Parameter       | Type    | Required | Description                  | Constraints                       |
|-----------------|---------|----------|------------------------------|----------------------------------|
| `user_id`       | Integer | Conditional | Recipient user ID         | Required if no user_ids provided  |
| `user_ids`      | Array   | Conditional | Array of recipient user IDs | Required if no user_id provided   |
| `type`          | String  | Yes      | Notification type            | Must be a valid notification type |
| `message`       | String  | Yes      | Notification message         | Max: 500 characters               |
| `data`          | Object  | No       | Additional notification data | Max size: 5KB                     |
| `send_email`    | Boolean | No       | Also send as email           | Default: false                    |
| `send_sms`      | Boolean | No       | Also send as SMS             | Default: false                    |
| `priority`      | String  | No       | Notification priority        | Values: low, normal, high         |

### Example Request

```json
{
  "user_id": 123,
  "type": "system_notification",
  "message": "Your vehicle inspection is due next week",
  "data": {
    "booking_id": 456,
    "vehicle_id": 789,
    "inspection_date": "2023-06-22T14:00:00Z"
  },
  "send_email": true,
  "priority": "normal"
}
```

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Notification sent successfully",
  "data": {
    "notification_id": 125,
    "channels_sent": ["in_app", "email"]
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                      |
|-------------|--------------------------|--------------------------------------------------|
| 400         | `VALIDATION_ERROR`       | Invalid or missing parameters                     |
| 401         | `UNAUTHORIZED`           | User not authenticated                            |
| 403         | `FORBIDDEN`              | User does not have admin privileges               |
| 404         | `USER_NOT_FOUND`         | One or more users not found                       |
| 500         | `NOTIFICATION_FAILED`    | Failed to send notification                       |

### Notes

- Message templates are supported using {{variable}} placeholders
- Email and SMS delivery is asynchronous
- Notifications respect user preferences
- Rate limiting and throttling apply to prevent notification spam
- Bulk notifications to many users are processed in batches

---

## Get Notification Preferences

Retrieve notification preferences for the authenticated user.

### HTTP Request

`GET /notifications/preferences`

### Authentication

Requires a valid user authentication token.

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "data": {
    "preferences": {
      "email_enabled": true,
      "sms_enabled": false,
      "push_enabled": true,
      "categories": {
        "booking": {
          "in_app": true,
          "email": true,
          "sms": false,
          "push": true
        },
        "payment": {
          "in_app": true,
          "email": true,
          "sms": false,
          "push": false
        },
        "system": {
          "in_app": true,
          "email": false,
          "sms": false,
          "push": false
        }
      },
      "quiet_hours": {
        "enabled": true,
        "start": "22:00",
        "end": "08:00",
        "timezone": "America/New_York"
      }
    }
  }
}
```

### Error Codes

| Status Code | Error Code           | Description                                      |
|-------------|---------------------|--------------------------------------------------|
| 401         | `UNAUTHORIZED`       | User not authenticated                           |
| 500         | `SERVER_ERROR`       | Failed to retrieve notification preferences      |

### Notes

- Default preferences are used if user hasn't set custom preferences
- Preferences are used to filter notifications across all channels
- Time-sensitive critical notifications may override quiet hours

---

## Update Notification Preferences

Update notification preferences for the authenticated user.

### HTTP Request

`PUT /notifications/preferences`

### Authentication

Requires a valid user authentication token.

### Request Body Parameters

| Parameter        | Type    | Required | Description                  | Constraints                       |
|------------------|---------|----------|------------------------------|----------------------------------|
| `email_enabled`  | Boolean | No       | Enable email notifications   | true/false                        |
| `sms_enabled`    | Boolean | No       | Enable SMS notifications     | true/false                        |
| `push_enabled`   | Boolean | No       | Enable push notifications    | true/false                        |
| `categories`     | Object  | No       | Category-specific settings   | See example below                 |
| `quiet_hours`    | Object  | No       | Quiet hours settings         | See example below                 |

### Example Request

```json
{
  "email_enabled": true,
  "sms_enabled": false,
  "categories": {
    "booking": {
      "email": true,
      "sms": false
    }
  },
  "quiet_hours": {
    "enabled": true,
    "start": "23:00",
    "end": "07:00",
    "timezone": "America/New_York"
  }
}
```

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Notification preferences updated successfully"
}
```

### Error Codes

| Status Code | Error Code           | Description                                      |
|-------------|---------------------|--------------------------------------------------|
| 400         | `VALIDATION_ERROR`   | Invalid preferences format                       |
| 401         | `UNAUTHORIZED`       | User not authenticated                           |
| 500         | `UPDATE_FAILED`      | Failed to update notification preferences        |

### Notes

- Partial updates are supported - only specified fields will be changed
- Preferences update is logged for audit purposes
- Timezone must be a valid IANA timezone identifier
- Changes take effect immediately for new notifications
