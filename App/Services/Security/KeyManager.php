<?php

namespace App\Services\Security;

use Exception;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use Illuminate\Support\Facades\Log;

class KeyManager
{
    public const DEBUG_MODE = true;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private array $keys;

    public function __construct(array $keys, LoggerInterface $logger, ExceptionHandler $exceptionHandler)
    {
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;
        $this->keys = $keys;
    }

    public function getKey(string $identifier): string
    {
        $keyName = 'encryption_key_' . strtolower($identifier);

        if (!isset($this->keys[$keyName]) || empty($this->keys[$keyName])) {
            $this->logger->error("[security] ❌ Encryption key for {$identifier} not found.", ['identifier' => $identifier]);
            throw new Exception("Encryption key for {$identifier} not found.");
        }

        return $this->keys[$keyName];
    }

    public function loadKey(string $keyName): string
    {
        try {
            $key = config("keys.$keyName");
            if (!$key) {
                throw new \RuntimeException("Key not found: $keyName");
            }
            return $key;
        } catch (\Exception $e) {
            Log::error("Key loading failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function storeKey(string $keyName, string $key): bool
    {
        try {
            config(["keys.$keyName" => $key]);
            return true;
        } catch (\Exception $e) {
            Log::error("Key storage failed: " . $e->getMessage());
            return false;
        }
    }

    public function generateKey(): string
    {
        return base64_encode(random_bytes(32)); // AES-256 key
    }

    public function rotateKey(string $keyName): bool
    {
        try {
            $newKey = bin2hex(random_bytes(32));
            return $this->storeKey($keyName, $newKey);
        } catch (\Exception $e) {
            Log::error("Key rotation failed: " . $e->getMessage());
            return false;
        }
    }

    public function revokeKey(string $identifier): void
    {
        $this->logger->info("[security] ✅ Revoking key for {$identifier}", ['identifier' => $identifier]);
        // Implementation for revoking key securely
    }
}
