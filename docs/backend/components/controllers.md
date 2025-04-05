# Controllers

## Overview
Controllers handle HTTP requests, process input data, interact with services, and return appropriate responses. They act as the bridge between the client-facing routes and the business logic contained within services.

## Controller Structure
All controllers in the CarFuse application extend a base `Controller` class that provides common functionality such as:

- Standardized JSON response formatting
- Error handling and exception management
- Basic input validation
- Logging capabilities

Controllers should follow these principles:
- Keep controllers thin - business logic belongs in services
- Use dependency injection for all service dependencies
- Log important events using the injected logger
- Handle exceptions properly using the ExceptionHandler
- Return consistent response formats

## Available Controllers

### AuthController
Handles user authentication including login, registration, token refresh, and password reset.

| Method | Path | Auth Required | Purpose |
|--------|------|--------------|---------|
| POST | /auth/login | No | Authenticate a user and provide access tokens |
| POST | /auth/register | No | Register a new user account |
| POST | /auth/refresh | Refresh Token | Refresh an expired JWT token |
| POST | /auth/logout | Yes | Invalidate current user tokens |
| GET | /auth/user | Yes | Get authenticated user details |
| POST | /auth/password/reset-request | No | Request a password reset link |
| POST | /auth/password/reset | No | Reset password using token |

#### Login Endpoint
**Required Parameters:**
- `email` (string): User's email address
- `password` (string): User's password

**Response Format:**
```json
{
  "message": "Login successful",
  "user_id": 123,
  "name": "John Doe"
}
```

**Notes:**
- JWT and refresh tokens are sent as secure, HttpOnly cookies
- Implements rate limiting to prevent brute force attacks
- Logs login attempts for security monitoring

**Error Codes:**
- 400: Missing required fields
- 401: Invalid credentials
- 429: Too many login attempts

**Dependencies:**
- AuthService, TokenService, RateLimiter, LoggerInterface

### BookingController
Manages vehicle booking operations such as creating bookings, rescheduling, cancellation, and retrieval of booking information.

| Method | Path | Auth Required | Purpose |
|--------|------|--------------|---------|
| GET | /bookings/{id} | Yes | View booking details |
| GET | /bookings | Yes | List user's bookings |
| POST | /bookings | Yes | Create a new booking |
| PUT | /bookings/{id}/reschedule | Yes | Reschedule a booking |
| POST | /bookings/{id}/cancel | Yes | Cancel a booking and process refund |
| GET | /bookings/{id}/logs | Yes | Get booking activity logs |
| GET | /bookings/list | Yes | Get bookings in HTML format for HTMX |

#### Create Booking Endpoint
**Required Parameters:**
- `vehicle_id` (integer): ID of the vehicle to book
- `pickup_date` (string, date format): Date and time of pickup
- `dropoff_date` (string, date format): Date and time of dropoff
- `pickup_location` (string): Location for pickup
- `dropoff_location` (string): Location for dropoff
- `payment_method_id` (integer): ID of the payment method to use

**Response Format:**
```json
{
  "status": "success",
  "message": "Booking created successfully",
  "data": {
    "booking_id": 456
  }
}
```

**Error Codes:**
- 400: Validation error or business rule violation
- 401: Unauthorized access
- 500: Server error

**Notes:**
- Creates audit log entries for booking creation
- Sends notification after successful booking
- Validates available vehicle inventory

**Dependencies:**
- BookingService, PaymentService, Validator, AuditService, NotificationService, ResponseFactoryInterface

### PaymentController
Handles payment processing, refunds, and retrieval of transaction history.

| Method | Path | Auth Required | Purpose |
|--------|------|--------------|---------|
| POST | /payments/process | Yes | Process a payment |
| POST | /payments/refund | Yes (Admin) | Process a refund |
| GET | /payments/user | Yes | Get user's transaction history |
| GET | /payments/{id} | Yes | Get payment details |
| POST | /payments/methods | Yes | Add a payment method |
| GET | /payments/methods | Yes | Get user's payment methods |
| DELETE | /payments/methods/{id} | Yes | Delete a payment method |
| POST | /payments/gateway | Yes | Process payment via gateway |
| POST | /payments/gateway/{gateway}/callback | No | Handle gateway callback |
| GET | /payments/history | Yes | Get payment history (HTMX) |
| GET | /payments/methods/details/{id} | Yes | Get payment method details (HTMX) |
| GET | /payments/details/{id} | Yes | Get payment details (HTMX) |
| GET | /payments/invoice/{id} | Yes | Download payment invoice |

