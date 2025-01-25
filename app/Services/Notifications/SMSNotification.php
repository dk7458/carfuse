<?php

namespace App\Services\Notifications;

use App\Models\User;
use Exception;

class SMSNotification extends NotificationType
{
    public function sendNotification(User $user, string $template, array $data): bool
    {
        try {
            $content = $this->getTemplateContent($template, $data, $user->locale);
            // SMS sending logic here
            // ...
            $this->logger->info("SMS sent to user: {$user->id}");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
