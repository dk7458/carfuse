<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

abstract class BaseCleanup
{
    protected $logger;
    protected $config;

    public function __construct()
    {
        $this->logger = Log::channel('cleanup');
        $this->config = Config::get('cleanup');
    }

    abstract public function performCleanup(array $config): array;

    protected function backupBeforeCleanup(array $config): void
    {
        if ($config['backup_before_delete']) {
            // Implement backup logic here
        }
    }
}
