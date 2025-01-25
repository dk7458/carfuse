
<?php

namespace App\Services;

class PDFFormatter implements ReportFormatter
{
    public function format(array $report): string
    {
        // PDF conversion logic here
        return 'pdf_content';
    }
}