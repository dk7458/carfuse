<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';

require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'functions/global.php';


// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/public/login.php');
}

$userId = $_SESSION['user_id'];

// Fetch bookings for the user's calendar
$stmt = $conn->prepare("
    SELECT 
        b.id, 
        b.pickup_date, 
        b.dropoff_date, 
        CONCAT(f.make, ' ', f.model) AS vehicle 
    FROM bookings b
    JOIN fleet f ON b.vehicle_id = f.id
    WHERE b.user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mój Kalendarz</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <link rel="stylesheet" href="/public/assets/css/theme.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert2 for better notifications -->
</head>
<body>
    <?php include '../shared/navbar_user.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Mój Kalendarz</h1>
        <div id="calendar"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                editable: true, // Enable drag-and-drop
                events: [
                    <?php foreach ($bookings as $booking): ?>
                    {
                        id: '<?= $booking['id'] ?>',
                        title: '<?= htmlspecialchars($booking['vehicle']) ?>',
                        start: '<?= $booking['pickup_date'] ?>',
                        end: '<?= date('Y-m-d', strtotime($booking['dropoff_date'] . ' +1 day')) ?>',
                        backgroundColor: 'rgba(75, 192, 192, 1)',
                    },
                    <?php endforeach; ?>
                ],
                eventDrop: function (info) {
                    const eventId = info.event.id;
                    const newStart = info.event.start.toISOString().split('T')[0];
                    const newEnd = info.event.end
                        ? info.event.end.toISOString().split('T')[0]
                        : newStart;

                    // Send reschedule request
                    fetch('/controllers/calendar_ctrl.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'reschedule_booking',
                            booking_id: eventId,
                            new_start: newStart,
                            new_end: newEnd,
                        }),
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Zaktualizowano',
                                    text: data.message,
                                    confirmButtonText: 'OK'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Błąd',
                                    text: data.error || 'Wystąpił problem podczas zmiany rezerwacji.',
                                    confirmButtonText: 'OK'
                                });
                                info.revert(); // Revert changes on error
                            }
                        })
                        .catch((error) => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Błąd sieci',
                                text: 'Nie udało się połączyć z serwerem. Spróbuj ponownie później.',
                                confirmButtonText: 'OK'
                            });
                            info.revert(); // Revert changes on exception
                        });
                }
            });
            calendar.render();
        });
    </script>
</body>
</html>
