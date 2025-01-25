<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class CleanupService
{
    private $config;
    private $logger;
    private $cleanupManager;

    public function __construct(CleanupManager $cleanupManager)
    {
        $this->config = Config::get('cleanup');
        $this->logger = Log::channel('cleanup');
        $this->cleanupManager = $cleanupManager;
    }

    public function executeCleanups(): void
    {
        $this->logger->info('Starting all cleanup processes');

        $this->cleanupManager->registerCleanup(new PaymentCleanup());
        $this->cleanupManager->registerCleanup(new LogCleanup());
        $this->cleanupManager->registerCleanup(new SessionCleanup());

        $this->cleanupManager->executeAll();

        $this->logger->info('All cleanup processes completed');
    }
}
