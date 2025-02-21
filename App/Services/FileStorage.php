<?php

namespace App\Services;

use Exception;
use App\Services\EncryptionService;
use Psr\Log\LoggerInterface;
use App\Handlers\ExceptionHandler;

class FileStorage
{
    public const DEBUG_MODE = true;
    private string $basePath;
    private array $config;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private EncryptionService $encryptionService;

    public function __construct(array $config, EncryptionService $encryptionService, LoggerInterface $logger, ExceptionHandler $exceptionHandler)
    {
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;
        $this->config = $config;
        $this->encryptionService = $encryptionService;
        $this->basePath = rtrim($config['base_directory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_dir($this->basePath) || !is_writable($this->basePath)) {
            throw new Exception("Invalid storage base path or insufficient permissions: {$this->basePath}");
        }
    }

    public function storeFile(string $directory, string $fileName, string $content, bool $encrypt = false): string
    {
        $safeDirectory = $this->getDirectoryPath($directory);
        $safeFileName = $this->sanitizeFileName($fileName);
        $filePath = $safeDirectory . $safeFileName;

        if ($encrypt) {
            $content = $this->encryptionService->encrypt($content);
        }

        try {
            if (file_put_contents($filePath, $content) === false) {
                throw new Exception("Failed to store file: $fileName");
            }

            chmod($filePath, $this->config['security']['permissions']['default']);
            if (self::DEBUG_MODE) {
                $this->logger->info("[FileStorage] File stored: {$fileName}");
            }

            return $filePath;
        } catch (\Exception $e) {
            $this->logger->error("[FileStorage] ❌ Failed to store file: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function retrieveFile(string $filePath, bool $decrypt = false): string
    {
        try {
            if (!file_exists($filePath) || !is_readable($filePath)) {
                throw new Exception("File not found or not readable: $filePath");
            }

            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new Exception("Failed to retrieve file: $filePath");
            }

            if ($decrypt) {
                $content = $this->encryptionService->decrypt($content);
                if ($content === null) {
                    throw new Exception("Failed to decrypt file: $filePath");
                }
            }

            if (self::DEBUG_MODE) {
                $this->logger->info("[FileStorage] File retrieved: {$filePath}");
            }
            return $content;
        } catch (\Exception $e) {
            $this->logger->error("[FileStorage] ❌ Failed to retrieve file: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function deleteFile(string $filePath): void
    {
        try {
            if (!file_exists($filePath)) {
                throw new Exception("File not found: $filePath");
            }

            if (!unlink($filePath)) {
                throw new Exception("Failed to delete file: $filePath");
            }

            if (self::DEBUG_MODE) {
                $this->logger->info("[FileStorage] File deleted: {$filePath}");
            }
        } catch (\Exception $e) {
            $this->logger->error("[FileStorage] ❌ Failed to delete file: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    private function sanitizeFileName(string $fileName): string
    {
        return preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $fileName);
    }

    private function getDirectoryPath(string $directory): string
    {
        $path = $this->basePath . trim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_dir($path) && !mkdir($path, 0755, true)) {
            $this->logger->error("❌ Failed to create directory.", ['path' => $path, 'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)]);
            throw new Exception("Failed to create directory: $path");
        }

        if (!is_writable($path)) {
            $this->logger->error("❌ Directory is not writable.", ['path' => $path, 'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)]);
            throw new Exception("Directory is not writable: $path");
        }

        return $path;
    }
}
