import { fetchData, handleApiError } from './shared/utils.js';

/**
 * File Path: /frontend/assets/js/notifications.js
 * Description: Handles user notifications and alerts
 * Changelog:
 * - Implemented centralized error handling
 * - Added WebSocket support for real-time notifications
 */

document.addEventListener('DOMContentLoaded', () => {
    let ws = null;
    const notificationsList = document.getElementById('notificationsList');

    async function fetchNotifications() {
        try {
            const data = await fetchData('/public/api.php', {
                endpoint: 'notifications',
                method: 'GET',
                params: { action: 'fetch' }
            });

            renderNotifications(data.notifications);
            updateBadge(data.unreadCount);
        } catch (error) {
            handleApiError(error, 'fetching notifications');
        }
    }

    async function markAsRead(notificationId) {
        try {
            await fetchData('/public/api.php', {
                endpoint: 'notifications',
                method: 'POST',
                body: {
                    action: 'mark_read',
                    notification_id: notificationId
                }
            });
        } catch (error) {
            handleApiError(error, 'marking notification as read');
        }
    }

    // WebSocket connection
    function initializeWebSocket() {
        ws = new WebSocket(WS_URL);
        ws.onmessage = handleWebSocketMessage;
        ws.onclose = () => setTimeout(initializeWebSocket, 3000);
    }

    // Initialize
    fetchNotifications();
    initializeWebSocket();
});
