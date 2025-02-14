<?php

namespace App\Services\Security;

use Exception;
use Psr\Log\LoggerInterface;

class KeyManager
{
    private array $keys;
    private LoggerInterface $logger;

    public function __construct(array $keys, LoggerInterface $logger)
    {
        $this->keys = $keys;
        $this->logger = $logger;
    }

    public function getKey(string $identifier): string
    {
        $keyName = 'encryption_key_' . strtolower($identifier);

        if (!isset($this->keys[$keyName]) || empty($this->keys[$keyName])) {
            $this->logger->error("[KeyManager] Encryption key for {$identifier} not found.");
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
        $this->logger->info("[KeyManager] Storing key for {$identifier}");
        // Implementation for storing key securely (e.g., database, key vault)
    }

    public function rotateKey(string $identifier): void
    {
        $newKey = $this->generateKey();
        $this->storeKey($identifier, $newKey);
        $this->logger->info("[KeyManager] Rotated key for {$identifier}");
    }

    public function revokeKey(string $identifier): void
    {
        $this->logger->info("[KeyManager] Revoking key for {$identifier}");
        // Implementation for revoking key securely
    }
}
