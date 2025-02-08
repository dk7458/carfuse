import ajax from './ajax';
import { showErrorToast, showSuccessToast } from './toasts';

document.addEventListener('DOMContentLoaded', function () {
    fetchNotifications();
    setInterval(fetchNotifications, 30000); // Refresh notifications every 30 seconds
});

/**
 * Fetches notifications from the server.
 */
async function fetchNotifications() {
    try {
        const notifications = await ajax.get('/notifications');
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
            <p>${notification.message}</p>
            <button class="mark-as-read" data-id="${notification.id}">Oznacz jako przeczytane</button>
        `;
        notificationsContainer.appendChild(notificationElement);
    });

    attachMarkAsReadListeners();
}

/**
 * Adds event listeners to "Mark as Read" buttons.
 */
function attachMarkAsReadListeners() {
    document.querySelectorAll('.mark-as-read').forEach(button => {
        button.addEventListener('click', function () {
            markAsRead(this.dataset.id);
        });
    });
}

/**
 * Marks a notification as read.
 */
async function markAsRead(notificationId) {
    try {
        const response = await ajax.post(`/notifications/${notificationId}/read`);
        if (response.success) {
            updateNotificationStatus(notificationId);
            showSuccessToast('Powiadomienie oznaczone jako przeczytane.');
        } else {
            showErrorToast(response.error || 'Błąd oznaczania powiadomienia jako przeczytanego.');
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
        notificationElement.closest('.notification').classList.add('read');
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
