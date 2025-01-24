document.addEventListener('DOMContentLoaded', function () {
    // Fetch error logs
    function fetchErrorLogs() {
        fetch('/public/api.php?endpoint=logs&action=fetch_errors')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch error logs');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update the UI with error logs
                    console.log('Error Logs:', data.logs);
                } else {
                    console.error('Error:', data.error);
                }
            })
            .catch(error => {
                console.error('Unexpected error:', error);
            });
    }

    // Example usage
    fetchErrorLogs();
});
