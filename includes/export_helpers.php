<?php
/**
 * File Path: /includes/export_helpers.php
 * Description: Provides helper functions for exporting data in CSV and PDF formats.
 * Changelog:
 * - Added `exportToCSV` function.
 * - Added `exportToPDF` function.
 * - Improved error handling and added support for custom headers.
 */

require_once __DIR__ . '/pdf_generator.php';

/**
 * Exports data to a CSV file and streams it to the client.
 *
 * @param array $data Array of associative arrays representing rows.
 * @param string $filename Name of the CSV file to download.
 * @param array|null $headers Optional custom headers for the CSV file.
 */
function exportToCSV(array $data, string $filename = 'report.csv', array $headers = null): void
{
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment;filename=\"$filename\"");

    $output = fopen('php://output', 'w');

    if (!empty($data)) {
        // Write the header row
        fputcsv($output, $headers ?? array_keys($data[0]));

        // Write each row
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    } else {
        // Write an empty message if no data is available
        fputcsv($output, ['No data available']);
    }

    fclose($output);
    exit;
}

/**
 * Generates PDF content and streams it to the client.
 *
 * @param string $title Title of the report.
 * @param array $data Array of associative arrays representing rows.
 * @param string $filename Name of the PDF file to download.
 * @param string|null $dateFrom Start date of the report (optional).
 * @param string|null $dateTo End date of the report (optional).
 */
function exportToPDF(string $title, array $data, string $filename = 'report.pdf', ?string $dateFrom = null, ?string $dateTo = null): void
{
    $htmlContent = generatePDFContent($title, $data, $dateFrom, $dateTo);
    generatePDF($htmlContent, 'php://output');

    header('Content-Type: application/pdf');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    exit;
}

/**
 * Generates HTML content for PDF export.
 *
 * @param string $title Title of the report.
 * @param array $data Array of associative arrays representing rows.
 * @param string|null $dateFrom Start date of the report (optional).
 * @param string|null $dateTo End date of the report (optional).
 * @return string HTML content for the PDF.
 */
function generatePDFContent(string $title, array $data, ?string $dateFrom = null, ?string $dateTo = null): string
{
    ob_start();
    ?>
    <h1><?= htmlspecialchars($title) ?></h1>
    <?php if ($dateFrom && $dateTo): ?>
        <p>Okres: <?= htmlspecialchars($dateFrom) ?> - <?= htmlspecialchars($dateTo) ?></p>
    <?php endif; ?>
    <table border="1" cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <?php foreach (array_keys($data[0] ?? []) as $header): ?>
                    <th><?= htmlspecialchars(ucfirst($header)) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                        <td><?= htmlspecialchars($cell) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();
}
?>
