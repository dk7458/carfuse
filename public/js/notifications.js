import ajax from './ajax';
import { showErrorToast, showSuccessToast } from './toasts';

let lastFetchTimestamp = 0;
const FETCH_INTERVAL = 30000; // 30 seconds

document.addEventListener('DOMContentLoaded', function () {
    if (document.visibilityState === 'visible') {
        fetchNotifications();
    }
    document.addEventListener('visibilitychange', handleVisibilityChange);
    document.addEventListener('newEvent', handleNewEvent); // Custom event for new actions
    setInterval(fetchNotifications, FETCH_INTERVAL); // Polling mechanism

    // Event delegation for dynamically loaded notifications
    document.getElementById('notifications-container').addEventListener('click', function (event) {
        if (event.target.classList.contains('mark-as-read')) {
            markAsRead(event.target.dataset.id);
        }
    });
});

/**
 * Handles visibility change events.
 */
function handleVisibilityChange() {
    if (document.visibilityState === 'visible') {
        fetchNotifications();
    }
}

/**
 * Handles new events that require fresh notifications.
 */
function handleNewEvent() {
    fetchNotifications();
}

/**
 * Fetches notifications from the server.
 */
async function fetchNotifications() {
    const now = Date.now();
    if (now - lastFetchTimestamp < FETCH_INTERVAL) return; // Prevent redundant calls

    lastFetchTimestamp = now;
    try {
        const response = await fetch('/api/user/notifications.php');
        if (response.status === 401) {
            showErrorToast('Musisz być zalogowany, aby zobaczyć powiadomienia.');
            return;
        }
        const notifications = await response.json();
        if (notifications.length > 0) {
            displayNotifications(notifications);
        } else {
            displayNoNotificationsMessage();
        }
    } catch (error) {
        console.error('Błąd pobierania powiadomień:', error);
        showErrorToast('Nie udało się pobrać powiadomień.');
    }
}

/**
 * Displays notifications in the UI.
 */
function displayNotifications(notifications) {
    const notificationsContainer = document.getElementById('notifications-container');
    if (!notificationsContainer) return;

    notificationsContainer.innerHTML = '';

    notifications.forEach(notification => {
        const notificationElement = document.createElement('div');
        notificationElement.className = `notification ${notification.read ? 'read' : 'unread'}`;
        notificationElement.innerHTML = `
            <p style="font-weight: ${notification.read ? 'normal' : 'bold'};">${notification.message}</p>
            <button class="mark-as-read" data-id="${notification.id}">Oznacz jako przeczytane</button>
        `;
        notificationsContainer.appendChild(notificationElement);
    });
}

/**
 * Marks a notification as read.
 */
async function markAsRead(notificationId) {
    try {
        const response = await fetch(`/api/user/notifications.php/${notificationId}/read`, {
            method: 'POST'
        });
        const result = await response.json();
        if (result.success) {
            updateNotificationStatus(notificationId);
            showSuccessToast('Powiadomienie oznaczone jako przeczytane.');
        } else {
            showErrorToast(result.error || 'Błąd oznaczania powiadomienia jako przeczytanego.');
        }
    } catch (error) {
        console.error('Błąd oznaczania powiadomienia:', error);
        showErrorToast('Nie udało się oznaczyć powiadomienia.');
    }
}

/**
 * Updates the UI when a notification is marked as read.
 */
function updateNotificationStatus(notificationId) {
    const notificationElement = document.querySelector(`.mark-as-read[data-id="${notificationId}"]`);
    if (notificationElement) {
        const parentElement = notificationElement.closest('.notification');
        parentElement.classList.add('read');
        parentElement.querySelector('p').style.fontWeight = 'normal';
        notificationElement.remove();
    }
}

/**
 * Displays a message when there are no notifications.
 */
function displayNoNotificationsMessage() {
    const notificationsContainer = document.getElementById('notifications-container');
    if (!notificationsContainer) return;

    notificationsContainer.innerHTML = `<p class="text-muted">Brak nowych powiadomień.</p>`;
}
