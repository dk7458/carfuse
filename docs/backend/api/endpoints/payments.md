# Payments API Endpoints

## Overview

The Payments API provides endpoints for processing payments, refunds, and managing payment methods. This API also handles retrieving transaction history, generating invoices, and interacting with external payment gateways.

## Authentication and Permissions

| Endpoint Pattern                     | Required Role | Notes                                      |
|-------------------------------------|---------------|-------------------------------------------|
| `POST /payments/process`             | User          | Process a payment                          |
| `POST /payments/refund`              | Admin         | Process a refund (admin only)              |
| `GET /payments/user`                 | User          | Get user's transaction history            |
| `GET /payments/{id}`                 | User          | Get payment details                       |
| `POST /payments/methods`             | User          | Add a payment method                      |
| `GET /payments/methods`              | User          | Get user's payment methods                |
| `DELETE /payments/methods/{id}`      | User          | Delete a payment method                   |
| `POST /payments/gateway`             | User          | Process payment via gateway               |
| `POST /payments/gateway/{gateway}/callback` | None   | Handle gateway callback (no auth required) |
| `GET /payments/invoice/{id}`         | User          | Download payment invoice                  |

## Rate Limiting

Payment endpoints have stricter rate limits to prevent abuse:
- Standard tier: 30 requests per minute
- Premium tier: 60 requests per minute
- Payment processing: 10 requests per hour per user
- Invoice generation: 20 requests per hour per user

---

## Process Payment

Process a payment for a booking.

### HTTP Request

`POST /payments/process`

### Authentication

Requires a valid user authentication token.

### Request Body Parameters

| Parameter          | Type    | Required | Description                  | Constraints                       |
|--------------------|---------|----------|------------------------------|----------------------------------|
| `booking_id`       | Integer | Yes      | ID of the booking            | Must be a valid booking ID        |
| `amount`           | Number  | Yes      | Payment amount               | Must be greater than 0.01        |
| `payment_method_id`| Integer | Yes      | ID of the payment method     | Must be a valid payment method ID |
| `currency`         | String  | No       | 3-letter currency code       | Default: system default (e.g., USD)|

### Example Request

