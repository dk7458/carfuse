<?php

namespace DocumentManager\Services;

use Exception;

/**
 * FileStorage Service
 *
 * Handles secure storage, retrieval, and deletion of files.
 */
class FileStorage
{
    private string $storagePath;

    /**
     * Constructor
     *
     * @param string $storagePath Base path for file storage.
     */
    public function __construct(string $storagePath)
    {
        if (!is_dir($storagePath) || !is_writable($storagePath)) {
            throw new Exception("Invalid storage path or insufficient permissions: $storagePath");
        }
        $this->storagePath = rtrim($storagePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Store a file securely.
     *
     * @param string $fileName The name of the file to store.
     * @param string $content The content of the file.
     * @return string The path where the file was stored.
     * @throws Exception If storing the file fails.
     */
    public function storeFile(string $fileName, string $content): string
    {
        $safeFileName = $this->sanitizeFileName($fileName);
        $filePath = $this->storagePath . $safeFileName;

        if (file_put_contents($filePath, $content) === false) {
            throw new Exception("Failed to store file: $fileName");
        }

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
            throw new Exception("File not found or not readable: $filePath");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("Failed to retrieve file: $filePath");
        }

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
            throw new Exception("File not found: $filePath");
        }

        if (!unlink($filePath)) {
            throw new Exception("Failed to delete file: $filePath");
        }
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
}
