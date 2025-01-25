<?php

namespace App\Services;

interface ReportFormatter
{
    public function format(array $report): string;
}
