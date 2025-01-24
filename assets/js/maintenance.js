// Handle batch delete
document.getElementById('exportCsv').addEventListener('click', () => {
    window.location.href = '/controllers/maintenance_ctrl.php?action=export_csv';
});

document.getElementById('exportPdf').addEventListener('click', () => {
    window.location.href = '/controllers/maintenance_ctrl.php?action=export_pdf';
});

document.querySelectorAll('.delete-log').forEach(button => {
    button.addEventListener('click', () => {
        if (confirm('Czy na pewno chcesz usunąć ten log przeglądu?')) {
            const logId = button.dataset.id;

            fetch('/public/api.php?endpoint=maintenance&action=batch_delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ log_ids: [logId] })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.success);
                    location.reload();
                } else {
                    alert(data.error || 'Błąd podczas usuwania logu.');
                }
            });
        }
    });
});

fetch('/public/api.php?endpoint=maintenance&action=get_maintenance')
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to fetch maintenance data');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            console.log('Maintenance Data:', data.maintenance);
        } else {
            console.error('Error:', data.error);
        }
    })
    .catch(error => {
        console.error('Unexpected error:', error);
    });

document.getElementById('filterButton').addEventListener('click', () => {
    const search = document.getElementById('searchInput').value;
    const startDate = document.getElementById('startDateInput').value;
    const endDate = document.getElementById('endDateInput').value;

    fetch(`/public/api.php?endpoint=maintenance&action=fetch_logs&search=${search}&startDate=${startDate}&endDate=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const tableBody = document.getElementById('logsTableBody');
                tableBody.innerHTML = '';
                data.logs.forEach(log => {
                    const row = `<tr>
                        <td>${log.make}</td>
                        <td>${log.model}</td>
                        <td>${log.description}</td>
                        <td>${log.maintenance_date}</td>
                    </tr>`;
                    tableBody.innerHTML += row;
                });
            }
        })
        .catch(error => console.error('Error:', error));
});
