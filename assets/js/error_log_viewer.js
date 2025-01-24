// ...existing code...

function fetchFilteredData() {
    const search = document.getElementById('searchInput').value;
    const startDate = document.getElementById('startDateInput').value;
    const endDate = document.getElementById('endDateInput').value;

    fetch(`/public/api.php?endpoint=logs&action=fetch_errors&search=${search}&startDate=${startDate}&endDate=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const logsTableBody = document.getElementById('logsTableBody');
                logsTableBody.innerHTML = '';
                data.errors.forEach(error => {
                    const row = `<tr>
                        <td>${error.timestamp}</td>
                        <td>${error.message}</td>
                        <td>${error.level}</td>
                    </tr>`;
                    logsTableBody.innerHTML += row;
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

fetch('/public/api.php?endpoint=error_log&action=view')
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to fetch error logs');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('Error Logs:', data.logs);
        } else {
            console.error('Error:', data.error);
        }
    })
    .catch(error => {
        console.error('Unexpected error:', error);
    });

// ...existing code...
