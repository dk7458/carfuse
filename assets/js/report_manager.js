document.addEventListener('DOMContentLoaded', () => {
    const categorySelect = document.querySelector('#category');
    const dateFromInput = document.querySelector('#date_from');
    const dateToInput = document.querySelector('#date_to');
    const reportChart = document.getElementById('reportChart');
    const exportCsvButton = document.getElementById('exportCsv');
    const exportPdfButton = document.getElementById('exportPdf');
    const reportTable = document.getElementById('reportTable');

    let chartInstance = null;

    // Fetch and render report data
    const fetchReportData = async () => {
        const category = categorySelect.value;
        const dateFrom = dateFromInput.value;
        const dateTo = dateToInput.value;

        try {
            const response = await fetch(`/controllers/report_ctrl.php?action=fetch&category=${category}&date_from=${dateFrom}&date_to=${dateTo}`);
            const data = await response.json();

            if (data.success) {
                renderTable(data.data);
                renderChart(data.data, category);
            } else {
                alert('Wystąpił błąd podczas pobierania danych: ' + data.error);
            }
        } catch (error) {
            console.error('Error fetching report data:', error);
            alert('Nie udało się pobrać danych.');
        }
    };

    // Render table data
    const renderTable = (data) => {
        const tableHead = reportTable.querySelector('thead');
        const tableBody = reportTable.querySelector('tbody');

        tableHead.innerHTML = '';
        tableBody.innerHTML = '';

        if (data.length > 0) {
            const headers = Object.keys(data[0]);
            const headerRow = document.createElement('tr');

            headers.forEach(header => {
                const th = document.createElement('th');
                th.textContent = header.charAt(0).toUpperCase() + header.slice(1);
                headerRow.appendChild(th);
            });

            tableHead.appendChild(headerRow);

            data.forEach(row => {
                const rowElement = document.createElement('tr');
                Object.values(row).forEach(cell => {
                    const td = document.createElement('td');
                    td.textContent = cell;
                    rowElement.appendChild(td);
                });
                tableBody.appendChild(rowElement);
            });
        } else {
            const noDataRow = document.createElement('tr');
            const noDataCell = document.createElement('td');
            noDataCell.textContent = 'Brak danych do wyświetlenia.';
            noDataCell.colSpan = 3;
            noDataRow.appendChild(noDataCell);
            tableBody.appendChild(noDataRow);
        }
    };

    // Render chart
    const renderChart = (data, category) => {
        if (chartInstance) {
            chartInstance.destroy();
        }

        const labels = data.map(row => row.date);
        const values = data.map(row => category === 'revenue' ? row.total : row.count);

        chartInstance = new Chart(reportChart, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: category === 'revenue' ? 'Przychody' : 'Liczba',
                    data: values,
                    borderColor: '#007bff',
                    tension: 0.1,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                }
            }
        });
    };

    // Export CSV
    exportCsvButton.addEventListener('click', () => {
        const category = categorySelect.value;
        const dateFrom = dateFromInput.value;
        const dateTo = dateToInput.value;

        window.location.href = `/controllers/report_ctrl.php?action=export_csv&category=${category}&date_from=${dateFrom}&date_to=${dateTo}`;
    });

    // Export PDF
    exportPdfButton.addEventListener('click', () => {
        const category = categorySelect.value;
        const dateFrom = dateFromInput.value;
        const dateTo = dateToInput.value;

        window.location.href = `/controllers/report_ctrl.php?action=export_pdf&category=${category}&date_from=${dateFrom}&date_to=${dateTo}`;
    });

    // Fetch initial data
    fetchReportData();

    // Re-fetch data when filters change
    [categorySelect, dateFromInput, dateToInput].forEach(element => {
        element.addEventListener('change', fetchReportData);
    });
});
