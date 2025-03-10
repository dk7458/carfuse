## AdminController

**Class Purpose**: Handles admin user management and dashboard operations.

**Key Methods**:
- `getAllUsers()`
- `updateUserRole(int $userId)`
- `deleteUser(int $userId)`
- `getDashboardData()`
- `createAdmin()`

**HTTP Routes**:
- `GET /admin/users`: Get all users
- `PUT /admin/users/{userId}/role`: Update user role
- `DELETE /admin/users/{userId}`: Delete user
- `GET /admin/dashboard/data`: Get dashboard data
- `POST /admin/create`: Create admin

**Parameters & Responses**:
- `getAllUsers`: (GET, query: page), returns paginated user list
- `updateUserRole`: (PUT, path: userId, body: role), returns success/error
- `deleteUser`: (DELETE, path: userId), returns success/error
- `getDashboardData`: (GET), returns dashboard statistics
- `createAdmin`: (POST, body: name, email, password), returns success/error

## AdminDashboardController

**Class Purpose**: Renders and populates the admin dashboard interface.

**Key Methods**:
- `index()`
- `getDashboardData()`

**HTTP Routes**:
- `GET /admin/dashboard`: Render dashboard
- `GET /admin/dashboard/data`: Get dashboard data

**Parameters & Responses**:
- `index`: (GET), returns HTML view
- `getDashboardData`: (GET), returns dashboard data in JSON

## ApiController

**Class Purpose**: Provides common functionality for API controllers, including standardized JSON responses and error handling.

**Key Methods**:
- `success(string $message, array $data = [], int $status = 200)`
- `error(string $message, array $errors = [], int $status = 400)`

**HTTP Routes**: N/A (Base Controller)

**Parameters & Responses**:
- `success`: (N/A, message, data, status), returns JSON response
- `error`: (N/A, message, errors, status), returns JSON response

## AuditController

**Class Purpose**: Handles viewing and retrieving audit logs.

**Key Methods**:
- `index()`
- `fetchLogs()`
- `getLog(int $id)`
- `exportLogs()`

**HTTP Routes**:
- `GET /audit`: View audit logs
- `POST /audit/fetch`: Fetch logs
- `GET /audit/{id}`: Get log details
- `POST /audit/export`: Export logs

**Parameters & Responses**:
- `index`: (GET, query params), returns audit logs
- `fetchLogs`: (POST, body params), returns audit logs
- `getLog`: (GET, path: id), returns log details
- `exportLogs`: (POST, body params), returns export information

## AuthController

**Class Purpose**: Handles user authentication, registration, and password management.

**Key Methods**:
- `login(Request $request, Response $response)`
- `register(Request $request, Response $response)`
- `refresh(Request $request, Response $response)`
- `resetPasswordRequest(Request $request, Response $response)`
- `resetPassword(Request $request, Response $response)`
- `userDetails(Request $request, Response $response)`

**HTTP Routes**:
- `POST /auth/login`: Login
- `POST /auth/register`: Register
- `POST /auth/refresh`: Refresh token
- `POST /auth/logout`: Logout
- `GET /auth/user`: User details
- `POST /auth/reset-password-request`: Reset password request
- `POST /auth/reset-password`: Reset password

**Parameters & Responses**:
- `login`: (POST, body: email, password), returns success/error
- `register`: (POST, body: name, surname, email, password), returns success/error
- `refresh`: (POST, body: refresh_token), returns success/error
- `logout`: (POST), returns success/error
- `userDetails`: (GET), returns user details
- `resetPasswordRequest`: (POST, body: email), returns success/error
- `resetPassword`: (POST, body: token, password, confirm_password), returns success/error

## BookingController

**Class Purpose**: Handles booking operations such as viewing, rescheduling, canceling, and creating bookings.

**Key Methods**:
- `viewBooking(int $id)`
- `rescheduleBooking(int $id)`
- `cancelBooking(int $id)`
- `getBookingLogs(int $bookingId)`
- `getUserBookings()`
- `createBooking()`

