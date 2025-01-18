<?php

use Dompdf\Dompdf;

/**
 * Generate a rental contract as a PDF.
 * 
 * @param string $htmlContent The HTML content of the contract.
 * @param string $outputPath The file path to save the generated PDF.
 * @param string|null $signaturePath Path to the owner's signature image (optional).
 */
function generatePDF($htmlContent, $outputPath, $signaturePath = null) {
    // Initialize Dompdf
    $dompdf = new Dompdf();
    $dompdf->loadHtml($htmlContent);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Add the owner's signature if provided
    if ($signaturePath) {
        $canvas = $dompdf->getCanvas();
        $canvas->image($signaturePath, 50, $canvas->get_height() - 100, 150, 50); // Adjust position and size
    }

    // Save the PDF to the specified path
    file_put_contents($outputPath, $dompdf->output());
}

/**
 * Generate the HTML content for a rental contract.
 * 
 * @param array $booking Booking details.
 * @param array $vehicle Vehicle details.
 * @param array $customer Customer details.
 * @return string
 */
function generateContractHTML($booking, $vehicle, $customer) {
    $pickupDate = date('d-m-Y', strtotime($booking['pickup_date']));
    $dropoffDate = date('d-m-Y', strtotime($booking['dropoff_date']));
    $totalPrice = number_format($booking['total_price'], 2, ',', ' ');

    return "
        <h1>Umowa Najmu Pojazdu</h1>
        <p><strong>Data zawarcia umowy:</strong> " . date('d-m-Y') . "</p>
        <h2>Strony Umowy</h2>
        <p><strong>Wynajmujący:</strong> Carfuse Sp. z o.o., ul. Przykładowa 12, 00-001 Warszawa</p>
        <p><strong>Najemca:</strong> {$customer['name']}, adres: {$customer['address']}, dowód osobisty: {$customer['id_number']}</p>
        <h2>Dane Pojazdu</h2>
        <p><strong>Marka:</strong> {$vehicle['make']}</p>
        <p><strong>Model:</strong> {$vehicle['model']}</p>
        <p><strong>Numer Rejestracyjny:</strong> {$vehicle['registration_number']}</p>
        <p><strong>Stan Techniczny:</strong> {$vehicle['condition']}</p>
        <h2>Szczegóły Najmu</h2>
        <p><strong>Okres Wynajmu:</strong> Od $pickupDate do $dropoffDate</p>
        <p><strong>Cena Całkowita:</strong> $totalPrice PLN</p>
        <h2>Podpisy</h2>
        <p><strong>Wynajmujący:</strong> ___________________________</p>
        <p><strong>Najemca:</strong> ___________________________</p>
    ";
}
?>