#### Process Payment Endpoint
**Required Parameters:**
- `booking_id` (integer): ID of the booking
- `amount` (numeric): Payment amount
- `payment_method_id` (integer): ID of the payment method
- `currency` (string, optional): 3-letter currency code (default: system default)

**Response Format:**
```json
{
  "status": "success",
  "message": "Payment processed",
  "data": {
    "payment": {
      "id": 789,
      "amount": 199.99,
      "status": "completed",
      "created_at": "2023-05-01T14:30:00Z"
    }
  }
}
```

**Error Codes:**
- 400: Validation error
- 401: Unauthorized access
- 500: Payment processing failed

**Notes:**
- Sends payment confirmation notification
- Records transaction in system
- Validates payment method ownership

**Dependencies:**
- PaymentService, Validator, NotificationService, ExceptionHandler

### UserController
Manages user profiles, account settings, and user-specific operations.

| Method | Path | Auth Required | Purpose |
|--------|------|--------------|---------|
| POST | /users/register | No | Register a new user |
| GET | /users/profile | Yes | Get user profile |
| PUT | /users/profile | Yes | Update user profile |
| POST | /users/password/reset-request | No | Request password reset |
| POST | /users/password/reset | No | Reset password with token |
| GET | /users/dashboard | Yes | Get user dashboard data |
| GET | /users/profile/page | Yes | Show profile page (HTML) |
| POST | /users/profile/update | Yes | Handle profile update |
| POST | /users/password/change | Yes | Change user password |

#### Update User Profile Endpoint
**Required Parameters:**
- `name` (string, optional): User's name
- `bio` (string, optional): User biography
- `location` (string, optional): User location
- `avatar_url` (string, optional): URL to user avatar

**Response Format:**
```json
{
  "status": "success",
  "message": "Profile updated successfully",
  "data": {
    "id": 123,
    "name": "John Doe",
    "email": "john@example.com",
    "bio": "Software developer",
    "location": "New York",
    "avatar_url": "/uploads/avatars/user_123.jpg"
  }
}
```

**Error Codes:**
- 400: Validation error
- 401: User not authenticated
- 500: Failed to update profile

**Notes:**
- Supports file upload for profile picture
- Creates audit log of profile changes
- Validates input data

**Dependencies:**
- User model, Validator, TokenService, AuditService, AuthService, ExceptionHandler

### DocumentController
Handles document generation, management, and template processing for contracts, invoices, and terms & conditions.

| Method | Path | Auth Required | Purpose |
|--------|------|--------------|---------|
| POST | /documents/templates | Yes (Admin) | Upload document template |
| POST | /documents/contracts/{bookingId}/{userId} | Yes | Generate contract for booking |
| POST | /documents/terms | Yes (Admin) | Upload terms & conditions |
| POST | /documents/invoices/{bookingId} | Yes | Generate invoice for booking |
| DELETE | /documents/{id} | Yes (Admin) | Delete a document |
| GET | /documents/templates | Yes (Admin) | Get all templates |
| GET | /documents/templates/{id} | Yes (Admin) | Get specific template |

#### Generate Contract Endpoint
**Required Parameters:**
- `bookingId` (integer): ID of the booking
- `userId` (integer): ID of the user

**Response Format:**
```json
{
  "status": "success",
  "message": "Contract generated successfully",
  "contract_path": "/documents/contracts/booking_456_user_123.pdf"
}
```

**Error Codes:**
- 400: Invalid booking or user ID
- 401: Unauthorized access
- 500: Failed to generate contract

**Notes:**
- Securely generates contract using booking and user data
- Validates user has access to requested booking
- Stores contract in secure location