**HTTP Routes**:
- `GET /bookings/{id}`: View booking
- `POST /bookings/{id}/reschedule`: Reschedule booking
- `POST /bookings/{id}/cancel`: Cancel booking
- `GET /bookings/{bookingId}/logs`: Get booking logs
- `GET /bookings/user`: Get user bookings
- `POST /bookings`: Create booking

**Parameters & Responses**:
- `viewBooking`: (GET, path: id), returns booking details
- `rescheduleBooking`: (POST, path: id, body: pickup_date, dropoff_date), returns success/error
- `cancelBooking`: (POST, path: id), returns success/error
- `getBookingLogs`: (GET, path: bookingId), returns booking logs
- `getUserBookings`: (GET), returns user bookings
- `createBooking`: (POST, body: vehicle_id, pickup_date, dropoff_date), returns success/error

## Controller (Base)

**Class Purpose**: Provides shared methods for all controllers.

**Key Methods**:
- `jsonResponse(Response $response, $data, $status = 200)`
- `errorResponse(Response $response, $message, $status = 400)`

**HTTP Routes**: N/A (Base Controller)

**Parameters & Responses**:
- `jsonResponse`: (N/A, data, status), returns JSON response
- `errorResponse`: (N/A, message, status), returns JSON response

## DashboardController

**Class Purpose**: Handles user dashboard related operations.

**Key Methods**:
- `userDashboard()`
- `getUserBookings()`
- `fetchStatistics()`
- `fetchNotifications()`
- `fetchUserProfile()`

**HTTP Routes**:
- `GET /dashboard`: User dashboard
- `GET /dashboard/bookings`: Get user bookings
- `GET /dashboard/statistics`: Fetch statistics
- `GET /dashboard/notifications`: Fetch notifications
- `GET /dashboard/profile`: Fetch user profile

**Parameters & Responses**:
- `userDashboard`: (GET), returns HTML view
- `getUserBookings`: (GET), returns user bookings
- `fetchStatistics`: (GET), returns statistics
- `fetchNotifications`: (GET), returns notifications
- `fetchUserProfile`: (GET), returns user profile


## DocumentController

**Class Purpose**: Manages document templates, contracts, and terms and conditions.

**Key Methods**:
- `uploadTemplate()`
- `generateContract(int $bookingId, int $userId)`
- `uploadTerms()`
- `generateInvoice(int $bookingId)`
- `deleteDocument(int $documentId)`
- `getTemplates()`
- `getTemplate(int $templateId)`

**HTTP Routes**:
- `POST /documents/templates`: Upload template
- `GET /documents/contracts/{bookingId}`: Generate contract
- `POST /documents/terms`: Upload terms
- `GET /documents/invoices/{bookingId}`: Generate invoice
- `DELETE /documents/{documentId}`: Delete document
- `GET /documents/templates`: Get all templates
- `GET /documents/templates/{templateId}`: Get template

**Parameters & Responses**:
- `uploadTemplate`: (POST, body: name, content), returns success/error
- `generateContract`: (GET, path: bookingId, userId), returns success/error
- `uploadTerms`: (POST, body: content), returns success/error
- `generateInvoice`: (GET, path: bookingId), returns success/error
- `deleteDocument`: (DELETE, path: documentId), returns success/error
- `getTemplates`: (GET), returns templates
- `getTemplate`: (GET, path: templateId), returns template

## NotificationController

**Class Purpose**: Handles notification management, including sending, fetching, marking as read, and deleting notifications.

**Key Methods**:
- `viewNotifications()`
- `getUserNotifications()`
- `fetchNotificationsAjax()`
- `markNotificationAsRead()`
- `deleteNotification()`
- `sendNotification()`

**HTTP Routes**:
- `GET /notifications`: View notifications
- `GET /notifications/user`: Get user notifications
- `GET /notifications/ajax`: Fetch notifications (AJAX)
- `POST /notifications/mark-read`: Mark notification as read
- `POST /notifications/delete`: Delete notification
- `POST /notifications/send`: Send notification

**Parameters & Responses**:
- `viewNotifications`: (GET), returns notifications
- `getUserNotifications`: (GET), returns notifications
- `fetchNotificationsAjax`: (GET), returns notifications
- `markNotificationAsRead`: (POST, body: notification_id), returns success/error
- `deleteNotification`: (POST, body: notification_id), returns success/error
- `sendNotification`: (POST, body: user_id, type, message, options), returns success/error

