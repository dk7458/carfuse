## Admin

**Class Purpose**: Represents an administrator user in the system.

**Database Table**: `admins`

**Key Properties**:
- `id`: int, primaryKey, autoIncrement
- `name`: string, required
- `email`: string, required, unique
- `password`: string, required
- `role`: string, default 'admin'
- `created_at`: timestamp
- `updated_at`: timestamp

**Relationships**: None

**Important Methods**:
- `findByEmail(string $email)`: Find an admin by email.
- `findById(int $id)`: Find an admin by ID.

## AuditLog

**Class Purpose**: Represents a single audit log entry.

**Database Table**: `audit_logs`

**Key Properties**:
- `id`: int, primaryKey, autoIncrement
- `event_type`: string, required
- `message`: string, required
- `user_id`: int, nullable
- `timestamp`: timestamp
- `details`: text, nullable
- `ip_address`: string, nullable
- `category`: string, required

**Relationships**: None

**Important Methods**:
- `getLogs(array $filters)`: Get logs based on filters.
- `getById(int $id)`: Get log by ID.

## AuditTrail

**Class Purpose**: This model does not exist in the provided file list.

## BaseFinancialModel

**Class Purpose**: This model does not exist in the provided file list.

## BaseModel

**Class Purpose**: Provides base functionality for all models.

**Database Table**: N/A (Abstract Class)

**Key Properties**: N/A (Abstract Class)

**Relationships**: N/A (Abstract Class)

**Important Methods**: N/A (Abstract Class)

## Booking

**Class Purpose**: Represents a booking record in the system.

**Database Table**: `bookings`

**Key Properties**:
- `id`: int, primaryKey, autoIncrement
- `user_id`: int, required
- `vehicle_id`: int, required
- `pickup_date`: datetime, required
- `dropoff_date`: datetime, required
- `status`: string, default 'pending'
- `created_at`: timestamp
- `updated_at`: timestamp

**Relationships**:
- `belongsTo`: User

**Important Methods**:
- `find(int $id)`: Find a booking by ID.
- `getByUser(int $userId)`: Get bookings by user ID.

## Contract

**Class Purpose**: Represents a contract associated with a booking.

**Database Table**: `contracts`

**Key Properties**:
- `id`: int, primaryKey, autoIncrement
- `booking_id`: int, required
- `user_id`: int, required
- `contract_pdf`: string, required
- `created_at`: timestamp

**Relationships**:
- `belongsTo`: Booking, User

**Important Methods**:
- `getByBookingId(int $bookingId)`: Get contract by booking ID.
- `getByUserId(int $userId)`: Get contracts by user ID.

## Document

**Class Purpose**: Represents a generic document stored in the system.

**Database Table**: `documents`

**Key Properties**:
- `id`: int, primaryKey, autoIncrement
- `name`: string, required
- `file_path`: string, required
- `created_at`: timestamp

**Relationships**: None

**Important Methods**:
- `find(int $id)`: Find a document by ID.

## DocumentTemplate

**Class Purpose**: Represents a template for generating documents.

**Database Table**: `document_templates`

**Key Properties**:
- `id`: int, primaryKey, autoIncrement
- `name`: string, required
- `content`: text, required
- `created_at`: timestamp
- `updated_at`: timestamp

**Relationships**: None

**Important Methods**:
- `find(int $id)`: Find a template by ID.
- `findByName(string $name)`: Find a template by name.

## Notification

**Class Purpose**: Represents a notification sent to a user.

**Database Table**: `notifications`

**Key Properties**:
- `id`: int, primaryKey, autoIncrement
- `user_id`: int, required
- `type`: string, required
- `message`: text, required
- `sent_at`: timestamp
- `is_read`: boolean, default false

**Relationships**:
- `belongsTo`: User

**Important Methods**:
- `getByUserId(int $userId)`: Get notifications by user ID.

## PasswordReset

**Class Purpose**: Represents a password reset request.

**Database Table**: `password_resets`

**Key Properties**:
- `id`: int, primaryKey, autoIncrement
- `email`: string, required
- `token`: string, required
- `created_at`: timestamp

