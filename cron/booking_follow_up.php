<?php
/**
 * Ścieżka pliku: /cron/booking_follow_up.php
 * Opis: Wysyła powiadomienia przypominające o rezerwacjach, takie jak przypomnienia o nadchodzących rezerwacjach, prośby o opinię po zakończeniu rezerwacji lub powiadomienia o niedokończonych rezerwacjach.
 * Historia zmian:
 * - Wersja początkowa dla powiadomień dotyczących rezerwacji.
 */

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'includes/functions.php';

require_once BASE_PATH . 'includes/notifications.php';


header('Content-Type: text/plain; charset=UTF-8');

// Funkcja do logowania akcji
function logFollowUpAction($message) {
    echo date('[Y-m-d H:i:s] ') . $message . "\n";
}

try {
    // ==========================
    // Pobierz nadchodzące rezerwacje (przypomnienia)
    // ==========================
    $reminderQuery = "
        SELECT b.id, b.pickup_date, u.email, u.phone, f.make, f.model 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN fleet f ON b.vehicle_id = f.id
        WHERE b.status = 'active' AND DATE(b.pickup_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    ";
    $upcomingBookings = $conn->query($reminderQuery);

    while ($booking = $upcomingBookings->fetch_assoc()) {
        $message = "Przypomnienie: Twoja rezerwacja na {$booking['make']} {$booking['model']} jest zaplanowana na jutro ({$booking['pickup_date']}).";

        // Wyślij e-mail
        if (!empty($booking['email'])) {
            sendNotification('email', $booking['email'], 'Przypomnienie o nadchodzącej rezerwacji', $message);
        }

        // Wyślij SMS
        if (!empty($booking['phone'])) {
            sendNotification('sms', $booking['phone'], null, $message);
        }

        logFollowUpAction("Wysłano przypomnienie dla ID rezerwacji: {$booking['id']}");
    }

    // ==========================
    // Pobierz prośby o opinię po zakończeniu rezerwacji
    // ==========================
    $feedbackQuery = "
        SELECT b.id, b.dropoff_date, u.email, u.phone, f.make, f.model 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN fleet f ON b.vehicle_id = f.id
        WHERE b.status = 'completed' AND DATE(b.dropoff_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    ";
    $completedBookings = $conn->query($feedbackQuery);

    while ($booking = $completedBookings->fetch_assoc()) {
        $message = "Mamy nadzieję, że korzystanie z pojazdu {$booking['make']} {$booking['model']} było dla Ciebie przyjemne. Prosimy o podzielenie się swoją opinią na temat doświadczenia z rezerwacji.";

        // Wyślij e-mail
        if (!empty($booking['email'])) {
            sendNotification('email', $booking['email'], 'Twoja opinia jest dla nas ważna!', $message);
        }

        // Wyślij SMS
        if (!empty($booking['phone'])) {
            sendNotification('sms', $booking['phone'], null, $message);
        }

        logFollowUpAction("Wysłano prośbę o opinię dla ID rezerwacji: {$booking['id']}");
    }

    // ==========================
    // Pobierz niedokończone rezerwacje (follow-up)
    // ==========================
    $incompleteQuery = "
        SELECT b.id, u.email, u.phone, f.make, f.model 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN fleet f ON b.vehicle_id = f.id
        WHERE b.status = 'pending' AND DATE(b.created_at) < DATE_SUB(CURDATE(), INTERVAL 3 DAY)
    ";
    $incompleteBookings = $conn->query($incompleteQuery);

    while ($booking = $incompleteBookings->fetch_assoc()) {
        $message = "Wygląda na to, że nie dokończyłeś rezerwacji na {$booking['make']} {$booking['model']}. Dokończ rezerwację wkrótce, aby zapewnić sobie dostępność pojazdu.";

        // Wyślij e-mail
        if (!empty($booking['email'])) {
            sendNotification('email', $booking['email'], 'Dokończ swoją rezerwację', $message);
        }

        // Wyślij SMS
        if (!empty($booking['phone'])) {
            sendNotification('sms', $booking['phone'], null, $message);
        }

        logFollowUpAction("Wysłano przypomnienie o niedokończonej rezerwacji dla ID rezerwacji: {$booking['id']}");
    }

} catch (Exception $e) {
    logError("Błąd w procesie follow-up: " . $e->getMessage());
    logFollowUpAction("Błąd: " . $e->getMessage());
}

logFollowUpAction("Proces follow-up dla rezerwacji zakończony.");
