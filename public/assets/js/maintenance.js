document.addEventListener('DOMContentLoaded', function () {
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

                fetch('/public/api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'batch_delete', log_ids: [logId] })
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

    // Fetch maintenance logs
    function fetchMaintenanceLogs() {
        fetch('/public/api.php?endpoint=maintenance&action=fetch_logs')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch maintenance logs');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update the UI with maintenance logs
                    console.log('Maintenance Logs:', data.logs);
                } else {
                    console.error('Error:', data.error);
                }
            })
            .catch(error => {
                console.error('Unexpected error:', error);
            });
    }

    // Example usage
    fetchMaintenanceLogs();
});
