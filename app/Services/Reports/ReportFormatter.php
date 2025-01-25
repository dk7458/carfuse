<?php

namespace App\Services\Reports;

interface ReportFormatter
{
    /**
     * Convert a raw report array to a specific format (CSV, PDF, JSON, etc.).
     */
    public function format(array $report): string;
}
