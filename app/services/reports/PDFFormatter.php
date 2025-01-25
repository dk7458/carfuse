<?php

namespace App\Services\Reports;

class PDFFormatter implements ReportFormatter
{
    public function format(array $report): string
    {
        // Basic PDF conversion logic
        // TODO: Possibly integrate a library like dompdf
        return 'pdf_content';
    }
}
