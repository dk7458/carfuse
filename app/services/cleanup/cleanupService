<?php

namespace App\Services\Cleanup;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use App\Services\Backup\BackupService; // references the new BackupService

class CleanupService
{
    private array $config;
    private \Illuminate\Log\LogManager $logger;
    private BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->config = Config::get('cleanup');
        $this->logger = Log::channel('cleanup');
        $this->backupService = $backupService;
    }

    /**
     * Delete expired payments based on retention policy.
     */
    public function deleteExpiredPayments(): array
    {
        try {
            $this->logger->info('Starting payment cleanup process');
            $cutoffDate = Carbon::now()->subDays($this->config['payments']['retention_days']);
            $stats = ['processed' => 0, 'deleted' => 0, 'errors' => 0];

            if ($this->config['payments']['backup_before_delete']) {
                // Create a file backup before deletions, or you could do a DB backup
                $this->backupService->createFileBackup();
            }

            DB::table('payments')
                ->where('created_at', '<', $cutoffDate)
                ->whereIn('status', $this->config['payments']['statuses'])
                ->chunkById($this->config['payments']['chunk_size'], function ($payments) use (&$stats) {
                    foreach ($payments as $payment) {
                        try {
                            DB::table('payments')->delete($payment->id);
                            $stats['deleted']++;
                        } catch (Exception $e) {
                            $stats['errors']++;
                            $this->logger->error("Failed to delete payment {$payment->id}: {$e->getMessage()}");
                        }
                        $stats['processed']++;
                    }
                });

            $this->logger->info('Payment cleanup completed', $stats);
            return $stats;
        } catch (Exception $e) {
            $this->logger->error('Payment cleanup failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Clear old logs from local directories.
     */
    public function clearOldLogs(): array
    {
        try {
            $this->logger->info('Starting log cleanup process');
            $cutoffDate = Carbon::now()->subDays($this->config['logs']['retention_days']);
            $stats = ['processed' => 0, 'deleted' => 0, 'errors' => 0];

            foreach ($this->config['logs']['paths'] as $path) {
                if ($this->config['logs']['backup_before_delete']) {
                    // Possibly run a file backup or some custom logic
                    $this->backupService->createFileBackup();
                }

                $files = glob($path . '/*.log');
                foreach ($files as $file) {
                    try {
                        if (filemtime($file) < $cutoffDate->timestamp) {
                            unlink($file);
                            $stats['deleted']++;
                        }
                    } catch (Exception $e) {
                        $stats['errors']++;
                        $this->logger->error("Failed to delete log file {$file}: {$e->getMessage()}");
                    }
                    $stats['processed']++;
                }
            }

            $this->logger->info('Log cleanup completed', $stats);
            return $stats;
        } catch (Exception $e) {
            $this->logger->error('Log cleanup failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Purge expired sessions from the DB.
     */
    public function purgeExpiredSessions(): array
    {
        try {
            $this->logger->info('Starting session cleanup process');
            $cutoffDate = Carbon::now()->subDays($this->config['sessions']['retention_days']);
            $stats = ['processed' => 0, 'deleted' => 0, 'errors' => 0];

            DB::table('sessions')
                ->where('last_activity', '<', $cutoffDate->timestamp)
                ->whereNotIn('user_id', $this->config['sessions']['excluded_users'])
                ->chunkById(1000, function ($sessions) use (&$stats) {
                    foreach ($sessions as $session) {
                        try {
                            DB::table('sessions')->delete($session->id);
                            $stats['deleted']++;
                        } catch (Exception $e) {
                            $stats['errors']++;
                            $this->logger->error("Failed to delete session {$session->id}: {$e->getMessage()}");
                        }
                        $stats['processed']++;
                    }
                });

            $this->logger->info('Session cleanup completed', $stats);
            return $stats;
        } catch (Exception $e) {
            $this->logger->error('Session cleanup failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Optional method to validate that cleanup happened properly.
     */
    public function validateCleanup(string $type): bool
    {
        // Add domain-specific validation logic here
        return true;
    }
}
