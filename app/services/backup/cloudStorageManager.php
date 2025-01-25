<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\Storage;

class CloudStorageManager
{
    /**
     * Export a local file to configured cloud storage.
     */
    public function exportFile(string $localPath, string $cloudPath): bool
    {
        $cloudStorage = Storage::disk(config('backup.storage.cloud.provider'));
        return $cloudStorage->put($cloudPath, file_get_contents($localPath));
    }

    /**
     * Validate if the exported file exists in cloud storage.
     */
    public function validateCloudExport(string $path): bool
    {
        $cloudStorage = Storage::disk(config('backup.storage.cloud.provider'));
        return $cloudStorage->exists($path);
    }
}
