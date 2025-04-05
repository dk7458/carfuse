# Encryption

## Overview

The CarFuse application employs a multi-layered encryption approach to protect sensitive data both at rest (in storage) and in transit. This document outlines how encryption is implemented throughout the application.

## Encryption Standards and Algorithms

### Core Encryption

- **Primary Algorithm**: AES-256 (Advanced Encryption Standard with 256-bit key)
- **Implementation**: Laravel's `Crypt` facade for standard operations
- **Key Derivation**: PBKDF2 (Password-Based Key Derivation Function 2)
- **Data Signatures**: HMAC with SHA-256

### Transport Security

- **HTTPS/TLS**: Required for all API endpoints
- **API Payload Encryption**: Additional application-level encryption for sensitive endpoints
- **JWT Security**: Signed tokens with HS256 algorithm

## Key Management

The application uses the `KeyManager` service to manage encryption keys:

### Key Hierarchy

1. **Master Key**: Used for encrypting other keys
2. **Domain-specific Keys**: Separate keys for different data domains
3. **Rotation Keys**: Temporary keys used during key rotation processes

### Key Generation

```php
// Example using the KeyManager service
$keyManager = new KeyManager($config, $logger, $exceptionHandler);
$newKey = $keyManager->generateKey(); // Returns a cryptographically secure random key
```

### Key Rotation

Keys are rotated according to the following schedule:
- Master key: Every 90 days
- Domain keys: Every 30 days
- JWT keys: Every 14 days

```php
// Key rotation example
$keyManager->rotateKey('payments');
```

### Key Storage

- Keys are never stored in code or version control
- Keys are stored in secure key vaults or encrypted configuration
- In development environments, keys are stored in `.env` files (excluded from version control)
- In production, keys are stored in a secure key management service

## Data-at-Rest Encryption

### Database Encryption

Sensitive fields in the database are encrypted before storage and decrypted after retrieval:

```php
// How encryption is applied to model data
protected function encryptSensitiveData(array $data): array
{
    foreach ($this->encryptedFields as $field) {
        if (isset($data[$field])) {
            $data[$field] = $this->encryptionService->encrypt($data[$field]);
        }
    }
    return $data;
}
```

### Sensitive Fields

The following types of data are automatically encrypted:
1. **Financial Information**: Payment card numbers, account details
2. **Personal Identifiers**: National ID numbers, passport numbers
3. **Contact Information**: When specified in model configuration

### File Encryption

Sensitive files are encrypted using the `FileStorage` service with the `encrypt` flag:

```php
// Example of storing an encrypted file
$encryptedPath = $fileStorage->storeFile("documents/secure", 
                                        "contract.pdf", 
                                        $content, 
                                        true); // true enables encryption
```

## Data-in-Transit Protection

### API Request/Response Encryption

The `EncryptionMiddleware` provides application-level encryption for sensitive API endpoints:

1. Endpoint configuration is stored in `config/sensitive_endpoints.json`
2. Field configuration is stored in `config/sensitive_fields.json`
3. Requests to sensitive endpoints have their sensitive fields encrypted
4. Responses from sensitive endpoints are encrypted in their entirety

### JWT Token Security

- Access tokens expire after 1 hour
- Refresh tokens expire after 7 days
- Tokens are cryptographically signed using HMAC SHA-256
- Token verification includes issuer and audience validation

## Best Practices for Developers

### Adding Encryption to New Fields

1. Identify sensitive fields in your model
2. Add field names to the `$encryptedFields` array:

```php
protected $encryptedFields = ['existing_field', 'your_new_sensitive_field'];
```

3. Ensure model extends `BaseModel` and implements encryption methods
4. Update relevant tests to account for encryption/decryption

### Working with Encrypted Data

1. **Never log encrypted data** in its raw form
2. **Never store encryption keys** in the repository
3. Use the `EncryptionService` directly for manual encryption needs:

```php
$encrypted = $this->encryptionService->encrypt($sensitiveData);
$decrypted = $this->encryptionService->decrypt($encrypted);
```

4. For file operations, use the `FileStorage` service with the encryption flag

### Debugging Encryption Issues

1. Enable DEBUG_MODE only in development environments
2. Look for specific encryption error messages in the logs
3. Verify key availability and permissions
4. Check if the encrypted data was modified after encryption

### Security Auditing

The system automatically logs encryption-related events:
- Key rotations
- Encryption failures
- Suspicious decryption attempts
- File encryption operations

## Implementation Details

### EncryptionService

The core service handling all encryption/decryption operations:

```php
// Example usage
$encryptionService = new EncryptionService($logger, $exceptionHandler, $encryptionKey);

// String encryption
$encrypted = $encryptionService->encrypt($sensitiveData);
$decrypted = $encryptionService->decrypt($encrypted);

// File encryption
$encryptionService->encryptFile($inputPath, $outputPath);
$encryptionService->decryptFile($encryptedPath, $decryptedOutputPath);

// Signatures
$signature = $encryptionService->sign($data);
$isValid = $encryptionService->verify($data, $signature);
```

### Security Recommendations

1. **Defense in Depth**: Don't rely solely on encryption; implement proper access controls
2. **Regular Auditing**: Review encryption logs and key usage periodically
3. **Key Rotation**: Rotate keys according to schedule or after security incidents
4. **Separation of Duties**: Use different keys for different types of data
