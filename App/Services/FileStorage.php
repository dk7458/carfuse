<?php

namespace App\Services;

use Exception;
use App\Services\EncryptionService;

class FileStorage
{
    private string $basePath;
    private array $config;
    private $logger;
    private EncryptionService $encryptionService;

    public function __construct(array $config, EncryptionService $encryptionService)
    {
        $this->logger = getLogger('file.log');
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

        if (file_put_contents($filePath, $content) === false) {
            $this->logger->error("❌ [FileStorage] Failed to store file.", ['file' => $fileName, 'path' => $filePath, 'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)]);
            throw new Exception("Failed to store file: $fileName");
        }

        chmod($filePath, $this->config['security']['permissions']['default']);
        $this->logger->info("✅ [FileStorage] File stored successfully.", ['file' => $fileName, 'path' => $filePath]);

        return $filePath;
    }

    public function retrieveFile(string $filePath, bool $decrypt = false): string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->logger->error("❌ File not found or not readable.", ['path' => $filePath, 'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)]);
            throw new Exception("File not found or not readable: $filePath");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            $this->logger->error("❌ [FileStorage] Failed to retrieve file.", ['path' => $filePath, 'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)]);
            throw new Exception("Failed to retrieve file: $filePath");
        }

        if ($decrypt) {
            $content = $this->encryptionService->decrypt($content);
            if ($content === null) {
                throw new Exception("Failed to decrypt file: $filePath");
            }
        }

        $this->logger->info("✅ [FileStorage] File retrieved successfully.", ['path' => $filePath]);
        return $content;
    }

    public function deleteFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            $this->logger->error("❌ File not found.", ['path' => $filePath, 'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)]);
            throw new Exception("File not found: $filePath");
        }

        if (!unlink($filePath)) {
            $this->logger->error("❌ [FileStorage] Failed to delete file.", ['path' => $filePath, 'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)]);
            throw new Exception("Failed to delete file: $filePath");
        }

        $this->logger->info("✅ [FileStorage] File deleted successfully.", ['path' => $filePath]);
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