**Dependencies:**
- DocumentService, Validator, AuditService, ExceptionHandler

### SignatureController
Manages electronic signatures for documents including uploading, verifying, and retrieving signatures.

| Method | Path | Auth Required | Purpose |
|--------|------|--------------|---------|
| POST | /signatures/upload | Yes | Upload a signature |
| POST | /signatures/verify | Yes | Verify a signature against document hash |
| GET | /signatures/user/{userId} | Yes | Retrieve a user's signature |

#### Upload Signature Endpoint
**Required Parameters:**
- `user_id` (integer): ID of the user
- `file` (file): Signature image file (PNG, JPG, JPEG, max 2MB)

**Response Format:**
```json
{
  "status": "success", 
  "message": "Signature uploaded successfully", 
  "data": "/uploads/signatures/user_123_timestamp.png"
}
```

**Error Codes:**
- 400: Invalid file format or size
- 401: Unauthorized access
- 500: Upload failed

**Notes:**
- Validates file type and size
- Logs upload in audit trail
- Secure file storage with user-specific naming

**Dependencies:**
- SignatureService, AuditService, TokenService, ExceptionHandler

### NotificationController
Handles user notification management including sending, reading, and deleting notifications.

| Method | Path | Auth Required | Purpose |
|--------|------|--------------|---------|
| GET | /notifications | Yes | View user notifications |
| GET | /notifications/user | Yes | Get user notifications via API |
| GET | /notifications/unread | Yes | Fetch unread notifications |
| POST | /notifications/mark-read | Yes | Mark notification as read |
| POST | /notifications/delete | Yes | Delete a notification |
| POST | /notifications/send | Yes | Send a notification |

#### Send Notification Endpoint
**Required Parameters:**
- `user_id` (integer): ID of the recipient user
- `type` (string): Notification type (email, sms, webhook, push)
- `message` (string): Notification message content
- `options` (array, optional): Additional configuration options

**Response Format:**
```json
{
  "status": "success",
  "message": "Notification sent successfully",
  "data": {
    "notification": {
      "id": 456,
      "user_id": 123,
      "type": "email",
      "message": "Your booking has been confirmed",
      "read": false,
      "created_at": "2023-05-01T12:30:00Z"
    }
  }
}
```

**Error Codes:**
- 400: Validation failed
- 401: Unauthorized access
- 500: Failed to send notification

**Notes:**
- Supports multiple notification channels
- Creates audit log of notification sending
- Validates recipient access permissions

**Dependencies:**
- NotificationService, TokenService, AuditService, ExceptionHandler

### ReportController
Manages report generation and viewing for both users and administrators.

| Method | Path | Auth Required | Purpose |
|--------|------|--------------|---------|
| GET | /reports | Yes (Admin) | Admin report dashboard |
| POST | /reports/generate | Yes (Admin) | Generate admin report |
| GET | /reports/user | Yes | User report dashboard |
| POST | /reports/user/generate | Yes | Generate user report |

#### Generate Report Endpoint
**Required Parameters:**
- `date_range` (object): Report date range with `start` and `end` dates
- `format` (string): Output format (pdf, csv, excel)
- `report_type` (string): Type of report to generate
- `filters` (array, optional): Custom report filters

**Response Format:**
File download with appropriate headers

**Error Codes:**
- 400: Missing required parameters
- 401: Unauthorized access
- 500: Report generation failed

**Notes:**
- Logs report generation in audit trail
- Supports multiple export formats
- Includes comprehensive filtering options

**Dependencies:**
- ReportService, NotificationService, AuditService, ExceptionHandler

### DashboardController
Handles dashboard data and components for the user interface.

| Method | Path | Auth Required | Purpose |
|--------|------|--------------|---------|
| GET | /dashboard | Yes | User dashboard view |
| GET | /dashboard/statistics | Yes | Get user statistics (HTMX) |
| GET | /dashboard/bookings | Yes | Get user bookings (HTMX) |
| GET | /dashboard/profile | Yes | Get user profile (HTMX) |
| GET | /dashboard/notifications | Yes | Get user notifications (HTMX) |

