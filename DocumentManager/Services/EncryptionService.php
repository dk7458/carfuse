<?php

namespace DocumentManager\Services;

/**
 * Encryption Service
 *
 * Provides functionality for encrypting and decrypting files and strings
 * using AES-256-CBC encryption. Ensures secure handling of sensitive data.
 */
class EncryptionService
{
    private string $encryptionKey;
    private string $cipher = 'AES-256-CBC';
    private int $ivLength;

    public function __construct(string $encryptionKey)
    {
        if (empty($encryptionKey) || strlen($encryptionKey) < 32) {
            throw new \InvalidArgumentException('Encryption key must be at least 32 characters long.');
        }

        $this->encryptionKey = $encryptionKey;
        $this->ivLength = openssl_cipher_iv_length($this->cipher);

        if ($this->ivLength === false) {
            throw new \RuntimeException('Unable to determine IV length for the cipher.');
        }
    }

    /**
     * Encrypt a string.
     *
     * @param string $data The data to encrypt.
     * @return string The encrypted data (base64 encoded).
     */
    public function encrypt(string $data): string
    {
        $iv = random_bytes($this->ivLength);
        $encrypted = openssl_encrypt($data, $this->cipher, $this->encryptionKey, 0, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed.');
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a string.
     *
     * @param string $encryptedData The encrypted data (base64 encoded).
     * @return string The decrypted string.
     */
    public function decrypt(string $encryptedData): string
    {
        $decoded = base64_decode($encryptedData, true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid base64-encoded data.');
        }

        $iv = substr($decoded, 0, $this->ivLength);
        $cipherText = substr($decoded, $this->ivLength);

        if (strlen($iv) !== $this->ivLength) {
            throw new \RuntimeException('Invalid IV length.');
        }

        $decrypted = openssl_decrypt($cipherText, $this->cipher, $this->encryptionKey, 0, $iv);

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed.');
        }

        return $decrypted;
    }

    /**
     * Encrypt a file.
     *
     * @param string $inputFile The path to the file to encrypt.
     * @param string $outputFile The path to save the encrypted file.
     * @return bool True on success.
     */
    public function encryptFile(string $inputFile, string $outputFile): bool
    {
        $this->validateFile($inputFile, 'Input file does not exist or is not readable');
        $data = $this->readFile($inputFile);
        $encryptedData = $this->encrypt($data);

        return $this->writeFile($outputFile, $encryptedData, 'Failed to write the encrypted file');
    }

    /**
     * Decrypt a file.
     *
     * @param string $inputFile The path to the encrypted file.
     * @param string $outputFile The path to save the decrypted file.
     * @return bool True on success.
     */
    public function decryptFile(string $inputFile, string $outputFile): bool
    {
        $this->validateFile($inputFile, 'Input file does not exist or is not readable');
        $encryptedData = $this->readFile($inputFile);
        $decryptedData = $this->decrypt($encryptedData);

        return $this->writeFile($outputFile, $decryptedData, 'Failed to write the decrypted file');
    }

    /**
     * Validate if a file exists and is readable.
     *
     * @param string $filePath The path to the file.
     * @param string $errorMessage The error message to throw if invalid.
     */
    private function validateFile(string $filePath, string $errorMessage): void
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException($errorMessage . ": $filePath");
        }
    }

    /**
     * Read the contents of a file.
     *
     * @param string $filePath The path to the file.
     * @return string The file content.
     */
    private function readFile(string $filePath): string
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read the file: $filePath");
        }

        return $content;
    }

    /**
     * Write data to a file.
     *
     * @param string $filePath The path to the file.
     * @param string $data The data to write.
     * @param string $errorMessage The error message to throw if writing fails.
     * @return bool True on success.
     */
    private function writeFile(string $filePath, string $data, string $errorMessage): bool
    {
        if (file_put_contents($filePath, $data) === false) {
            throw new \RuntimeException("$errorMessage: $filePath");
        }

        return true;
    }
}
