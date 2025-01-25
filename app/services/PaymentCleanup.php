<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Exception;

class PaymentCleanup extends BaseCleanup
{
    public function performCleanup(array $config): array
    {
        $this->logger->info('Starting payment cleanup process');
        $cutoffDate = Carbon::now()->subDays($config['retention_days']);
        $stats = ['processed' => 0, 'deleted' => 0, 'errors' => 0];

        $this->backupBeforeCleanup($config);

        DB::table('payments')
            ->where('created_at', '<', $cutoffDate)
            ->whereIn('status', $config['statuses'])
            ->chunkById($config['chunk_size'], function ($payments) use (&$stats) {
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
    }
}
