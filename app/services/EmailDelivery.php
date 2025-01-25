
<?php

namespace App\Services;

class EmailDelivery implements ReportDelivery
{
    public function send(string $path, array $options): bool
    {
        // Email sending logic here
        return true;
    }
}