<?php
require_once '/home/u122931475/domains/carfuse.pl/public_html/config.php';
// File Path: /views/admin/admin_calendar.php
/**
 * Description: Enhanced admin calendar with tooltips and conflict notifications for rescheduling.
 * Changelog:
 * - Added tooltips for detailed event information.
 * - Improved conflict notifications with detailed messages.
 * - Added visual indicators for vehicle maintenance schedules.
 */

require_once BASE_PATH . 'includes/session_middleware.php';

require_once BASE_PATH . 'includes/db_connect.php';

require_once BASE_PATH . 'functions/global.php';


// Enforce role-based access
enforceRole(['admin', 'super_admin'], '/public/login.php');

// Fetch data using the centralized proxy
$filters = [
    'search' => $_GET['search'] ?? '',
    'startDate' => $_GET['start_date'] ?? '',
    'endDate' => $_GET['end_date'] ?? ''
];
$queryString = http_build_query($filters);
$response = file_get_contents(BASE_URL . "/public/api.php?endpoint=calendar&action=fetch_events&" . $queryString);
$data = json_decode($response, true);

if ($data['success']) {
    $events = $data['events'];
    foreach ($events as $event) {
        echo "<tr>
            <td>{$event['title']}</td>
            <td>{$event['start_date']}</td>
            <td>{$event['end_date']}</td>
            <td>{$event['description']}</td>
        </tr>";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalendarz Administratora</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.css">
    <link rel="stylesheet" href="/public/assets/css/theme.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert2 for better notifications -->
</head>
<body>
    <?php include '../../views/shared/navbar_admin.php'; ?>

    <div class="container mt-5">
        <h1 class="text-center">Kalendarz Administratora</h1>
        <div id="calendar"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                editable: true,
                eventDidMount: function (info) {
                    // Add tooltips with event details
                    const tooltipContent = `
                        <strong>${info.event.title}</strong><br>
                        <strong>Data odbioru:</strong> ${info.event.start.toLocaleDateString()}<br>
                        <strong>Data zwrotu:</strong> ${info.event.end.toLocaleDateString()}<br>
                    `;
                    info.el.setAttribute('title', tooltipContent);
                },
                events: [
                    <?php foreach ($bookings as $booking): ?>
                    {
                        id: '<?= $booking['id'] ?>',
                        title: '<?= htmlspecialchars($booking['user'] . ': ' . $booking['make'] . ' ' . $booking['model']) ?>',
                        start: '<?= $booking['pickup_date'] ?>',
                        end: '<?= date('Y-m-d', strtotime($booking['dropoff_date'] . ' +1 day')) ?>',
                        backgroundColor: 'rgba(75, 192, 192, 1)',
                        extendedProps: {
                            totalPrice: '<?= $booking['total_price'] ?> PLN'
                        }
                    },
                    <?php endforeach; ?>
                    <?php foreach ($maintenanceSchedules as $maintenance): ?>
                    {
                        id: 'maintenance-<?= $maintenance['id'] ?>',
                        title: 'Maintenance: <?= htmlspecialchars($maintenance['make'] . ' ' . $maintenance['model']) ?>',
                        start: '<?= $maintenance['maintenance_date'] ?>',
                        backgroundColor: 'rgba(255, 99, 132, 1)',
                        extendedProps: {
                            description: '<?= htmlspecialchars($maintenance['description']) ?>'
                        }
                    },
                    <?php endforeach; ?>
                ],
                eventDrop: function (info) {
                    // Send rescheduling data to the back-end
                    fetch('/controllers/calendar_ctrl.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'reschedule_booking',
                            booking_id: info.event.id,
                            new_start: info.event.start.toISOString().split('T')[0],
                            new_end: info.event.end.toISOString().split('T')[0]
                        })
                    })
                        .then(response => response.json())
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
                                    text: data.error || "Wystąpił problem podczas zmiany rezerwacji.",
                                    confirmButtonText: 'OK'
                                });
                                info.revert(); // Revert the event to its original position
                            }
                        })
                        .catch((error) => {
                            console.error("Error:", error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Błąd sieci',
                                text: 'Nie udało się połączyć z serwerem. Spróbuj ponownie później.',
                                confirmButtonText: 'OK'
                            });
                            info.revert(); // Revert the event to its original position
                        });
                }
            });

            calendar.render();
        });
    </script>
</body>
</html>