## PaymentController

**Class Purpose**: Handles payment processing, refunds, and user transactions.

**Key Methods**:
- `processPayment()`
- `refundPayment()`
- `getUserTransactions()`
- `getPaymentDetails(int $transactionId)`
- `addPaymentMethod()`
- `getUserPaymentMethods()`
- `processGatewayPayment()`
- `handleGatewayCallback(string $gateway)`

**HTTP Routes**:
- `POST /payments/process`: Process payment
- `POST /payments/refund`: Refund payment
- `GET /payments/transactions`: Get user transactions
- `GET /payments/{transactionId}`: Get payment details
- `POST /payments/methods`: Add payment method
- `GET /payments/methods`: Get user payment methods
- `POST /payments/gateway/process`: Process gateway payment
- `POST/GET /payments/gateway/callback/{gateway}`: Handle gateway callback

**Parameters & Responses**:
- `processPayment`: (POST, body: booking_id, amount, payment_method_id, currency), returns success/error
- `refundPayment`: (POST, body: payment_id, amount, reason), returns success/error
- `getUserTransactions`: (GET), returns transactions
- `getPaymentDetails`: (GET, path: transactionId), returns payment details
- `addPaymentMethod`: (POST, body: type, card_last4, card_brand, expiry_date, is_default), returns success/error
- `getUserPaymentMethods`: (GET), returns payment methods
- `processGatewayPayment`: (POST, body: gateway, booking_id, amount, currency), returns success/error
- `handleGatewayCallback`: (POST/GET, path: gateway, body/query params), returns success/error

## ReportController

**Class Purpose**: Handles report generation and management.

**Key Methods**:
- `index()`
- `generateReport()`
- `userReports()`
- `generateUserReport()`

**HTTP Routes**:
- `GET /reports`: Admin report dashboard
- `POST /reports/generate`: Generate report
- `GET /reports/user`: User report dashboard
- `POST /reports/user/generate`: Generate user report

**Parameters & Responses**:
- `index`: (GET), returns report dashboard
- `generateReport`: (POST, body: date_range, format, report_type, filters), returns report file
- `userReports`: (GET), returns user report dashboard
- `generateUserReport`: (POST, body: user_id, date_range, format, report_type), returns report file

## SignatureController

**Class Purpose**: Handles the management of user signatures.

**Key Methods**:
- `uploadSignature(array $data)`
- `verifySignature(int $userId, string $documentHash)`
- `getSignature(int $userId)`

**HTTP Routes**:
- `POST /signatures/upload`: Upload signature
- `POST /signatures/verify/{userId}`: Verify signature
- `GET /signatures/{userId}`: Get signature

**Parameters & Responses**:
- `uploadSignature`: (POST, body: user_id, file), returns success/error
- `verifySignature`: (POST, path: userId, body: documentHash), returns success/error
- `getSignature`: (GET, path: userId), returns signature

## UserController

**Class Purpose**: Handles user management operations such as registration, profile management, and password resets.

**Key Methods**:
- `registerUser(Request $request, Response $response)`
- `getUserProfile(Request $request, Response $response)`
- `updateProfile(Request $request, Response $response)`
- `requestPasswordReset(Request $request, Response $response)`
- `resetPassword(Request $request, Response $response)`
- `userDashboard(Request $request, Response $response)`

**HTTP Routes**:
- `POST /users/register`: Register user
- `GET /users/profile`: Get user profile
- `PUT /users/profile`: Update profile
- `POST /users/password/request`: Request password reset
- `POST /users/password/reset`: Reset password
- `GET /users/dashboard`: User dashboard

**Parameters & Responses**:
- `registerUser`: (POST, body: email, password, name), returns success/error
- `getUserProfile`: (GET), returns user profile
- `updateProfile`: (PUT, body: name, bio, location, avatar_url), returns success/error
- `requestPasswordReset`: (POST, body: email), returns success/error
- `resetPassword`: (POST, body: token, password), returns success/error
- `userDashboard`: (GET), returns user dashboard
