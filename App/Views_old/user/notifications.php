<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - CarFuse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">My Notifications</h2>

        <div class="card shadow mt-3">
            <div class="card-body">
                <h4 class="mb-4">Notifications</h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="notificationTable">
                        <!-- Dynamic data will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow mt-4">
            <div class="card-body">
                <h4 class="mb-4">Notification Settings</h4>
                <form id="notificationSettingsForm">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="emailNotifications" name="emailNotifications">
                        <label class="form-check-label" for="emailNotifications">Enable Email Notifications</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="smsNotifications" name="smsNotifications">
                        <label class="form-check-label" for="smsNotifications">Enable SMS Notifications</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function loadNotifications() {
            try {
                const response = await fetch('/notifications');
                const data = await response.json();

                if (data.status === 'success') {
                    const notifications = data.notifications;
                    const tableBody = document.getElementById('notificationTable');
                    tableBody.innerHTML = '';

                    notifications.forEach(notification => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${notification.type}</td>
                            <td>${notification.message}</td>
                            <td>${new Date(notification.sent_at).toLocaleString()}</td>
                            <td>${notification.is_read ? 'Read' : 'Unread'}</td>
                            <td>
                                ${notification.is_read ? '' : `<button class="btn btn-sm btn-success" onclick="markAsRead(${notification.id})">Mark as Read</button>`}
                                <button class="btn btn-sm btn-danger" onclick="deleteNotification(${notification.id})">Delete</button>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                } else {
                    alert('Failed to fetch notifications');
                }
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        }

        async function markAsRead(id) {
            try {
                const response = await fetch('/notifications/mark-as-read', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notificationId: id })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    loadNotifications();
                } else {
                    alert('Failed to mark notification as read');
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        }

        async function deleteNotification(id) {
            try {
                const response = await fetch('/notifications/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notificationId: id })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    loadNotifications();
                } else {
                    alert('Failed to delete notification');
                }
            } catch (error) {
                console.error('Error deleting notification:', error);
            }
        }

        document.getElementById('notificationSettingsForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            const emailNotifications = document.getElementById('emailNotifications').checked;
            const smsNotifications = document.getElementById('smsNotifications').checked;

            try {
                const response = await fetch('/notifications/settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        emailNotifications,
                        smsNotifications
                    })
                });
                const data = await response.json();
                if (data.status === 'success') {
                    alert('Settings updated successfully');
                } else {
                    alert('Failed to update settings');
                }
            } catch (error) {
                console.error('Error updating settings:', error);
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            loadNotifications();
        });
    </script>
</body>
</html>
