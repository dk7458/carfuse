<?php

namespace App\Services;

class SFTPDelivery implements ReportDelivery
{
    public function send(string $path, array $options): bool
    {
        // SFTP upload logic here
        return true;
    }
}