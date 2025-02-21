<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;
use App\Handlers\ExceptionHandler;

class EncryptionService
{
    public const DEBUG_MODE = true;

    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;

    public function __construct(LoggerInterface $logger, ExceptionHandler $exceptionHandler)
    {
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;
    }

    public function encrypt(string $data): string
    {
        try {
            return Crypt::encryptString($data);
        } catch (\Exception $e) {
            $this->logger->error("[Encryption] ❌ Encryption failed: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function decrypt(string $encryptedData): ?string
    {
        try {
            return Crypt::decryptString($encryptedData);
        } catch (\Exception $e) {
            $this->logger->error("[Encryption] ❌ Decryption failed: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }

    public function encryptFile(string $inputFile, string $outputFile): bool
    {
        try {
            $data = file_get_contents($inputFile);
            if ($data === false) {
                throw new \RuntimeException("Failed to read file: $inputFile");
            }
            $encrypted = Crypt::encryptString($data);
            Storage::put($outputFile, $encrypted);
            return true;
        } catch (\Exception $e) {
            $this->logger->error("File encryption failed: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
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
            $this->logger->error("File decryption failed: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return false;
        }
    }

    public function sign(string $data): string
    {
        return hash_hmac('sha256', $data, config('app.key'));
    }

    public function verify(string $data, string $signature): bool
    {
        return hash_equals($this->sign($data), $signature);
    }
}
