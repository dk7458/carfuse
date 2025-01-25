<?php

namespace App\Services\Reports;

class CSVFormatter implements ReportFormatter
{
    public function format(array $report): string
    {
        // Basic CSV conversion example
        // TODO: Expand with real CSV logic
        return 'csv_content';
    }
}
