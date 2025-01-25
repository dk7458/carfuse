<?php

namespace App\Services;

interface ReportDelivery
{
    public function send(string $path, array $options): bool;
}