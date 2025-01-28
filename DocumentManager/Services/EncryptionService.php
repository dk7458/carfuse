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
     * Encrypts a string.
     *
     * @param string $data The data to encrypt.
     * @return string The encrypted data, base64 encoded.
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
     * Decrypts an encrypted string.
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
     * Encrypts a file.
     *
     * @param string $inputFile The path to the file to encrypt.
     * @param string $outputFile The path to save the encrypted file.
     * @return bool True on success, false on failure.
     */
    public function encryptFile(string $inputFile, string $outputFile): bool
    {
        if (!file_exists($inputFile) || !is_readable($inputFile)) {
            throw new \InvalidArgumentException("Input file does not exist or is not readable: $inputFile");
        }

        $data = file_get_contents($inputFile);
        if ($data === false) {
            throw new \RuntimeException("Failed to read the input file: $inputFile");
        }

        $encryptedData = $this->encrypt($data);

        if (file_put_contents($outputFile, $encryptedData) === false) {
            throw new \RuntimeException("Failed to write the encrypted file: $outputFile");
        }

        return true;
    }

    /**
     * Decrypts an encrypted file.
     *
     * @param string $inputFile The path to the encrypted file.
     * @param string $outputFile The path to save the decrypted file.
     * @return bool True on success, false on failure.
     */
    public function decryptFile(string $inputFile, string $outputFile): bool
    {
        if (!file_exists($inputFile) || !is_readable($inputFile)) {
            throw new \InvalidArgumentException("Input file does not exist or is not readable: $inputFile");
        }

        $encryptedData = file_get_contents($inputFile);
        if ($encryptedData === false) {
            throw new \RuntimeException("Failed to read the input file: $inputFile");
        }

        $decryptedData = $this->decrypt($encryptedData);

        if (file_put_contents($outputFile, $decryptedData) === false) {
            throw new \RuntimeException("Failed to write the decrypted file: $outputFile");
        }

        return true;
    }
}
