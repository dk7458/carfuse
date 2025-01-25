<?php

namespace App\Services\Notifications;

use App\Models\User;
use Exception;

class EmailNotification extends NotificationType
{
    public function sendNotification(User $user, string $template, array $data): bool
    {
        try {
            $content = $this->getTemplateContent($template, $data, $user->locale);
            // Email sending logic here using your chosen provider
            // ...
            $this->logger->info("Email sent to user: {$user->id}");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
