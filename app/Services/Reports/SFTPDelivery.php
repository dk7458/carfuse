<?php

namespace App\Services\Reports;

class SFTPDelivery implements ReportDelivery
{
    public function send(string $path, array $options): bool
    {
        // SFTP upload logic
        // Use $options for credentials, remote path, etc.
        return true;
    }
}
