<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Exception;

class NotificationService
{
    private array $config;
    private \Illuminate\Log\LogManager $logger;
    private RetryManager $retryManager;
    private array $notificationTypes = [];

    public function __construct()
    {
        $this->config = Config::get('notifications');
        $this->logger = Log::channel('notifications');
        $this->retryManager = new RetryManager();

        // Register default notification types
        $this->registerNotificationType('email', new EmailNotification());
        $this->registerNotificationType('sms', new SMSNotification());
        $this->registerNotificationType('push', new PushNotification());
    }

    /**
     * Register a custom notification type if needed.
     */
    public function registerNotificationType(string $type, NotificationType $notificationType): void
    {
        $this->notificationTypes[$type] = $notificationType;
    }

    /**
     * Send a notification of a given type to a user.
     */
    public function sendNotification(
        string $type,
        User $user,
        string $template,
        array $data
    ): bool {
        if (!isset($this->notificationTypes[$type])) {
            throw new Exception("Notification type {$type} not supported.");
        }

        try {
            if (!$this->shouldSendNotification($user, $type)) {
                return false;
            }

            return $this->notificationTypes[$type]->sendNotification($user, $template, $data);
        } catch (Exception $e) {
            return $this->retryManager->handleRetry($type, $user, $template, $data, $e);
        }
    }

    /**
     * Send a bulk batch of notifications.
     */
    public function sendBatchNotifications(
        array $users,
        string $type,
        string $template,
        array $data
    ): array {
        $results = ['success' => 0, 'failed' => 0];
        $chunks = array_chunk($users, $this->config['batch']['size']);

        foreach ($chunks as $chunk) {
            foreach ($chunk as $user) {
                $success = $this->sendNotification($type, $user, $template, $data);
                $results[$success ? 'success' : 'failed']++;
            }
            sleep($this->config['batch']['delay']);
        }

        return $results;
    }

    /**
     * Check user preferences to see if they should receive a particular notification type.
     */
    private function shouldSendNotification(User $user, string $type): bool
    {
        $preferences = DB::table('notification_preferences')
            ->where('user_id', $user->id)
            ->first();

        return $preferences ? ($preferences->{$type . '_enabled'} ?? true) : true;
    }
}
