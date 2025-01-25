<?php

namespace App\Services\Reports;

interface ReportDelivery
{
    /**
     * Send or place a report located at $path.
     */
    public function send(string $path, array $options): bool;
}
