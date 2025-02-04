<?php

namespace App\Services;

use Exception;
use RuntimeException;
use Illuminate\Support\Facades\Log;

/**
 * EncryptionService
 *
 * Provides functionality for encrypting/decrypting strings and files securely.
 */
class EncryptionService
{
    private string $encryptionKey;
    private string $cipher = 'AES-256-CBC';
    private int $ivLength;

    public function __construct()
    {
        // Load encryption key from config
        $configPath = __DIR__ . '/../../config/encryption.php';

        if (!file_exists($configPath)) {
            throw new RuntimeException('❌ Encryption configuration file is missing.');
        }

        $config = require $configPath;
        $this->encryptionKey = $config['encryption_key'] ?? '';

        if (empty($this->encryptionKey) || strlen($this->encryptionKey) < 32) {
            throw new RuntimeException('❌ Encryption key is missing or too short. It must be at least 32 characters long.');
        }

        $this->ivLength = openssl_cipher_iv_length($this->cipher);
        if ($this->ivLength === false) {
            throw new RuntimeException('❌ Unable to determine IV length for the cipher.');
        }
    }

    public function encrypt(string $data): string
    {
        $iv = random_bytes($this->ivLength);
        $encrypted = openssl_encrypt($data, $this->cipher, $this->encryptionKey, 0, $iv);

        if ($encrypted === false) {
            throw new RuntimeException('❌ Encryption failed.');
        }

        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $encryptedData): ?string
    {
        $decoded = base64_decode($encryptedData, true);
        if ($decoded === false) {
            Log::error('❌ Decryption failed: Invalid base64 input.');
            return null;
        }

        $iv = substr($decoded, 0, $this->ivLength);
        $cipherText = substr($decoded, $this->ivLength);

        if (strlen($iv) !== $this->ivLength) {
            throw new RuntimeException('❌ Invalid IV length.');
        }

        $decrypted = openssl_decrypt($cipherText, $this->cipher, $this->encryptionKey, 0, $iv);

        if ($decrypted === false) {
            Log::error('❌ Decryption failed: Data may have been tampered with.');
            return null;
        }

        return $decrypted;
    }

    /**
     * Encrypt a file.
     */
    public function encryptFile(string $inputFile, string $outputFile): bool
    {
        $this->validateFile($inputFile);
        $data = file_get_contents($inputFile);

        if ($data === false) {
            throw new \RuntimeException("Failed to read file: $inputFile");
        }

        return $this->writeFile($outputFile, $this->encrypt($data));
    }

    /**
     * Decrypt a file.
     */
    public function decryptFile(string $inputFile, string $outputFile): bool
    {
        $this->validateFile($inputFile);
        $encryptedData = file_get_contents($inputFile);

        if ($encryptedData === false) {
            throw new \RuntimeException("Failed to read encrypted file: $inputFile");
        }

        $decryptedData = $this->decrypt($encryptedData);
        if ($decryptedData === null) {
            throw new \RuntimeException("Failed to decrypt file: $inputFile");
        }

        return $this->writeFile($outputFile, $decryptedData);
    }

    /**
     * Sign data using HMAC SHA-256.
     */
    public function sign(string $data): string
    {
        return hash_hmac('sha256', $data, $this->encryptionKey);
    }

    /**
     * Verify the integrity of signed data.
     */
    public function verify(string $data, string $signature): bool
    {
        return hash_equals($this->sign($data), $signature);
    }

    /**
     * Validate if a file exists and is readable.
     */
    private function validateFile(string $filePath): void
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException("File not found or not readable: $filePath");
        }
    }

    /**
     * Write data to a file.
     */
    private function writeFile(string $filePath, string $data): bool
    {
        if (file_put_contents($filePath, $data) === false) {
            throw new \RuntimeException("Failed to write to file: $filePath");
        }

        return true;
    }
}
