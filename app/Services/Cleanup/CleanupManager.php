<?php

namespace App\Services\Cleanup;

class CleanupManager
{
    private $cleanups = [];

    public function registerCleanup(BaseCleanup $cleanup): void
    {
        $this->cleanups[] = $cleanup;
    }

    public function executeAll(): void
    {
        foreach ($this->cleanups as $cleanup) {
            $config = $cleanup->config[get_class($cleanup)];
            $cleanup->performCleanup($config);
        }
    }
}
