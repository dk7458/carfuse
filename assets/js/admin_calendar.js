document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        editable: true, // Enable drag-and-drop
        events: '/controllers/calendar_ctrl.php?action=fetch_calendar_data',
        eventDidMount: function (info) {
            // Add tooltips with event details
            const tooltipContent = `
                <strong>${info.event.title}</strong><br>
                <strong>Data odbioru:</strong> ${info.event.start.toLocaleDateString()}<br>
                <strong>Data zwrotu:</strong> ${info.event.end.toLocaleDateString()}<br>
            `;
            info.el.setAttribute('title', tooltipContent);
        },
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
        },
    });

    calendar.render();
});
