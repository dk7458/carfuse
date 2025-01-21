document.addEventListener('DOMContentLoaded', () => {
    // Fetch logs
    function fetchLogs() {
        const params = new URLSearchParams(new FormData(document.querySelector('form')));
        fetch(`/controllers/logs_ctrl.php?action=fetch&${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const tbody = document.querySelector('#logsTable tbody');
                    tbody.innerHTML = '';
                    data.data.forEach(log => {
                        const row = `<tr>
                            <td>${log.id}</td>
                            <td>${log.user_id}</td>
                            <td>${log.action}</td>
                            <td>${log.log_type}</td>
                            <td><button class="btn btn-sm btn-info view-details" data-id="${log.id}">Szczegóły</button></td>
                            <td>${log.timestamp}</td>
                        </tr>`;
                        tbody.innerHTML += row;
                    });
                    attachViewDetailsEvent();
                }
            });
    }

    // Fetch chart data
    function fetchChartData() {
        fetch('/controllers/logs_ctrl.php?action=chart_data')
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById('logsChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.dates,
                        datasets: [{
                            label: 'Liczba Logów',
                            data: data.counts,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1,
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: { title: { display: true, text: 'Data' } },
                            y: { title: { display: true, text: 'Liczba Logów' } }
                        }
                    }
                });
            });
    }

    // Attach event to view details button
    function attachViewDetailsEvent() {
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', () => {
                const logId = button.dataset.id;
                fetch(`/controllers/logs_ctrl.php?action=view&id=${logId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const log = data.log;
                            alert(`ID: ${log.id}\nUżytkownik: ${log.user_id}\nAkcja: ${log.action}\nTyp: ${log.log_type}\nSzczegóły: ${log.details}`);
                        }
                    });
            });
        });
    }

    // Clear logs
    document.getElementById('clearLogs').addEventListener('click', () => {
        if (confirm('Czy na pewno chcesz usunąć logi starsze niż 30 dni?')) {
            fetch('/controllers/logs_ctrl.php?action=clear_logs', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Stare logi zostały usunięte.');
                        fetchLogs();
                    }
                });
        }
    });

    // Export logs as CSV
    document.getElementById('exportCsv').addEventListener('click', (e) => {
        e.preventDefault();

        const params = new URLSearchParams(new FormData(document.querySelector('form')));
        params.append('action', 'export_csv');

        fetch(`/controllers/logs_ctrl.php?${params.toString()}`)
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = 'error_logs.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            })
            .catch(error => console.error('Error exporting logs:', error));
    });

    // Initial load
    fetchLogs();
    fetchChartData();
});
