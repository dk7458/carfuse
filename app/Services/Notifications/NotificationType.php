<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Models\User;

abstract class NotificationType
{
    protected \Illuminate\Log\LogManager $logger;
    protected array $config;

    public function __construct()
    {
        $this->logger = Log::channel('notifications');
        $this->config = Config::get('notifications');
    }

    abstract public function sendNotification(User $user, string $template, array $data): bool;

    protected function getTemplateContent(string $template, array $data, string $locale): string
    {
        $path = $this->config['templates_path'] . "/{$locale}/{$template}.blade.php";

        if (!file_exists($path)) {
            $path = $this->config['templates_path'] . "/en/{$template}.blade.php";
        }

        // Typical Laravel view rendering:
        return view($path, $data)->render();
    }
}