#### User Dashboard Statistics Endpoint
**Required Parameters:**
- None (Uses session for authentication)

**Response Format:**
HTML component for HTMX integration showing:
- Total bookings count
- Completed bookings count
- Total payments amount

**Error Codes:**
- HTML error message on failure

**Notes:**
- Uses HTMX partial rendering
- Implements caching for performance
- Creates audit log of dashboard access

**Dependencies:**
- BookingService, MetricsService, NotificationService, UserService, AuditService, ExceptionHandler

### AdminController
Manages admin operations for user management and system oversight.

| Method | Path | Auth Required | Purpose |
|--------|------|--------------|---------|
| GET | /admin/users | Yes (Admin) | Get all users |
| GET | /admin/users/{id} | Yes (Admin) | Get user by ID |
| POST | /admin/users | Yes (Admin) | Create a new user |
| PUT | /admin/users/{id} | Yes (Admin) | Update user details |
| POST | /admin/users/{id}/status | Yes (Admin) | Toggle user active status |
| POST | /admin/users/{id}/role | Yes (Admin) | Update user role |
| DELETE | /admin/users/{id} | Yes (Admin) | Delete a user |
| GET | /admin/dashboard/data | Yes (Admin) | Get admin dashboard data |
| POST | /admin/users/admin | Yes (Admin) | Create a new admin user |
| GET | /admin/users/page | Yes (Admin) | Users management page (HTML) |

#### Get All Users Endpoint
**Required Parameters:**
- `page` (integer, optional): Pagination page number (default: 1)
- `per_page` (integer, optional): Items per page (default: 10)
- `role` (string, optional): Filter by user role
- `status` (string, optional): Filter by user status
- `search` (string, optional): Search term for filtering

**Response Format:**
```json
{
  "status": "success",
  "message": "User list retrieved successfully",
  "data": [
    {
      "id": 123,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "user",
      "active": true,
      "created_at": "2023-04-01T10:20:30Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total_pages": 5,
    "total": 45,
    "per_page": 10
  }
}
```

**Error Codes:**
- 401: Unauthorized or insufficient permissions
- 500: Failed to retrieve users

**Notes:**
- Supports HTMX partial rendering for UI
- Implements pagination headers
- Provides search and filtering capabilities

**Dependencies:**
- AdminService, ResponseFactoryInterface, ExceptionHandler

### AdminDashboardController
Provides data visualization and statistics for the administrative dashboard.

| Method | Path | Auth Required | Purpose |
|--------|------|--------------|---------|
| GET | /admin/dashboard | Yes (Admin) | Admin dashboard main view |
| GET | /admin/dashboard/data | Yes (Admin) | Get admin dashboard statistics via API |

#### Dashboard Data Endpoint
**Required Parameters:**
- None (Uses session for authentication)

