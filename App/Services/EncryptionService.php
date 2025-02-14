<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EncryptionService
{
    // Remove manual $encryptionKey, $cipher, and $ivLength properties.
    
    public function __construct()
    {
        // ...existing constructor code removed; Laravel handles key management via config('app.key')...
    }

    public function encrypt(string $data): string
    {
        try {
            return Crypt::encryptString($data);
        } catch (\Exception $e) {
            Log::error('Encryption failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function decrypt(string $encryptedData): ?string
    {
        try {
            return Crypt::decryptString($encryptedData);
        } catch (\Exception $e) {
            Log::error('Decryption failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function encryptFile(string $inputFile, string $outputFile): bool
    {
        // Use Storage facade and Crypt for file encryption.
        try {
            $data = file_get_contents($inputFile); // retain manual file reading
            if ($data === false) {
                throw new \RuntimeException("Failed to read file: $inputFile");
            }
            $encrypted = Crypt::encryptString($data);
            Storage::put($outputFile, $encrypted);
            return true;
        } catch (\Exception $e) {
            Log::error('File encryption failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function decryptFile(string $inputFile, string $outputFile): bool
    {
        try {
            $encryptedData = Storage::get($inputFile);
            $decrypted = Crypt::decryptString($encryptedData);
            Storage::put($outputFile, $decrypted);
            return true;
        } catch (\Exception $e) {
            Log::error('File decryption failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function sign(string $data): string
    {
        // Use Laravel's app key for HMAC signing.
        return hash_hmac('sha256', $data, config('app.key'));
    }

    public function verify(string $data, string $signature): bool
    {
        return hash_equals($this->sign($data), $signature);
    }

    // ...existing code...
}
