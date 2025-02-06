import ajax from './ajax';

document.addEventListener('DOMContentLoaded', function () {
    fetchNotifications();
});

/**
 * Pobiera powiadomienia z serwera.
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
    }
}

/**
 * Wyświetla powiadomienia w interfejsie użytkownika.
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
 * Dodaje obsługę kliknięcia przycisku "Oznacz jako przeczytane".
 */
function attachMarkAsReadListeners() {
    document.querySelectorAll('.mark-as-read').forEach(button => {
        button.addEventListener('click', function () {
            markAsRead(this.dataset.id);
        });
    });
}

/**
 * Oznacza powiadomienie jako przeczytane.
 */
async function markAsRead(notificationId) {
    try {
        const response = await fetch(`/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getAuthToken()
            }
        });

        const data = await response.json();

        if (data.success) {
            updateNotificationStatus(notificationId);
        } else {
            console.error('Błąd oznaczania powiadomienia jako przeczytanego:', data.error);
        }
    } catch (error) {
        console.error('Błąd oznaczania powiadomienia jako przeczytanego:', error);
    }
}

/**
 * Aktualizuje status powiadomienia bez ponownego ładowania wszystkich powiadomień.
 */
function updateNotificationStatus(notificationId) {
    const notificationElement = document.querySelector(`.mark-as-read[data-id="${notificationId}"]`);
    if (notificationElement) {
        notificationElement.closest('.notification').classList.add('read');
        notificationElement.remove();
    }
}

/**
 * Wyświetla informację, gdy brak powiadomień.
 */
function displayNoNotificationsMessage() {
    const notificationsContainer = document.getElementById('notifications-container');
    if (!notificationsContainer) return;

    notificationsContainer.innerHTML = `<p class="text-muted">Brak nowych powiadomień.</p>`;
}

/**
 * Pobiera token autoryzacyjny użytkownika.
 */
function getAuthToken() {
    return localStorage.getItem('auth_token') || '';
}
