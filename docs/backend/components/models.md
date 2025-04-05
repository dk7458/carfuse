# Models

## Overview
The CarFuse application uses a collection of models that represent the core business entities and their relationships. Each model encapsulates business logic and data operations for its respective domain, following a clean separation of concerns architecture.

## Base Models

### BaseModel
**Purpose**: Serves as the parent class for all models, providing common CRUD operations and utility methods.

**Fields**:
| Field | Type | Description |
|-------|------|-------------|
| $table | string | The database table name |
| $resourceName | string | Resource name for audit logging |
| $useTimestamps | bool | Whether model uses timestamps |
| $useSoftDeletes | bool | Whether model uses soft deletes |
| $useUuid | bool | Whether model uses UUID as primary key |
| $fillable | array | Fields that can be mass assigned |
| $casts | array | Field type casting definitions |

**Key Methods**:
- `find(int|string $id)`: Retrieves a record by ID
- `findBy(string $field, $value)`: Finds records by field value
- `all(array $orderBy, ?int $limit, ?int $offset)`: Gets all records with optional pagination
- `create(array $data)`: Creates a new record
- `update(int|string $id, array $data)`: Updates an existing record
- `delete(int|string $id)`: Deletes a record (soft delete if enabled)

### BaseFinancialModel
**Purpose**: Extends BaseModel with specialized functionality for financial data handling, including encryption of sensitive information.

**Additional Fields**:
| Field | Type | Description |
|-------|------|-------------|
| $encryptedFields | array | Fields that should be encrypted in database |

**Key Methods**:
- `encryptSensitiveData(array $data)`: Encrypts sensitive fields before storage
- `decryptSensitiveData(array $data)`: Decrypts sensitive fields after retrieval
- `recordAuditEvent(string $action, array $data, ?int $userId)`: Records security events in audit log

## Core Models

### User
**Purpose**: Represents a user in the system with their associated data and relationships.

**Fields**:
| Field | Type | Description |
|-------|------|-------------|
| id | string (UUID) | Primary key |
| name | string | User's first name |
| surname | string | User's last name |
| email | string | User's email address |
| password_hash | string | Hashed password |
| role | string | User role (user, admin, super_admin) |
| phone | string | Phone number |
| address | string | Physical address |
| status | string | User status |
| created_at | DateTime | Creation timestamp |
| updated_at | DateTime | Last update timestamp |
| deleted_at | DateTime | Soft delete timestamp |

**Relationships**:
- One-to-Many with Booking
- One-to-Many with Payment
- One-to-Many with Signature
- One-to-Many with TransactionLog

**Key Methods**:
- `createWithDefaultRole(array $data)`: Creates a new user with role assignment and password hashing
- `updateWithPasswordHandling(string|int $id, array $data)`: Updates user data with password handling
- `getBookings(string $userId)`: Gets user's bookings
- `getPayments(int $userId)`: Gets user's payments
- `getTransactions(int $userId)`: Gets user's transactions

### Vehicle
**Purpose**: Represents a vehicle in the system with its details and status.

**Fields**:
| Field | Type | Description |
|-------|------|-------------|
| registration_number | string | Vehicle registration plate |
| type | string | Vehicle type |
| status | string | Vehicle status (available, unavailable, maintenance) |
| make | string | Vehicle manufacturer |
| model | string | Vehicle model |
| year | integer | Manufacturing year |

**Relationships**:
- One-to-Many with Booking

**Key Methods**:
- `findAvailable()`: Finds all available vehicles
- `findByType(string $type)`: Finds vehicles by type

### Booking
**Purpose**: Represents a booking of a vehicle by a user.

**Fields**:
| Field | Type | Description |
|-------|------|-------------|
| user_id | int | ID of the user who made the booking |
| vehicle_id | int | ID of the vehicle being booked |
| pickup_date | datetime | Date and time of vehicle pickup |
| dropoff_date | datetime | Date and time of vehicle return |
| status | string | Booking status (pending, confirmed, cancelled, completed) |
| created_at | datetime | Creation timestamp |
| updated_at | datetime | Last update timestamp |
| deleted_at | datetime | Soft delete timestamp |

**Relationships**:
- Many-to-One with User
- Many-to-One with Vehicle
- One-to-Many with Payment
- One-to-One with Contract

