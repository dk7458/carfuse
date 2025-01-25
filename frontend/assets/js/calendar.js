import { fetchData, showAlert, formatDate } from './shared/utils.js';

/**
 * File Path: /frontend/assets/js/calendar.js
 * Description: Manages calendar events, including fetching, creating, updating, and deleting events.
 * Changelog:
 * - Refactored to use shared utilities, modularized event handlers, and improved error handling.
 */

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const userRole = calendarEl.dataset.role; // Role determines admin/user actions

    const fetchEventsUrl = userRole === 'admin'
        ? '/public/api.php?endpoint=calendar&action=fetch_all_events'
        : '/public/api.php?endpoint=calendar&action=fetch_user_events';

    const saveEventUrl = '/public/api.php?endpoint=calendar&action=save_event';
    const deleteEventUrl = '/public/api.php?endpoint=calendar&action=delete_event';

    // Initialize FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        selectable: true,
        editable: userRole === 'admin',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: async function(fetchInfo, successCallback, failureCallback) {
            try {
                const events = await fetchData(fetchEventsUrl);
                successCallback(events);
            } catch (error) {
                console.error('Error fetching events:', error);
                showAlert('Error fetching events.', 'error');
                failureCallback(error);
            }
        },
        eventClick(info) {
            handleEventClick(info.event);
        },
        dateClick(info) {
            if (userRole === 'admin') handleDateClick(info.dateStr);
        },
        eventDrop(info) {
            if (userRole === 'admin') handleEventUpdate(info.event);
        },
        eventResize(info) {
            if (userRole === 'admin') handleEventUpdate(info.event);
        }
    });

    calendar.render();

    // Handle event click to show event details in a modal
    function handleEventClick(event) {
        const modal = new bootstrap.Modal(document.getElementById('eventModal'));
        document.getElementById('modal-title').innerText = `Event: ${event.title}`;
        document.getElementById('modal-body').innerHTML = `
            <p><strong>Start:</strong> ${formatDate(event.start)}</p>
            <p><strong>End:</strong> ${event.end ? formatDate(event.end) : 'N/A'}</p>
            <p><strong>Description:</strong> ${event.extendedProps.description || 'N/A'}</p>
        `;
        modal.show();
    }

    // Handle date click to create a new event
    function handleDateClick(dateStr) {
        const modal = new bootstrap.Modal(document.getElementById('eventFormModal'));
        document.getElementById('event-date').value = dateStr;
        modal.show();
    }

    // Handle event update (drag and drop or resize)
    async function handleEventUpdate(event) {
        const data = {
            id: event.id,
            start: event.start.toISOString(),
            end: event.end ? event.end.toISOString() : null
        };

        try {
            const result = await fetchData(saveEventUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (!result.success) {
                showAlert(result.error || 'Error updating the event.', 'error');
                calendar.refetchEvents();
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('Error updating the event.', 'error');
        }
    }
});
