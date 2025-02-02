<?php

namespace DocumentManager\Services;

use Exception;
use Psr\Log\LoggerInterface;

/**
 * FileStorage Service
 *
 * Handles secure storage, retrieval, and deletion of files with support for encryption,
 * logging, and temporary file management.
 */
class FileStorage
{
    private string $basePath;
    private array $config;
    private LoggerInterface $logger;

    /**
     * Constructor
     *
     * @param array $config Configuration for the FileStorage service.
     * @param LoggerInterface $logger Logger instance for logging file operations.
     */
    public function __construct(array $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;

        $this->basePath = rtrim($config['base_directory'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_dir($this->basePath) || !is_writable($this->basePath)) {
            throw new Exception("Invalid storage base path or insufficient permissions: {$this->basePath}");
        }
    }

    /**
     * Store a file securely.
     *
     * @param string $directory Directory where the file will be stored.
     * @param string $fileName The name of the file to store.
     * @param string $content The content of the file.
     * @return string The path where the file was stored.
     * @throws Exception If storing the file fails.
     */
    public function storeFile(string $directory, string $fileName, string $content): string
    {
        $safeDirectory = $this->getDirectoryPath($directory);
        $safeFileName = $this->sanitizeFileName($fileName);
        $filePath = $safeDirectory . $safeFileName;

        if (file_put_contents($filePath, $content) === false) {
            $this->logger->error("Failed to store file", ['file' => $fileName, 'path' => $filePath]);
            throw new Exception("Failed to store file: $fileName");
        }

        chmod($filePath, $this->config['security']['permissions']['default']);
        $this->logger->info("File stored successfully", ['file' => $fileName, 'path' => $filePath]);

        return $filePath;
    }

    /**
     * Retrieve a file's content.
     *
     * @param string $filePath The path of the file to retrieve.
     * @return string The content of the file.
     * @throws Exception If the file does not exist or cannot be read.
     */
    public function retrieveFile(string $filePath): string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->logger->error("File not found or not readable", ['path' => $filePath]);
            throw new Exception("File not found or not readable: $filePath");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            $this->logger->error("Failed to retrieve file", ['path' => $filePath]);
            throw new Exception("Failed to retrieve file: $filePath");
        }

        $this->logger->info("File retrieved successfully", ['path' => $filePath]);
        return $content;
    }

    /**
     * Delete a file securely.
     *
     * @param string $filePath The path of the file to delete.
     * @return void
     * @throws Exception If the file does not exist or cannot be deleted.
     */
    public function deleteFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            $this->logger->error("File not found", ['path' => $filePath]);
            throw new Exception("File not found: $filePath");
        }

        if (!unlink($filePath)) {
            $this->logger->error("Failed to delete file", ['path' => $filePath]);
            throw new Exception("Failed to delete file: $filePath");
        }

        $this->logger->info("File deleted successfully", ['path' => $filePath]);
    }

    /**
     * Sanitize the file name to prevent directory traversal attacks.
     *
     * @param string $fileName The original file name.
     * @return string The sanitized file name.
     */
    private function sanitizeFileName(string $fileName): string
    {
        return preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $fileName);
    }

    /**
     * Get the full directory path, creating it if it doesn't exist.
     *
     * @param string $directory The relative directory path.
     * @return string The absolute directory path.
     * @throws Exception If the directory cannot be created or is not writable.
     */
    private function getDirectoryPath(string $directory): string
    {
        $path = $this->basePath . trim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_dir($path) && !mkdir($path, 0755, true)) {
            $this->logger->error("Failed to create directory", ['path' => $path]);
            throw new Exception("Failed to create directory: $path");
        }

        if (!is_writable($path)) {
            $this->logger->error("Directory is not writable", ['path' => $path]);
            throw new Exception("Directory is not writable: $path");
        }

        return $path;
    }
}