**Response Format:**
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
    "recent_bookings": [
      {
        "id": 1001,
        "user_id": 123,
        "vehicle_id": 45,
        "status": "completed",
        "amount": 249.99,
        "created_at": "2023-05-15T14:30:22Z"
      }
    ]
  }
}
```

**Error Codes:**
- 401: Unauthorized access
- 500: Failed to retrieve dashboard data

**Notes:**
- Uses caching for performance optimization
- Creates audit logs for dashboard access
- Provides real-time business metrics

**Dependencies:**
- User/Booking/Payment models, Cache, AuditService, ExceptionHandler

### AdminSettingsController
Manages system configuration and settings for the administrative panel.

| Method | Path | Auth Required | Purpose |
|--------|------|--------------|---------|
| GET | /admin/settings | Yes (Admin) | Show settings page |
| GET | /admin/settings/all | Yes (Admin) | Retrieve all system settings |
| POST | /admin/settings | Yes (Admin) | Save all settings at once |
| POST | /admin/settings/{tab} | Yes (Admin) | Save tab-specific settings |
| POST | /admin/settings/email/test | Yes (Admin) | Test email connection |

#### Save All Settings Endpoint
**Required Parameters:**
- Configuration settings as JSON object

**Response Format:**
```json
{
  "status": "success",
  "message": "Settings saved successfully"
}
```

**Error Codes:**
- 400: Invalid settings data format
- 401: Unauthorized access
- 500: Failed to save settings

**Notes:**
- Creates audit log of settings changes
- Validates settings before saving
- Supports modular settings by tab

**Dependencies:**
- SettingsService, AuditService, ExceptionHandler

### ApiController
Base controller for API operations providing standardized response formats and error handling.

| Method | Path | Auth Required | Purpose |
|--------|------|--------------|---------|
| N/A | N/A | N/A | Base controller - not directly routable |

**Notes:**
- Provides standardized success/error response formats
- Implements common exception handling
- Manages audit logging for API operations

**Dependencies:**
- AuditService, ExceptionHandler

### AuditController
Manages system audit log viewing and retrieval for security and compliance purposes.

| Method | Path | Auth Required | Purpose |
|--------|------|--------------|---------|
| GET | /admin/audit | Yes (Admin) | View audit logs dashboard |
| POST | /admin/audit/logs | Yes (Admin) | Fetch filtered audit logs |
| GET | /admin/audit/logs/{id} | Yes (Admin) | Get detailed log entry |
| POST | /admin/audit/export | Yes (Admin) | Export audit logs |

#### Fetch Logs Endpoint
**Required Parameters:**
- `category` (string, optional): Filter by log category
- `action` (string, optional): Filter by action type
- `user_id` (integer, optional): Filter by user ID
- `booking_id` (integer, optional): Filter by booking ID
- `start_date` (string, optional): Start date for date range filter
- `end_date` (string, optional): End date for date range filter
- `page` (integer, optional): Page number for pagination
- `per_page` (integer, optional): Items per page
- `log_level` (string, optional): Filter by log level

**Response Format:**
```json
{
  "status": "success",
  "data": {
    "logs": [
      {
        "id": 12345,
        "event_type": "booking_created",
        "message": "User created a new booking",
        "context": {"booking_id": 789, "amount": 149.99},
        "user_id": 123,
        "resource_id": 789,
        "category": "booking",
        "created_at": "2023-05-15T10:30:45Z",
        "ip_address": "192.168.1.1"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 10,
      "total_items": 250,
      "per_page": 25
    }
  }
}
```

**Error Codes:**
- 403: Admin access required
- 500: Failed to retrieve logs

**Notes:**
- Comprehensive filtering options
- Access control based on admin role
- Efficient query optimization

**Dependencies:**
- AuditService, ExceptionHandler

### BaseController
Abstract base controller providing common functionality for HTMX-specific controllers.

| Method | Path | Auth Required | Purpose |
|--------|------|--------------|---------|
| N/A | N/A | N/A | Abstract base class - not directly routable |

**Notes:**
- Handles HTMX-specific response headers
- Provides methods for toast notifications
- Manages view rendering and redirects
- Abstracts request handling

**Dependencies:**
- LoggerInterface, ExceptionHandler

### Controller
Base controller providing core functionality for all controllers in the system.

| Method | Path | Auth Required | Purpose |
|--------|------|--------------|---------|
| N/A | N/A | N/A | Base class - not directly routable |

**Notes:**
- Standardized JSON response generation
- Common error response handling
- Exception management
- Input validation helpers

**Dependencies:**
- LoggerInterface, ExceptionHandler

## Best Practices
1. **Thin Controllers**: Keep controllers focused on handling HTTP requests and delegating business logic to services.

2. **Consistent Response Format**: Always return consistent JSON response structures using helper methods.

3. **Proper Error Handling**: Use ExceptionHandler to handle exceptions and return appropriate HTTP status codes.

4. **Audit Logging**: Log important user actions using AuditService for security and compliance.

5. **Input Validation**: Always validate input data using the Validator service before processing.

6. **Authentication & Authorization**: Always check for proper authentication and authorization before processing requests.

7. **Service Dependency Injection**: Inject all required services via constructor to ensure testability.

8. **Appropriate HTTP Methods**: Use the correct HTTP methods (GET, POST, PUT, DELETE) for each endpoint.
