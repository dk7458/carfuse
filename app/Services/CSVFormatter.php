<?php

namespace App\Services;

class CSVFormatter implements ReportFormatter
{
    public function format(array $report): string
    {
        // CSV conversion logic here
        return 'csv_content';
    }
}
