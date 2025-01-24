document.addEventListener('DOMContentLoaded', function () {
    // Get initial filter values
    const reportTypeSelect = document.querySelector('select[name="category"]');
    const startDateInput = document.querySelector('input[name="date_from"]');
    const endDateInput = document.querySelector('input[name="date_to"]');

    const tableHead = document.querySelector('#reportTable thead');
    const tableBody = document.querySelector('#reportTable tbody');
    const chartContainer = document.getElementById('reportChart');
    const exportCsvButton = document.getElementById('exportCsv');
    const exportPdfButton = document.getElementById('exportPdf');

    // Event listener for filter changes
    document.querySelector('form').addEventListener('submit', function (e) {
        e.preventDefault();
        const reportType = reportTypeSelect.value;
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        fetchReportData(reportType, startDate, endDate);
    });

    // Fetch report data
    function fetchReportData(reportType, startDate, endDate) {
        fetch('/public/api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                endpoint: 'report',
                action: 'fetch',
                category: reportType,
                date_from: startDate,
                date_to: endDate,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    populateTable(data.data);
                    drawChart(data.data, reportType);
                } else {
                    console.error(data.error);
                    showAlert('error', data.error || 'Błąd podczas pobierania danych raportu.');
                }
            })
            .catch((error) => {
                console.error('Error fetching report data:', error);
                showAlert('error', 'Błąd sieci. Spróbuj ponownie później.');
            });
    }

    // Populate the report table
    function populateTable(data) {
        tableHead.innerHTML = '';
        tableBody.innerHTML = '';

        if (!data || data.length === 0) {
            const noDataRow = document.createElement('tr');
            const noDataCell = document.createElement('td');
            noDataCell.colSpan = 100;
            noDataCell.textContent = 'Brak danych do wyświetlenia.';
            noDataCell.classList.add('text-center');
            noDataRow.appendChild(noDataCell);
            tableBody.appendChild(noDataRow);
            return;
        }

        // Populate headers
        const headers = Object.keys(data[0]);
        const headerRow = document.createElement('tr');
        headers.forEach((header) => {
            const th = document.createElement('th');
            th.textContent = header.charAt(0).toUpperCase() + header.slice(1);
            headerRow.appendChild(th);
        });
        tableHead.appendChild(headerRow);

        // Populate rows
        data.forEach((row) => {
            const tr = document.createElement('tr');
            Object.values(row).forEach((cell) => {
                const td = document.createElement('td');
                td.textContent = cell;
                tr.appendChild(td);
            });
            tableBody.appendChild(tr);
        });
    }

    // Draw the report chart
    function drawChart(data, reportType) {
        if (chartContainer.chartInstance) {
            chartContainer.chartInstance.destroy();
        }

        const labels = data.map((item) => item.date);
        const values = data.map((item) => Object.values(item)[1]);

        chartContainer.chartInstance = new Chart(chartContainer, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: `Raport: ${reportType.charAt(0).toUpperCase() + reportType.slice(1)}`,
                        data: values,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                },
            },
        });
    }

    // Add export functionality
    exportCsvButton.addEventListener('click', function () {
        const reportType = reportTypeSelect.value;
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        window.location.href = `/public/api.php?endpoint=report&action=export_csv&category=${reportType}&date_from=${startDate}&date_to=${endDate}`;
    });

    exportPdfButton.addEventListener('click', function () {
        const reportType = reportTypeSelect.value;
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        window.location.href = `/public/api.php?endpoint=report&action=export_pdf&category=${reportType}&date_from=${startDate}&date_to=${endDate}`;
    });

    // Show alert
    function showAlert(type, message) {
        const alertBox = document.createElement('div');
        alertBox.className = `alert alert-${type} mt-3`;
        alertBox.textContent = message;
        document.querySelector('.container').prepend(alertBox);
        setTimeout(() => alertBox.remove(), 3000);
    }

    // Initial fetch
    fetchReportData(reportTypeSelect.value, startDateInput.value, endDateInput.value);

    // Fetch report details
    function fetchReportDetails(reportId) {
        fetch(`/public/api.php?endpoint=report&action=fetch_details&report_id=${reportId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch report details');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update the UI with report details
                    console.log('Report Details:', data.report);
                } else {
                    console.error('Error:', data.error);
                }
            })
            .catch(error => {
                console.error('Unexpected error:', error);
            });
    }

    // Example usage
    const reportId = 1; // Replace with actual report ID
    fetchReportDetails(reportId);
});
