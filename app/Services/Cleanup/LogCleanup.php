<?php

namespace App\Services\Cleanup;

use Exception;

class LogCleanup extends BaseCleanup
{
    public function performCleanup(array $config): array
    {
        $this->logger->info('Starting log cleanup process');
        $cutoffDate = Carbon::now()->subDays($config['retention_days']);
        $stats = ['processed' => 0, 'deleted' => 0, 'errors' => 0];

        foreach ($config['paths'] as $path) {
            $this->backupBeforeCleanup($config);

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
    }
}
