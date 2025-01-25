
<?php

namespace App\Services;

class JSONFormatter implements ReportFormatter
{
    public function format(array $report): string
    {
        return json_encode($report['data'], JSON_PRETTY_PRINT);
    }
}