```json
{
  "booking_id": 456,
  "amount": 349.99,
  "payment_method_id": 123,
  "currency": "USD"
}
```

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Payment processed",
  "data": {
    "payment": {
      "id": 789,
      "amount": 349.99,
      "currency": "USD",
      "status": "completed",
      "transaction_id": "txn_123456789",
      "created_at": "2023-06-15T14:30:00Z"
    }
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                    |
|-------------|--------------------------|------------------------------------------------|
| 400         | `VALIDATION_ERROR`       | Invalid request parameters                      |
| 400         | `PAYMENT_AMOUNT_MISMATCH`| Payment amount does not match booking amount   |
| 400         | `BOOKING_ALREADY_PAID`   | Booking is already paid for                    |
| 400         | `INVALID_PAYMENT_METHOD` | Payment method is invalid or expired           |
| 401         | `UNAUTHORIZED`           | User not authenticated                         |
| 403         | `PERMISSION_DENIED`      | User cannot access this booking                |
| 404         | `BOOKING_NOT_FOUND`      | Booking not found                              |
| 500         | `PAYMENT_FAILED`         | Payment processing failed                      |

### Notes

- Payment confirmation notification is sent on success
- Transaction is logged in the system
- Payment method ownership is validated before processing
- Multiple payments for the same booking are prevented

---

## Process Refund

Process a refund for an existing payment. Admin access required.

### HTTP Request

`POST /payments/refund`

### Authentication

Requires a valid admin authentication token.

### Request Body Parameters

| Parameter    | Type    | Required | Description                  | Constraints                       |
|--------------|---------|----------|------------------------------|----------------------------------|
| `payment_id` | Integer | Yes      | ID of the payment to refund  | Must be a valid payment ID        |
| `amount`     | Number  | Yes      | Refund amount                | Must be > 0 and <= original amount|
| `reason`     | String  | Yes      | Reason for the refund        | Non-empty string                  |

### Example Request

```json
{
  "payment_id": 789,
  "amount": 349.99,
  "reason": "Booking cancellation"
}
```

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Refund processed",
  "data": {
    "refund": {
      "id": 123,
      "payment_id": 789,
      "amount": 349.99,
      "currency": "USD",
      "reason": "Booking cancellation",
      "status": "completed",
      "transaction_id": "ref_987654321",
      "created_at": "2023-06-16T10:22:18Z"
    }
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                    |
|-------------|--------------------------|------------------------------------------------|
| 400         | `VALIDATION_ERROR`       | Invalid request parameters                      |
| 400         | `REFUND_AMOUNT_EXCESSIVE`| Refund amount exceeds original payment         |
| 400         | `PAYMENT_NOT_REFUNDABLE` | Payment is not refundable                      |
| 401         | `UNAUTHORIZED`           | User not authenticated                         |
| 403         | `PERMISSION_DENIED`      | User does not have admin rights                |
| 404         | `PAYMENT_NOT_FOUND`      | Payment not found                              |
| 500         | `REFUND_FAILED`          | Refund processing failed                       |

### Notes

- Refund notification is sent to affected user
- Refund is recorded in the transaction history
- Original payment is linked to the refund record
- Audit logs are created for refund operations

---

## Get User Transaction History

Retrieve the authenticated user's transaction history.

### HTTP Request

`GET /payments/user`

### Authentication

Requires a valid user authentication token.

### Query Parameters

| Parameter | Type    | Required | Description                   | Constraints                        |
|-----------|---------|----------|------------------------------ |-----------------------------------|
| `page`    | Integer | No       | Page number for pagination    | Default: 1, Min: 1                |
| `limit`   | Integer | No       | Number of items per page      | Default: 20, Max: 100             |
| `type`    | String  | No       | Filter by transaction type    | Values: payment, refund           |
| `status`  | String  | No       | Filter by transaction status  | Values: pending, completed, failed|
| `from_date`| String | No       | Filter by minimum date        | ISO 8601 format                   |
| `to_date` | String  | No       | Filter by maximum date        | ISO 8601 format                   |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Transactions fetched",
  "data": [
    {
      "id": 789,
      "booking_id": 456,
      "type": "payment",
      "amount": 349.99,
      "currency": "USD",
      "status": "completed",
      "method": "credit_card",
      "card_last4": "4242",
      "transaction_id": "txn_123456789",
      "created_at": "2023-06-15T14:30:00Z"
    },
    {
      "id": 790,
      "booking_id": 457,
      "type": "payment",
      "amount": 249.99,
      "currency": "USD",
      "status": "pending",
      "method": "paypal",
      "transaction_id": "txn_987654321",
      "created_at": "2023-06-16T09:22:18Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total_pages": 3,
    "total_items": 45,
    "per_page": 20
  }
}
```

### Error Codes

| Status Code | Error Code         | Description                                    |
|-------------|-------------------|------------------------------------------------|
| 401         | `UNAUTHORIZED`     | User not authenticated                         |
| 500         | `SERVER_ERROR`     | Failed to fetch transactions                   |

### Notes

- Results are typically sorted by date (newest first)
- Sensitive payment details are partially masked
- Response includes pagination metadata
- Transaction history access is logged for audit purposes

---

## Get Payment Details

Retrieve detailed information for a specific payment.

### HTTP Request

`GET /payments/{id}`

### Authentication

Requires a valid user authentication token.

### Path Parameters

| Parameter | Type    | Required | Description        | Constraints                   |
|-----------|---------|----------|--------------------|------------------------------|
| `id`      | Integer | Yes      | Payment identifier | Must be a valid payment ID    |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Payment details fetched",
  "data": {
    "details": {
      "id": 789,
      "booking_id": 456,
      "user_id": 123,
      "type": "payment",
      "amount": 349.99,
      "currency": "USD",
      "status": "completed",
      "method": "credit_card",
      "card_brand": "Visa",
      "card_last4": "4242",
      "transaction_id": "txn_123456789",
      "reference": "BOOK-456-PAY",
      "description": "Payment for booking #456",
      "metadata": {
        "booking_date": "2023-07-15T10:00:00Z",
        "vehicle": "Toyota Camry"
      },
      "created_at": "2023-06-15T14:30:00Z",
      "updated_at": "2023-06-15T14:30:45Z",
      "booking": {
        "id": 456,
        "pickup_date": "2023-07-15T10:00:00Z",
        "dropoff_date": "2023-07-18T10:00:00Z",
        "vehicle_id": 789
      },
      "refunds": []
    }
  }
}
```

### Error Codes

| Status Code | Error Code         | Description                                    |
|-------------|-------------------|------------------------------------------------|
| 401         | `UNAUTHORIZED`     | User not authenticated                         |
| 403         | `PERMISSION_DENIED`| User does not have permission for this payment |
| 404         | `PAYMENT_NOT_FOUND`| Payment not found                              |
| 500         | `SERVER_ERROR`     | Failed to retrieve payment details             |

### Notes

- Users can only view their own payment details
- Admins can view all payment details
- Permission checking handles owner verification
- Includes linked booking information

---

## Add Payment Method

Add a new payment method to the user's account.

### HTTP Request

`POST /payments/methods`

### Authentication

Requires a valid user authentication token.

### Request Body Parameters

| Parameter       | Type    | Required | Description                      | Constraints                       |
|-----------------|---------|----------|----------------------------------|----------------------------------|
| `type`          | String  | Yes      | Type of payment method           | Values: credit_card, paypal, etc. |
| `card_last4`    | String  | Conditional | Last 4 digits of card         | Required if type is credit_card   |
| `card_brand`    | String  | Conditional | Card brand                    | Required if type is credit_card   |
| `expiry_date`   | String  | Conditional | Card expiry date (MM/YY)      | Required if type is credit_card   |
| `is_default`    | Boolean | No       | Set as default payment method    | Default: false                    |

### Example Request

```json
{
  "type": "credit_card",
  "card_last4": "4242",
  "card_brand": "Visa",
  "expiry_date": "12/25",
  "is_default": true
}
```

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Payment method added successfully",
  "data": {
    "payment_method": {
      "id": 123,
      "type": "credit_card",
      "card_last4": "4242",
      "card_brand": "Visa",
      "expiry_date": "12/25",
      "is_default": true,
      "created_at": "2023-06-16T14:30:00Z"
    }
  }
}
```

### Error Codes

| Status Code | Error Code                  | Description                                    |
|-------------|----------------------------|------------------------------------------------|
| 400         | `VALIDATION_ERROR`         | Invalid request parameters                      |
| 400         | `INVALID_PAYMENT_TYPE`     | Unsupported payment method type                 |
| 401         | `UNAUTHORIZED`             | User not authenticated                          |
| 500         | `METHOD_CREATION_FAILED`   | Failed to add payment method                    |

### Notes

- If set as default, other payment methods are automatically un-set as default
- Actual card data should not be sent directly to this API; use tokenization
- Sensitive payment details are securely stored according to PCI standards

---

## Get User Payment Methods

Retrieve all payment methods for the authenticated user.

### HTTP Request

`GET /payments/methods`

### Authentication

Requires a valid user authentication token.

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Payment methods retrieved successfully",
  "data": {
    "payment_methods": [
      {
        "id": 123,
        "type": "credit_card",
        "card_last4": "4242",
        "card_brand": "Visa",
        "expiry_date": "12/25",
        "is_default": true,
        "created_at": "2023-06-16T14:30:00Z"
      },
      {
        "id": 124,
        "type": "paypal",
        "email": "user@example.com",
        "is_default": false,
        "created_at": "2023-06-17T10:22:18Z"
      }
    ]
  }
}
```

### Error Codes

| Status Code | Error Code         | Description                                    |
|-------------|-------------------|------------------------------------------------|
| 401         | `UNAUTHORIZED`     | User not authenticated                         |
| 500         | `SERVER_ERROR`     | Failed to retrieve payment methods             |

### Notes

- Methods are sorted with default method first, then by creation date
- Sensitive payment information is partially masked
- The method count is not limited, but UI may limit display

---

## Delete Payment Method

Delete a payment method from the user's account.

### HTTP Request

`DELETE /payments/methods/{id}`

### Authentication

Requires a valid user authentication token.

### Path Parameters

| Parameter | Type    | Required | Description               | Constraints                       |
|-----------|---------|----------|---------------------------|----------------------------------|
| `id`      | Integer | Yes      | Payment method identifier | Must be a valid payment method ID |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Payment method deleted successfully"
}
```

### Error Codes

| Status Code | Error Code               | Description                                         |
|-------------|-------------------------|-----------------------------------------------------|
| 401         | `UNAUTHORIZED`          | User not authenticated                               |
| 403         | `PERMISSION_DENIED`     | Payment method doesn't belong to authenticated user  |
| 404         | `METHOD_NOT_FOUND`      | Payment method not found                             |
| 500         | `SERVER_ERROR`          | Failed to delete payment method                      |

### Notes

- If the default payment method is deleted, another method will be set as default if available
- Methods in use for recurring payments may require additional steps before deletion
- Deletion is logged for audit purposes

---

## Process Gateway Payment

Initiate a payment using a specific payment gateway.

### HTTP Request

`POST /payments/gateway`

### Authentication

Requires a valid user authentication token.

### Request Body Parameters

| Parameter    | Type    | Required | Description                      | Constraints                       |
|--------------|---------|----------|----------------------------------|----------------------------------|
| `gateway`    | String  | Yes      | Payment gateway to use           | Values: stripe, paypal, payu     |
| `booking_id` | Integer | Yes      | ID of the booking                | Must be a valid booking ID        |
| `amount`     | Number  | Yes      | Payment amount                   | Must be greater than 0.01        |
| `currency`   | String  | Yes      | 3-letter currency code           | Must be a supported currency code |
| `return_url` | String  | Yes      | URL to return on success         | Valid URL                         |
| `cancel_url` | String  | Yes      | URL to return on cancellation    | Valid URL                         |

### Example Request

```json
{
  "gateway": "stripe",
  "booking_id": 456,
  "amount": 349.99,
  "currency": "USD",
  "return_url": "https://carfuse.example.com/bookings/456/confirmation",
  "cancel_url": "https://carfuse.example.com/bookings/456/payment"
}
```

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Gateway payment initiated",
  "data": {
    "redirect_url": "https://checkout.stripe.com/pay/cs_test_...",
    "session_id": "cs_test_...",
    "expires_at": "2023-06-16T15:30:00Z"
  }
}
```