**Key Methods**:
- `updateStatus(int|string $id, string $newStatus)`: Updates booking status
- `getActive()`: Gets all active bookings
- `getByUser(int|string $userId)`: Gets bookings by user ID
- `getByStatus(string $status)`: Gets bookings by status
- `getByDateRange(string $start, string $end, array $filters)`: Gets bookings within a date range
- `isVehicleAvailable(int|string $vehicleId, string $startDate, string $endDate)`: Checks if vehicle is available for booking

### Payment
**Purpose**: Represents a payment transaction in the system.

**Fields**:
| Field | Type | Description |
|-------|------|-------------|
| user_id | int | ID of the user who made the payment |
| booking_id | int | ID of the associated booking |
| amount | float | Transaction amount |
| method | string | Payment method (credit_card, paypal, bank_transfer) |
| status | string | Status of payment (pending, completed, failed) |
| transaction_id | string | Unique external transaction identifier |
| type | string | Type of transaction (payment or refund) |
| refund_reason | string | Reason for refund, if applicable |
| original_payment_id | int | ID of original payment for refunds |
| created_at | datetime | Creation timestamp |
| updated_at | datetime | Last update timestamp |
| deleted_at | datetime | Soft delete timestamp |

**Relationships**:
- Many-to-One with User
- Many-to-One with Booking

**Key Methods**:
- `createPaymentRecord(array $data)`: Creates a payment record
- `createRefund(array $refundData)`: Creates a refund record
- `updatePaymentRecord(int $id, array $data)`: Updates a payment record
- `getByUser(int $userId)`: Gets payments by user ID
- `getByStatus(string $status)`: Gets payments by status
- `getByBooking(int $bookingId)`: Gets payments by booking ID
- `hasRefunds(int $paymentId)`: Checks if a payment has been refunded
- `getRefundedAmount(int $paymentId)`: Gets total refunded amount

### TransactionLog
**Purpose**: Represents financial transaction log entries with enhanced security for sensitive financial data.

**Fields**:
| Field | Type | Description |
|-------|------|-------------|
| user_id | int | User ID associated with transaction |
| amount | encrypted string | Transaction amount (encrypted) |
| card_number | encrypted string | Card number (encrypted) |
| card_last4 | encrypted string | Last 4 digits of card (encrypted) |
| transaction_type | string | Type of transaction |
| status | string | Transaction status |
| reference | string | Reference number |
| created_at | datetime | Creation timestamp |
| updated_at | datetime | Last update timestamp |
| deleted_at | datetime | Soft delete timestamp |

**Relationships**:
- Many-to-One with User

**Key Methods**:
- `encryptSensitiveData(array $data)`: Encrypts sensitive fields
- `decryptSensitiveData(array $data)`: Decrypts sensitive fields
- `logTransaction(array $transactionData)`: Logs a new financial transaction
- `updateStatus(string|int $transactionId, string $status)`: Updates transaction status
- `getForUser(int|string $userId)`: Gets transactions for a specific user
- `getByReference(string $reference)`: Gets transactions by reference number
- `getByDateRange(string $startDate, string $endDate)`: Gets transactions within a date range

### Contract
**Purpose**: Handles contract specific database operations.

**Fields**:
| Field | Type | Description |
|-------|------|-------------|
| booking_id | int | Associated booking ID |
| user_id | int | Associated user ID |
| content | text | Contract content |
| status | string | Contract status |

**Relationships**:
- One-to-One with Booking
- Many-to-One with User

**Key Methods**:
- `getByBookingId(int $bookingId)`: Gets contract by booking ID
- `getByUserId(int $userId)`: Gets contracts by user ID

### DocumentTemplate
**Purpose**: Manages templates for documents such as contracts, invoices, and Terms & Conditions.

**Fields**:
| Field | Type | Description |
|-------|------|-------------|
| name | string | Template name |
| content | text | Template content |
| description | string | Template description |
| file_path | string | Path to template file |
| created_at | datetime | Creation timestamp |
| updated_at | datetime | Last update timestamp |
| deleted_at | datetime | Soft delete timestamp |

**Key Methods**:
- `findByName(string $name)`: Finds a template by its name

### Signature
**Purpose**: Manages electronic signatures for documents and contracts.

