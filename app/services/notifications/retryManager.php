<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Exception;

class RetryManager
{
    private array $retryCount = [];
    private \Illuminate\Log\LogManager $logger;
    private array $config;

    public function __construct()
    {
        $this->logger = Log::channel('notifications');
        $this->config = Config::get('notifications');
    }

    /**
     * Attempt retry logic for failed notifications.
     */
    public function handleRetry(
        string $type,
        User $user,
        string $template,
        array $data,
        Exception $exception
    ): bool {
        $key = "{$type}:{$user->id}:{$template}";
        $this->retryCount[$key] = ($this->retryCount[$key] ?? 0) + 1;

        $this->logger->error("Failed to send {$type} notification to user: {$user->id}", [
            'error'   => $exception->getMessage(),
            'attempt' => $this->retryCount[$key]
        ]);

        if ($this->retryCount[$key] < $this->config['retry']['max_attempts']) {
            $this->queueFailedNotification($type, $user, $template, $data);
            return true;
        }

        return false;
    }

    /**
     * Queue failed notifications for a retry attempt later.
     */
    private function queueFailedNotification(
        string $type,
        User $user,
        string $template,
        array $data
    ): void {
        DB::table('failed_notifications')->insert([
            'type'        => $type,
            'user_id'     => $user->id,
            'template'    => $template,
            'data'        => json_encode($data),
            'attempts'    => $this->retryCount["{$type}:{$user->id}:{$template}"],
            'retry_after' => now()->addSeconds($this->config['retry']['interval']),
            'created_at'  => now(),
        ]);
    }
}
