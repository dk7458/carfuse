// Function to fetch real-time statistics
function fetchStatistics() {
    fetch('/api/statistics')
        .then(response => response.json())
        .then(data => {
            updateWidgets(data);
        })
        .catch(error => {
            showErrorToast('Failed to fetch statistics.');
            console.error('Error fetching statistics:', error);
        });
}

// Function to update dashboard widgets
function updateWidgets(data) {
    // Example: Update a widget with ID 'total-users'
    const totalUsersWidget = document.getElementById('total-users');
    if (totalUsersWidget) {
        totalUsersWidget.innerText = data.totalUsers;
    }

    // Update other widgets similarly
    // ...existing code...
}

// Ensure responsive UI updates
window.addEventListener('resize', () => {
    // Handle responsive updates
    // ...existing code...
});

// Fetch statistics on page load and set interval for real-time updates
document.addEventListener('DOMContentLoaded', () => {
    fetchStatistics();
    setInterval(fetchStatistics, 60000); // Update every 60 seconds
});