**Fields**:
| Field | Type | Description |
|-------|------|-------------|
| user_id | int | Associated user ID |
| signature | encrypted string | Signature data (encrypted) |
| file_path | string | Path to signature file |
| encrypted | bool | Whether signature is encrypted |
| created_at | datetime | Creation timestamp |

**Relationships**:
- Many-to-One with User

**Key Methods**:
- `getSignature(int $signatureId)`: Gets the signature
- `getUser(int $signatureId)`: Gets the user associated with the signature
- `storeSignaturePath(int $userId, string $filePath, bool $encrypted)`: Stores signature file path
- `getSignaturesByUserId(int $userId)`: Gets signatures by user ID
- `getSignaturePathByUserId(int $userId)`: Gets signature path by user ID

### Admin
**Purpose**: Manages system administrators.

**Fields**:
| Field | Type | Description |
|-------|------|-------------|
| name | string | Admin name |
| email | string | Admin email |
| password | string | Admin password (hashed) |
| role | string | Admin role |
| token | string | Authentication token |
| token_expiry | datetime | Token expiration time |
| created_at | datetime | Creation timestamp |
| updated_at | datetime | Last update timestamp |
| deleted_at | datetime | Soft delete timestamp |

**Key Methods**:
- `hashPassword(string $password)`: Hashes a password
- `verifyPassword(string $plainPassword, string $hashedPassword)`: Verifies password
- `getByEmail(string $email)`: Gets admin by email
- `restore(int|string $id)`: Restores a soft-deleted admin
- `getManagedUsers(int|string $adminId)`: Gets users managed by admin
- `getPermissions(int|string $adminId)`: Gets admin permissions
- `findByToken(string $token)`: Finds admin by token
- `createAdminWithHashedPassword(array $data)`: Creates admin with password hashing
- `updateAdminWithPasswordHandling(int|string $id, array $data)`: Updates admin with password handling

### Report
**Purpose**: Represents an admin report in the system.

**Fields**:
| Field | Type | Description |
|-------|------|-------------|
| admin_id | int | ID of admin who created report |
| title | string | Report title |
| content | text | Report content |
| status | string | Report status |
| created_at | datetime | Creation timestamp |
| updated_at | datetime | Last update timestamp |
| deleted_at | datetime | Soft delete timestamp |

**Relationships**:
- Many-to-One with Admin

**Key Methods**:
- `getByDateRange(string $start, string $end)`: Gets reports within a date range
- `getAdmin(int $reportId)`: Gets the admin who created the report

### AuditLog
**Purpose**: Represents the audit_logs table and provides methods for retrieving, searching, and exporting audit data.

**Fields**:
| Field | Type | Description |
|-------|------|-------------|
| action | string | Action performed |
| message | string | Description of the event |
| log_level | string | Log level (info, warning, error) |
| details | json | Additional details |
| user_id | int | Associated user ID |
| created_at | datetime | When the event occurred |

**Key Methods**:
- `getLogs(array $filters)`: Gets logs with filtering and pagination
- `getById(int $id)`: Gets a single log by ID
- `deleteLogs(array $filters, bool $forceBulkDelete)`: Deletes logs based on criteria
- `exportLogs(array $filters)`: Exports logs to CSV
- `createLog(array $data)`: Creates a new log entry

### AuditTrail
**Purpose**: Represents the audit trails stored in the database and provides methods for accessing and filtering the logs.

**Fields**:
| Field | Type | Description |
|-------|------|-------------|
| user_id | int | User who performed the action |
| booking_id | int | Related booking if applicable |
| action | string | Action performed |
| message | string | Description of the event |
| details | json | Additional details |
| created_at | datetime | When the event occurred |

**Relationships**:
- Many-to-One with User
- Many-to-One with Booking

**Key Methods**:
- `getLogs(array $filters)`: Retrieves audit trail records based on filters

## Relationship Diagram

Below is a simplified representation of relationships between key models:

```
User ─────┬───> Booking <───┬─── Vehicle
         │       │         │
         │       │         │
         ▼       ▼         │
     Payment     │         │
         │       │         │
         │       ▼         │
TransactionLog   Contract   │
         │                 │
         └───> AuditTrail <─┘
                  │
                  │
                  ▼
            DocumentTemplate
                  │
                  │
                  ▼
               Signature
```

## Validation

Models use validation rules defined as static properties (e.g., `$rules`) and validation is typically performed before create/update operations. The Validator service is often used in conjunction with models to enforce data integrity.
