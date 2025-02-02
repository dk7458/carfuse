document.addEventListener('DOMContentLoaded', function() {
    fetchNotifications();

    // Fetch notifications from the server
    function fetchNotifications() {
        fetch('/notifications')
            .then(response => response.json())
            .then(data => {
                displayNotifications(data);
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    // Display notifications on the UI
    function displayNotifications(notifications) {
        const notificationsContainer = document.getElementById('notifications-container');
        notificationsContainer.innerHTML = '';

        notifications.forEach(notification => {
            const notificationElement = document.createElement('div');
            notificationElement.className = 'notification';
            notificationElement.innerHTML = `
                <p>${notification.message}</p>
                <button class="mark-as-read" data-id="${notification.id}">Mark as read</button>
            `;
            notificationsContainer.appendChild(notificationElement);
        });

        // Add event listeners to mark-as-read buttons
        document.querySelectorAll('.mark-as-read').forEach(button => {
            button.addEventListener('click', function() {
                markAsRead(this.dataset.id);
            });
        });
    }

    // Mark a notification as read
    function markAsRead(notificationId) {
        fetch(`/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fetchNotifications();
            } else {
                console.error('Error marking notification as read:', data.error);
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    }
});
