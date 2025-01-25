<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Exception;

class BackupService
{
    private $config;
    private $logger;
    private $storage;
    private $databaseBackup;
    private $cloudStorageManager;

    public function __construct(DatabaseBackupInterface $databaseBackup, CloudStorageManager $cloudStorageManager)
    {
        $this->config = Config::get('backup');
        $this->logger = Log::channel('backup');
        $this->storage = Storage::disk('local');
        $this->databaseBackup = $databaseBackup;
        $this->cloudStorageManager = $cloudStorageManager;
    }

    public function createDatabaseBackup(bool $incremental = false): string
    {
        try {
            $filename = 'db_' . date('Y-m-d_His') . ($incremental ? '_inc' : '_full') . '.sql';
            $path = $this->config['storage']['local_path'] . '/' . $filename;

            // Database backup logic
            $command = $incremental ? 
                $this->databaseBackup->buildIncrementalBackupCommand() : 
                $this->databaseBackup->buildFullBackupCommand();

            exec($command . ' > ' . $path, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new Exception("Database backup failed");
            }

            if ($this->config['database']['validate_checksum']) {
                if (!$this->databaseBackup->validateBackup($path)) {
                    throw new Exception("Backup validation failed");
                }
            }

            $this->cloudStorageManager->exportFile($path, $this->config['storage']['cloud']['path']);
            $this->logger->info("Database backup created: $filename");

            return $path;
        } catch (Exception $e) {
            $this->logger->error("Database backup failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function createFileBackup(): string
    {
        try {
            $filename = 'files_' . date('Y-m-d_His') . '.tar.gz';
            $path = $this->config['storage']['local_path'] . '/' . $filename;

            $paths = implode(' ', $this->config['files']['paths']);
            $exclude = '--exclude=' . implode(' --exclude=', $this->config['files']['exclude']);
            
            exec("tar -czf $path $exclude $paths", $output, $returnVar);

            if ($returnVar !== 0) {
                throw new Exception("File backup failed");
            }

            $this->exportToCloud($path);
            $this->logger->info("File backup created: $filename");

            return $path;
        } catch (Exception $e) {
            $this->logger->error("File backup failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function cleanupOldBackups(): void
    {
        try {
            $this->cleanupByType('db', $this->config['database']['retention_days']);
            $this->cleanupByType('files', $this->config['files']['retention_days']);
            $this->logger->info("Cleanup completed");
        } catch (Exception $e) {
            $this->logger->error("Cleanup failed: " . $e->getMessage());
            throw $e;
        }
    }

    private function exportToCloud(string $path): void
    {
        $cloudPath = $this->config['storage']['cloud']['path'] . '/' . basename($path);
        $this->cloudStorageManager->exportFile($path, $cloudPath);
    }

    private function cleanupByType(string $type, int $days): void
    {
        $pattern = $this->config['storage']['local_path'] . "/$type_*.{sql,tar.gz}";
        $deadline = strtotime("-$days days");

        foreach (glob($pattern) as $file) {
            if (filemtime($file) < $deadline) {
                unlink($file);
                $this->logger->info("Deleted old backup: " . basename($file));
            }
        }
    }
}
