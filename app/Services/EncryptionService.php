<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class EncryptionService
{
    private static function getKey(): string
    {
        $key = env('ENCRYPTION_KEY');
        if (!$key) {
            throw new Exception('Encryption key not found.');
        }
        return $key;
    }

    public static function encrypt($data): string
    {
        $key = self::getKey();
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        if ($encrypted === false) {
            throw new Exception('Encryption failed.');
        }
        return base64_encode($iv . $encrypted);
    }

    public static function decrypt($data)
    {
        $key = self::getKey();
        $data = base64_decode($data);
        $ivLength = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
        if ($decrypted === false) {
            Log::error('Decryption failed.');
            return null;
        }
        return $decrypted;
    }

    public static function sign($data): string
    {
        $key = self::getKey();
        return hash_hmac('sha256', $data, $key);
    }

    public static function verify($data, $signature): bool
    {
        $key = self::getKey();
        $calculatedSignature = hash_hmac('sha256', $data, $key);
        return hash_equals($calculatedSignature, $signature);
    }
}
