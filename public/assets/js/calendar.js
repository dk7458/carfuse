// calendar.js
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const userRole = document.getElementById('calendar').dataset.role; // "user" or "admin"
    const fetchEventsUrl = userRole === 'admin' 
        ? '/controllers/calendar_ctrl.php?action=fetch_all_events' 
        : '/controllers/calendar_ctrl.php?action=fetch_user_events';
    const saveEventUrl = '/controllers/calendar_ctrl.php?action=save_event';
    const deleteEventUrl = '/controllers/calendar_ctrl.php?action=delete_event';

    // Initialize the FullCalendar instance
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        selectable: true,
        editable: userRole === 'admin', // Allow drag-and-drop for admin
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: fetchEventsUrl,
        eventDidMount: function (info) {
            // Add tooltips with event details
            const tooltipContent = `
                <strong>${info.event.title}</strong><br>
                <strong>Start:</strong> ${info.event.start.toLocaleString()}<br>
                <strong>End:</strong> ${info.event.end ? info.event.end.toLocaleString() : 'N/A'}<br>
                <strong>Description:</strong> ${info.event.extendedProps.description || 'N/A'}
            `;
            info.el.setAttribute('title', tooltipContent);

            // Add visual indicators for maintenance schedules
            if (info.event.extendedProps.type === 'maintenance') {
                info.el.style.backgroundColor = 'rgba(255, 99, 132, 1)';
            }
        },
        eventClick: function (info) {
            // Show event details in a modal
            handleEventClick(info.event);
        },
        dateClick: function (info) {
            // Admins can create new events
            if (userRole === 'admin') handleDateClick(info.dateStr);
        },
        eventDrop: function (info) {
            if (userRole === 'admin') handleEventUpdate(info.event);
        },
        eventResize: function (info) {
            if (userRole === 'admin') handleEventUpdate(info.event);
        }
    });

    calendar.render();

    // Handle event click
    function handleEventClick(event) {
        const modal = new bootstrap.Modal(document.getElementById('eventModal'));
        document.getElementById('modal-title').innerText = `Event: ${event.title}`;
        document.getElementById('modal-body').innerHTML = `
            <p><strong>Start:</strong> ${event.start.toLocaleString()}</p>
            <p><strong>End:</strong> ${event.end ? event.end.toLocaleString() : 'N/A'}</p>
            <p><strong>Description:</strong> ${event.extendedProps.description || 'N/A'}</p>
        `;
        if (userRole === 'admin') {
            document.getElementById('delete-event-btn').dataset.id = event.id;
            document.getElementById('delete-event-btn').classList.remove('d-none');
        } else {
            document.getElementById('delete-event-btn').classList.add('d-none');
        }
        modal.show();
    }

    // Handle date click (for creating new events)
    function handleDateClick(dateStr) {
        const modal = new bootstrap.Modal(document.getElementById('eventFormModal'));
        document.getElementById('event-date').value = dateStr;
        modal.show();
    }

    // Handle event update (drag/drop or resize)
    function handleEventUpdate(event) {
        const data = {
            id: event.id,
            start: event.start.toISOString(),
            end: event.end ? event.end.toISOString() : null,
            action: 'update_event'
        };

        if (detectConflicts(event)) {
            alert('Event conflicts with an existing booking.');
            calendar.refetchEvents(); // Revert the change if there's a conflict
            return;
        }

        fetch(saveEventUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Event updated successfully!');
            } else {
                alert(result.error || 'An error occurred.');
                calendar.refetchEvents(); // Revert the change if there's an error
            }
        });
    }

    // Handle delete event
    document.getElementById('delete-event-btn').addEventListener('click', function () {
        const eventId = this.dataset.id;
        if (confirm('Are you sure you want to delete this event?')) {
            fetch(deleteEventUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: eventId, action: 'delete_event' })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Event deleted successfully!');
                    calendar.refetchEvents();
                } else {
                    alert(result.error || 'An error occurred.');
                }
            });
        }
    });

    // Handle form submission for new events
    document.getElementById('event-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        fetch(saveEventUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Event created successfully!');
                calendar.refetchEvents();
                new bootstrap.Modal(document.getElementById('eventFormModal')).hide();
            } else {
                alert(result.error || 'An error occurred.');
            }
        });
    });

    // Conflict detection for overlapping bookings
    function detectConflicts(event) {
        const events = calendar.getEvents();
        for (let existingEvent of events) {
            if (existingEvent.id !== event.id && (
                (event.start >= existingEvent.start && event.start < existingEvent.end) ||
                (event.end > existingEvent.start && event.end <= existingEvent.end) ||
                (event.start <= existingEvent.start && event.end >= existingEvent.end)
            )) {
                return true;
            }
        }
        return false;
    }

    // Notifications for event reminders or updates
    function sendNotification(message) {
        if (Notification.permission === 'granted') {
            new Notification('Calendar Notification', { body: message });
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    new Notification('Calendar Notification', { body: message });
                }
            });
        }
    }

    // Example usage of sendNotification
    sendNotification('You have an upcoming event!');
});
