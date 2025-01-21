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

            fetch('/controllers/maintenance_ctrl.php', {
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
