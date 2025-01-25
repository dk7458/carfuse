
<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class CloudStorageManager
{
    public function exportFile(string $localPath, string $cloudPath): bool
    {
        $cloudStorage = Storage::disk(config('backup.storage.cloud.provider'));
        return $cloudStorage->put($cloudPath, file_get_contents($localPath));
    }

    public function validateCloudExport(string $path): bool
    {
        $cloudStorage = Storage::disk(config('backup.storage.cloud.provider'));
        return $cloudStorage->exists($path);
    }
}