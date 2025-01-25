<template>
  <div :class="['notification', type]" v-if="visible">
    <span>{{ message }}</span>
    <slot></slot>
    <button v-if="dismissible" @click="dismiss">Dismiss</button>
  </div>
  <div class="notifications-manager">
    <!-- Refresh Button -->
    <div class="controls">
      <button @click="fetchNotifications" class="refresh-btn">
        <i class="fas fa-sync"></i> Refresh
      </button>
      <span v-if="loading" class="loading">Loading...</span>
    </div>

    <!-- Notifications Table -->
    <div class="notifications-table">
      <table>
        <thead>
          <tr>
            <th>Type</th>
            <th>Message</th>
            <th>Status</th>
            <th>Time</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="notification in notifications" :key="notification.id"
              :class="{ 'unread': !notification.read }">
            <td>{{ notification.type }}</td>
            <td>{{ notification.message }}</td>
            <td>{{ notification.status }}</td>
            <td>{{ formatDate(notification.timestamp) }}</td>
            <td class="actions">
              <button @click="markAsRead(notification.id)" 
                      v-if="!notification.read">
                Mark Read
              </button>
              <button @click="resendNotification(notification.id)"
                      v-if="userRole === 'admin'">
                Resend
              </button>
              <button @click="deleteNotification(notification.id)" 
                      class="delete">
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Real-time notification toast -->
    <div v-if="newNotification" class="notification-toast">
      New notification received!
      <button @click="fetchNotifications">View</button>
    </div>
  </div>
</template>

<script>
import { io } from 'socket.io-client'
import { format } from 'date-fns'

export default {
  name: 'NotificationsManager',
  
  props: {
    userRole: {
      type: String,
      required: true,
      validator: (value) => ['admin', 'user'].includes(value)
    },
    userId: {
      type: String,
      required: true
    },
    message: {
      type: String,
      required: true
    },
    type: {
      type: String,
      required: true
    },
    dismissible: {
      type: Boolean,
      default: true
    }
  },

  data() {
    return {
      notifications: [],
      loading: false,
      socket: null,
      newNotification: false,
      visible: true
    }
  },

  async created() {
    await this.fetchNotifications()
    this.initializeWebSocket()
  },

  beforeUnmount() {
    if (this.socket) {
      this.socket.disconnect()
    }
  },

  methods: {
    // Fetch notifications from server
    // GET /api/notifications?userId={userId}&role={userRole}
    async fetchNotifications() {
      this.loading = true
      try {
        const response = await fetch(
          `/api/notifications?userId=${this.userId}&role=${this.userRole}`
        )
        this.notifications = await response.json()
        this.newNotification = false
      } catch (error) {
        console.error('Failed to fetch notifications:', error)
      } finally {
        this.loading = false
      }
    },

    // Mark notification as read
    // PUT /api/notifications/{id}/read
    async markAsRead(id) {
      try {
        await fetch(`/api/notifications/${id}/read`, {
          method: 'PUT'
        })
        this.notifications = this.notifications.map(notif =>
          notif.id === id ? { ...notif, read: true } : notif
        )
      } catch (error) {
        console.error('Failed to mark notification as read:', error)
      }
    },

    // Resend notification (admin only)
    // POST /api/notifications/{id}/resend
    async resendNotification(id) {
      if (this.userRole !== 'admin') return
      
      try {
        await fetch(`/api/notifications/${id}/resend`, {
          method: 'POST'
        })
        // Refresh notifications after resend
        await this.fetchNotifications()
      } catch (error) {
        console.error('Failed to resend notification:', error)
      }
    },

    // Delete notification
    // DELETE /api/notifications/{id}
    async deleteNotification(id) {
      try {
        await fetch(`/api/notifications/${id}`, {
          method: 'DELETE'
        })
        this.notifications = this.notifications.filter(
          notif => notif.id !== id
        )
      } catch (error) {
        console.error('Failed to delete notification:', error)
      }
    },

    initializeWebSocket() {
      this.socket = io(process.env.VUE_APP_WEBSOCKET_URL)
      
      this.socket.on('connect', () => {
        this.socket.emit('subscribe', {
          userId: this.userId,
          role: this.userRole
        })
      })

      this.socket.on('new-notification', () => {
        this.newNotification = true
      })
    },

    formatDate(date) {
      return format(new Date(date), 'MMM d, yyyy HH:mm')
    },

    dismiss() {
      this.visible = false;
      this.$emit('dismissed');
    }
  },

  emits: ['dismissed']
}
</script>

<style scoped>
.notifications-manager {
  padding: 1rem;
}

.controls {
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.notifications-table {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  overflow: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.unread {
  background-color: #f8f9fa;
  font-weight: 500;
}

.actions {
  display: flex;
  gap: 0.5rem;
}

.actions button {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  border: none;
  cursor: pointer;
}

.delete {
  background-color: #dc3545;
  color: white;
}

.notification-toast {
  position: fixed;
  bottom: 1rem;
  right: 1rem;
  background: #4CAF50;
  color: white;
  padding: 1rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 1rem;
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

@media (max-width: 768px) {
  .actions {
    flex-direction: column;
  }
  
  .notification-toast {
    left: 1rem;
    right: 1rem;
  }
}
</style>
