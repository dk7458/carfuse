<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Exception;

class SessionCleanup extends BaseCleanup
{
    public function performCleanup(array $config): array
    {
        $this->logger->info('Starting session cleanup process');
        $cutoffDate = Carbon::now()->subDays($config['retention_days']);
        $stats = ['processed' => 0, 'deleted' => 0, 'errors' => 0];

        DB::table('sessions')
            ->where('last_activity', '<', $cutoffDate->timestamp)
            ->whereNotIn('user_id', $config['excluded_users'])
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
    }
}
