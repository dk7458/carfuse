<?php

namespace App\Services\Notifications;

use App\Models\User;
use Exception;

class PushNotification extends NotificationType
{
    public function sendNotification(User $user, string $template, array $data): bool
    {
        try {
            $content = $this->getTemplateContent($template, $data, $user->locale);
            // Push notification logic
            // ...
            $this->logger->info("Push notification sent to user: {$user->id}");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