**Relationships**: None

**Important Methods**:
- `verifyToken(string $token)`: Verifies a password reset token.
- `markTokenUsed(string $token)`: Marks a password reset token as used.

## PaymentMethod

**Class Purpose**: Represents a user's payment method.

**Database Table**: `payment_methods`

**Key Properties**:
- `id`: int, primaryKey, autoIncrement
- `user_id`: int, required
- `payment_type`: string, required
- `card_last4`: string, nullable
- `card_brand`: string, nullable
- `expiry_date`: string, nullable
- `is_active`: boolean, default true
- `is_default`: boolean, default false

**Relationships**:
- `belongsTo`: User

**Important Methods**:
- `getByUser(int $userId)`: Get payment methods by user ID.
- `getById(int $id)`: Get payment method by ID.

## Payment

**Class Purpose**: Represents a payment transaction.

**Database Table**: `payments`

**Key Properties**:
- `id`: int, primaryKey, autoIncrement
- `user_id`: int, required
- `booking_id`: int, nullable
- `amount`: decimal, required
- `method`: string, required
- `status`: string, required
- `transaction_id`: string, nullable
- `created_at`: timestamp

**Relationships**:
- `belongsTo`: User, Booking

**Important Methods**:
- `find(int $id)`: Find a payment by ID.
- `getByUserAndDateRange(int $userId, string $startDate, string $endDate)`: Get payments by user and date range.

## RefundLog

**Class Purpose**: Represents a refund transaction.

**Database Table**: `refund_logs`

**Key Properties**:
- `id`: int, primaryKey, autoIncrement
- `booking_id`: int, required
- `amount`: decimal, required
- `status`: string, required
- `created_at`: timestamp

**Relationships**:
- `belongsTo`: Booking

**Important Methods**:
- `create(array $data)`: Creates a new refund log entry.

## TransactionLog

**Class Purpose**: Represents a log of a transaction.

**Database Table**: `transaction_logs`

**Key Properties**:
- `id`: int, primaryKey, autoIncrement
- `payment_id`: int, nullable
- `booking_id`: int, nullable
- `user_id`: int, nullable
- `amount`: decimal, nullable
- `currency`: string, nullable
- `status`: string, required
- `type`: string, required
- `description`: string, nullable
- `gateway`: string, nullable
- `gateway_transaction_id`: string, nullable
- `created_at`: timestamp

**Relationships**: None

**Important Methods**:
- `getByUserId(int $userId)`: Get transaction logs by user ID.

## Report

This model does not exist in the provided file list.

## Signature

**Class Purpose**: Represents a user's electronic signature.

**Database Table**: `signatures`

**Key Properties**:
- `id`: int, primaryKey, autoIncrement
- `user_id`: int, required
- `file_path`: string, required
- `created_at`: timestamp

**Relationships**:
- `belongsTo`: User

**Important Methods**:
- `getSignaturePathByUserId(int $userId)`: Get signature path by user ID.

## RefreshToken

**Class Purpose**: Represents a refresh token for user authentication.

**Database Table**: `refresh_tokens`

**Key Properties**:
- `id`: int, primaryKey, autoIncrement
- `user_id`: int, required
- `token`: string, required, unique
- `expires_at`: timestamp
- `created_at`: timestamp
- `revoked`: boolean, default false

**Relationships**:
- `belongsTo`: User

**Important Methods**:
- `revoke(string $token)`: Revokes a refresh token.
- `getActiveForUser(int $userId)`: Get active tokens for a user.

## User

**Class Purpose**: Represents a user account in the system.

**Database Table**: `users`

**Key Properties**:
- `id`: int, primaryKey, autoIncrement
- `name`: string, required
- `email`: string, required, unique
- `password_hash`: string, required
- `role`: string, default 'user'
- `created_at`: timestamp
- `updated_at`: timestamp

**Relationships**:
- `hasMany`: Booking, PaymentMethod, Notification

**Important Methods**:
- `find(int $id)`: Find a user by ID.
- `findByEmail(string $email)`: Find a user by email.

