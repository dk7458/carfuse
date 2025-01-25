import { fetchData, handleApiError } from './shared/utils.js';

/**
 * File Path: /frontend/assets/js/notifications.js
 * Description: Handles user notifications and alerts
 * Changelog:
 * - Implemented centralized error handling
 * - Added WebSocket support for real-time notifications
 */

document.addEventListener('DOMContentLoaded', () => {
  const WS_CONFIG = {
    reconnectDelay: 3000,
    endpoints: {
      fetch: '/public/api.php?endpoint=notifications&action=fetch'
    }
  };

  const elements = {
    notificationsList: document.getElementById('notificationsList'),
    badge: document.getElementById('notificationBadge')
  };

  let wsConnection = null;

  async function fetchNotificationData() {
    try {
      const notificationData = await fetchData('/public/api.php', {
        endpoint: 'notifications',
        method: 'GET',
        params: { action: 'fetch' }
      });

      updateNotifications(notificationData);
    } catch (error) {
      handleApiError(error, 'fetching notifications');
    }
  }

  function updateNotifications({ notifications, unreadCount }) {
    renderNotificationList(notifications);
    updateNotificationBadge(unreadCount);
  }

  async function markAsRead(notificationId) {
    try {
      await fetchData(NOTIFICATION_CONFIG.endpoints.markRead, {
        method: 'POST',
        body: {
          notification_id: notificationId
        }
      });
    } catch (error) {
      handleApiError(error, 'marking notification as read');
    }
  }

  // WebSocket connection handler
  function initializeWebSocket() {
    wsConnection = new WebSocket(WS_URL);
    wsConnection.onmessage = handleWebSocketMessage;
    wsConnection.onclose = handleWebSocketClose;
    wsConnection.onerror = handleWebSocketError;
  }

  function handleWebSocketClose() {
    setTimeout(initializeWebSocket, WS_CONFIG.reconnectDelay);
  }

  function handleWebSocketError(error) {
    handleApiError(error, 'WebSocket connection');
  }

  // Initialize
  fetchNotificationData();
  initializeWebSocket();
});
