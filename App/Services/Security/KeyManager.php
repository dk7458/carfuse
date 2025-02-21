<?php

namespace App\Services\Security;

use Exception;
use Psr\Log\LoggerInterface;
use App\Handlers\ExceptionHandler;

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

    public function generateKey(): string
    {
        return base64_encode(random_bytes(32)); // AES-256 key
    }

    public function storeKey(string $identifier, string $key): void
    {
        if (self::DEBUG_MODE) {
            $this->logger->info("[security] Storing key for {$identifier}");
        }
        $this->logger->info("[security] ✅ Storing key for {$identifier}", ['identifier' => $identifier]);
        // Implementation for storing key securely (e.g., database, key vault)
    }

    public function rotateKey(string $identifier): void
    {
        $newKey = $this->generateKey();
        $this->storeKey($identifier, $newKey);
        $this->logger->info("[security] ✅ Rotated key for {$identifier}", ['identifier' => $identifier]);
    }

    public function revokeKey(string $identifier): void
    {
        $this->logger->info("[security] ✅ Revoking key for {$identifier}", ['identifier' => $identifier]);
        // Implementation for revoking key securely
    }
}
