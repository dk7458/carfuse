document.addEventListener('DOMContentLoaded', function () {
    const reportType = document.querySelector('select[name="report_type"]').value;
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;

    // Fetch report data for table and chart
    fetch('/controllers/report_ctrl.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'chart_data',
            report_type: reportType,
            start_date: startDate,
            end_date: endDate
        })
    })
        .then(response => response.json())
        .then(data => {
            populateTable(data.table);
            drawChart(data.chart);
        })
        .catch(error => console.error('Error fetching report data:', error));

    // Populate table
    function populateTable(data) {
        const tableHead = document.querySelector('#reportTable thead');
        const tableBody = document.querySelector('#reportTable tbody');
        tableHead.innerHTML = '';
        tableBody.innerHTML = '';

        // Headers
        const headers = data.headers || [];
        const headerRow = document.createElement('tr');
        headers.forEach(header => {
            const th = document.createElement('th');
            th.textContent = header;
            headerRow.appendChild(th);
        });
        tableHead.appendChild(headerRow);

        // Rows
        const rows = data.rows || [];
        rows.forEach(row => {
            const tr = document.createElement('tr');
            row.forEach(cell => {
                const td = document.createElement('td');
                td.textContent = cell;
                tr.appendChild(td);
            });
            tableBody.appendChild(tr);
        });
    }

    // Draw chart
    function drawChart(chartData) {
        const ctx = document.getElementById('reportChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: `Raport: ${reportType}`,
                    data: chartData.data,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Export CSV
    document.getElementById('exportCsv').addEventListener('click', function () {
        window.location.href = `/controllers/report_ctrl.php?action=export_csv&report_type=${reportType}&start_date=${startDate}&end_date=${endDate}`;
    });
});
