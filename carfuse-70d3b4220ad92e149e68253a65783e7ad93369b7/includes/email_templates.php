<?php

/**
 * Get the email template for booking confirmation.
 * 
 * @param string $customerName
 * @param int $bookingId
 * @param string $contractLink
 * @return string
 */
function getBookingConfirmationEmail($customerName, $bookingId, $contractLink) {
    return "
        <h1>Potwierdzenie Rezerwacji</h1>
        <p>Drogi $customerName,</p>
        <p>Twoja rezerwacja o ID <strong>$bookingId</strong> została pomyślnie zarejestrowana.</p>
        <p>Możesz przeglądać i podpisać swoją umowę tutaj: 
        <a href='$contractLink' target='_blank'>Zobacz Umowę</a>.</p>
        <p>Dziękujemy za skorzystanie z naszych usług!</p>
    ";
}

/**
 * Get the email template for contract signing reminders.
 * 
 * @param string $customerName
 * @param string $signingLink
 * @return string
 */
function getContractReminderEmail($customerName, $signingLink) {
    return "
        <h1>Przypomnienie o Podpisaniu Umowy</h1>
        <p>Drogi $customerName,</p>
        <p>Przypominamy o konieczności podpisania umowy najmu. Kliknij poniższy link, aby podpisać:</p>
        <p><a href='$signingLink' target='_blank'>Podpisz Umowę</a></p>
        <p>Dziękujemy za wybór naszej firmy!</p>
    ";
}

/**
 * Get the email template for admin alerts.
 * 
 * @param string $alertMessage
 * @return string
 */
function getAdminAlertEmail($alertMessage) {
    return "
        <h1>Powiadomienie Administratora</h1>
        <p>$alertMessage</p>
    ";
}

function renderTemplate($template, $placeholders) {
    foreach ($placeholders as $key => $value) {
        $template = str_replace("{{ $key }}", $value, $template);
    }
    return $template;
}

function getEmailTemplate($type, $placeholders = []) {
    $templates = [
        'booking_reminder' => "
            <h1>Przypomnienie o Rezerwacji</h1>
            <p>Drogi {{ user_name }},</p>
            <p>Przypominamy, że Twoja rezerwacja zaczyna się {{ pickup_date }}.</p>
        ",
        'contract_expiration' => "
            <h1>Powiadomienie o Wygaśnięciu Kontraktu</h1>
            <p>Kontrakt na pojazd {{ vehicle_details }} wygasa {{ expiration_date }}.</p>
        ",
    ];

    return isset($templates[$type]) ? renderTemplate($templates[$type], $placeholders) : '';
}


?>
