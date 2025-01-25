<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class ReportService
{
    private array $config;
    private \Illuminate\Log\LogManager $logger;
    private ReportValidator $validator;

    public function __construct()
    {
        $this->config = Config::get('reports');
        $this->logger = Log::channel('reports');
        $this->validator = new ReportValidator();
    }

    /**
     * Generate a report for a certain type and period.
     */
    public function generateReport(
        string $type,
        string $period,
        Carbon $startDate,
        Carbon $endDate,
        array $options = []
    ): array {
        try {
            $this->logger->info("Generating {$type} report for period: {$period}");

            $data = $this->fetchReportData($type, $startDate, $endDate);
            $template = $this->config['types'][$type]['template'] ?? null;

            $report = [
                'type'        => $type,
                'period'      => $period,
                'start_date'  => $startDate->toDateString(),
                'end_date'    => $endDate->toDateString(),
                'generated_at'=> now(),
                'data'        => $data,
            ];

            return [
                'success'  => true,
                'report'   => $report,
                'template' => $template,
            ];
        } catch (Exception $e) {
            $this->logger->error("Report generation failed", ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Export a generated report using a chosen format (csv, pdf, json).
     */
    public function exportReport(array $report, string $format): string
    {
        try {
            if (!$this->validator->validate($report)) {
                throw new Exception("Report validation failed");
            }

            $formatter = $this->getFormatter($format);
            $filename = $this->generateFilename($report['type'], $format);
            $content = $formatter->format($report);

            $path = $this->config['delivery']['local']['path'] . '/' . $filename;
            file_put_contents($path, $content);

            $this->logger->info("Report exported", [
                'format' => $format,
                'path'   => $path,
            ]);

            return $path;
        } catch (Exception $e) {
            $this->logger->error("Report export failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Deliver a report via the specified method (email, sftp, etc.).
     */
    public function sendReport(string $path, string $method, array $options = []): bool
    {
        try {
            $delivery = $this->getDeliveryMethod($method);
            return $delivery->send($path, $options);
        } catch (Exception $e) {
            $this->logger->error("Report delivery failed", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Fetch report data from the DB, chunking by the configured size for performance.
     */
    private function fetchReportData(string $type, Carbon $startDate, Carbon $endDate): array
    {
        $chunkSize = $this->config['types'][$type]['query_chunk_size'] ?? 1000;
        $data = [];

        switch ($type) {
            case 'revenue':
                DB::table('transactions')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->orderBy('id')
                    ->chunk($chunkSize, function ($transactions) use (&$data) {
                        foreach ($transactions as $transaction) {
                            $data[] = $transaction;
                        }
                    });
                break;
            
            // Add other type cases here if needed
        }

        return $data;
    }

    /**
     * Instantiate the correct formatter for a given format.
     */
    private function getFormatter(string $format): ReportFormatter
    {
        return match ($format) {
            'csv'  => new CSVFormatter(),
            'pdf'  => new PDFFormatter(),
            'json' => new JSONFormatter(),
            default => throw new Exception("Unsupported format: {$format}"),
        };
    }

    /**
     * Build a filename for the exported report.
     */
    private function generateFilename(string $type, string $format): string
    {
        return sprintf(
            '%s_%s.%s',
            $type,
            date('Y-m-d_His'),
            $format
        );
    }

    /**
     * Get the appropriate delivery method instance.
     */
    private function getDeliveryMethod(string $method): ReportDelivery
    {
        return match ($method) {
            'email' => new EmailDelivery(),
            'sftp'  => new SFTPDelivery(),
            default => throw new Exception("Unsupported delivery method: {$method}"),
        };
    }
}