### Error Codes

| Status Code | Error Code                | Description                                    |
|-------------|--------------------------|------------------------------------------------|
| 400         | `VALIDATION_ERROR`       | Invalid request parameters                      |
| 400         | `UNSUPPORTED_GATEWAY`    | Selected gateway is not supported              |
| 400         | `GATEWAY_CONFIG_ERROR`   | Gateway configuration error                    |
| 401         | `UNAUTHORIZED`           | User not authenticated                         |
| 403         | `PERMISSION_DENIED`      | User cannot access this booking                |
| 404         | `BOOKING_NOT_FOUND`      | Booking not found                              |
| 500         | `GATEWAY_ERROR`          | Payment gateway error                          |

### Notes

- Different gateways may require different parameters
- Session/transaction info is stored for callback validation
- IP address and user agent are recorded for security purposes
- Response structure varies by gateway

---

## Handle Gateway Callback

Process callbacks from payment gateways after payment completion or cancellation.

### HTTP Request

`POST /payments/gateway/{gateway}/callback`

### Path Parameters

| Parameter | Type   | Required | Description            | Constraints                     |
|-----------|--------|----------|------------------------|--------------------------------|
| `gateway` | String | Yes      | Payment gateway name   | Must be a supported gateway     |

### Request Parameters

Parameters vary by gateway but typically include:

| Parameter    | Type   | Location | Description                                 |
|--------------|--------|----------|---------------------------------------------|
| `session_id` | String | Body/Query | Gateway session or transaction ID           |
| `status`     | String | Body/Query | Payment status from gateway                 |
| Various security tokens and signatures specific to each gateway                    |

### Response

Status code: `200 OK`

```json
{
  "status": "success",
  "message": "Callback processed"
}
```

### Error Codes

| Status Code | Error Code            | Description                                    |
|-------------|----------------------|------------------------------------------------|
| 400         | `INVALID_CALLBACK`   | Invalid callback parameters                     |
| 400         | `SIGNATURE_MISMATCH` | Security signature validation failed            |
| 500         | `PROCESSING_FAILED`  | Failed to process callback                      |

### Notes

- No authentication is required as callbacks come from payment gateways
- Strict validation of callback data and signatures is performed
- IP address verification against gateway IPs may be implemented
- Payment status is updated based on callback information
- Success/failure notifications are triggered based on payment outcome

---

## Download Payment Invoice

Generate and download an invoice for a payment.

### HTTP Request

`GET /payments/invoice/{id}`

### Authentication

Requires a valid user authentication token.

### Path Parameters

| Parameter | Type    | Required | Description        | Constraints                   |
|-----------|---------|----------|--------------------|------------------------------|
| `id`      | Integer | Yes      | Payment identifier | Must be a valid payment ID    |

### Response

Status code: `200 OK`

Content-Type: `application/pdf`
Content-Disposition: `attachment; filename="invoice-{reference}.pdf"`

Binary PDF data containing the invoice.

### Error Codes

| Status Code | Error Code               | Description                                         |
|-------------|-------------------------|-----------------------------------------------------|
| 401         | `UNAUTHORIZED`          | User not authenticated                               |
| 403         | `PERMISSION_DENIED`     | User does not have access to this payment            |
| 404         | `PAYMENT_NOT_FOUND`     | Payment not found                                    |
| 404         | `INVOICE_NOT_AVAILABLE` | Invoice cannot be generated for this payment         |
| 500         | `GENERATION_FAILED`     | Failed to generate invoice                           |

### Notes

- Invoices are only available for completed payments
- Download is logged for audit purposes
- Admins can access invoices for any payment
- Regular users can only access invoices for their own payments
- Invoice includes legally required fiscal information
