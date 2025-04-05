# Payment Processing Sequence

## Overview
The payment processing system in CarFuse handles securely processing customer payments with comprehensive fraud detection, transaction logging, and error handling. The system uses a multi-layered approach to ensure payment integrity and security.

## Payment Processing Sequence Diagram

```
┌──────────┐      ┌────────────────┐     ┌──────────────────────┐      ┌───────────────┐
│  Client  │─────▶│Payment API     │────▶│EncryptionMiddleware  │─────▶│AuthMiddleware │
└──────────┘      └────────────────┘     └──────────────────────┘      └───────────────┘
      │                    │                        │                          │
      │                    │                        │                          │
      │                    ▼                        │                          │
      │          ┌──────────────────┐               │                          │
      │          │PaymentController │               │                          │
      │          └──────────────────┘               │                          │
      │                    │                        │                          │
      │                    ▼                        │                          │
      │          ┌──────────────────┐               │                          │
      │          │Validator         │               │                          │
      │          └──────────────────┘               │                          │
      │                    │                        │                          │
      │                    ▼                        │                          │
      │      ┌────────────────────────┐             │                          │
      │      │PaymentProcessingService│◀────────────┘                          │
      │      └────────────────────────┘                                        │
      │                    │                                                   │
      │                    ▼                                                   │
      │      ┌────────────────────────┐                                        │
      │      │Fraud Validation        │                                        │
      │      └────────────────────────┘                                        │
      │                    │                                                   │
      │                    │                                                   │
      │                    ▼                                                   │
      │      ┌────────────────────────┐         ┌──────────────────────┐       │
      │      │Payment Gateway Client  │━━━━━━━━▶│External Payment      │       │
      │      │                        │◀━━━━━━━━│Gateway (API)         │       │
      │      └────────────────────────┘         └──────────────────────┘       │
      │                    │                                                   │
      │                    ▼                                                   │
      │      ┌────────────────────────┐         ┌──────────────────────┐       │
      │      │DatabaseHelper          │━━━━━━━━▶│Database              │       │
      │      │(Transaction)           │◀━━━━━━━━│(Multiple Tables)     │       │
      │      └────────────────────────┘         └──────────────────────┘       │
      │                    │                                                   │
      │                    ▼                                                   │
      │      ┌────────────────────────┐                                        │
      │      │NotificationService     │                                        │
      │      └────────────────────────┘                                        │
      │                    │                                                   │
      │                    ▼                                                   │
      │      ┌────────────────────────┐                                        │
      │      │AuditService            │◀──────────────────────────────────────┘
      │      └────────────────────────┘
      │                    │
      ▼                    ▼
┌──────────┐      ┌────────────────┐
│  Client  │◀─────│Response        │
└──────────┘      └────────────────┘
```

## Payment Processing Stages

### 1. Request & Security Processing
- Client submits payment request with booking ID, amount, and payment method
- Request passes through security middleware:
  - EncryptionMiddleware decrypts sensitive payment data
  - AuthMiddleware authenticates the user making the payment
  - CSRF validation ensures request legitimacy
- Encrypted channel (HTTPS) ensures data security in transit

### 2. Payment Validation
- PaymentController receives the payment request
- Validator component performs validation:
  - Required fields are present (amount, booking_id, etc.)
  - Amount matches the expected booking amount
  - Payment method is valid and supported
  - User has permission to pay for this booking
  - Booking status allows payment processing

### 3. Fraud Detection
- PaymentProcessingService performs fraud checks before processing:
  - Transaction velocity checks (multiple transactions in short time)
  - Payment amount validation against booking cost
  - IP reputation analysis for suspicious sources
  - User account risk scoring based on history
  - Geolocation validation when available
  - Device fingerprint verification for repeat customers

### 4. Payment Gateway Communication
- For declined fraud checks:
  - Payment is rejected with reason code
  - Security logs are generated
  - AuditService records attempted fraud
- For approved transactions:
  - Payment Gateway Client initiates external API call
  - Payment credentials sent to gateway
  - Gateway responds with authorization/rejection
  - Payment details never stored in plaintext

### 5. Database Transaction
- For successful payments:
  - DatabaseHelper begins a transaction
  - Payment record created
  - Booking status updated
  - Transaction log entry created
  - Transaction is committed
- For failed payments:
  - Error logged
  - Failure reason recorded
  - Transaction rolled back

### 6. Post-Payment Processing
- NotificationService alerts:
  - Customer of successful payment (email receipt)
  - Admins of payment status (dashboard updates)
  - Accounting system of received payment
- AuditService creates comprehensive audit trail

## Database Tables Involved

1. **payments**: Core payment records
   - payment_id
   - booking_id
   - user_id
   - amount
   - currency
   - payment_method
   - status
   - gateway_reference
   - created_at
   - updated_at

2. **transaction_logs**: Detailed payment processing logs
   - id
   - payment_id
   - type (payment/refund/chargeback)
   - status
   - description
   - gateway_response
   - ip_address
   - created_at

3. **bookings**: Updated with payment status
   - booking_id
   - payment_status
   - last_payment_id
   - updated_at

## Error Handling & Recovery

### Error Scenarios
- Gateway timeout: Retry policy with exponential backoff
- Gateway decline: Specific reason code returned to user
- System error: Transaction rolled back, error logged
- Partial payment: Support for multiple payments against booking

### Security Measures
- Payment data encrypted both in transit and at rest
- Payment card details never stored in database
- Only last 4 digits of card stored for reference
- All payment actions require authentication and authorization
- IP-based rate limiting on payment endpoints
- Full PCI-DSS compliance for payment handling
