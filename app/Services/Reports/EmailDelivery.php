<?php

namespace App\Services\Reports;

class EmailDelivery implements ReportDelivery
{
    public function send(string $path, array $options): bool
    {
        // Email sending logic
        // Possibly attach file from $path
        return true;
    }
}
