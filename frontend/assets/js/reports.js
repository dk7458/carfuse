import { fetchData, handleApiError } from './shared/utils.js';

// File Path: /assets/js/reports.js
// Description: Handles fetching, filtering, and displaying data for reports.
// Changelog:
// - Merged report-related functionalities into a single file.
// - Centralized report table and chart rendering logic.
// - Added support for exporting reports.

document.addEventListener('DOMContentLoaded', () => {
    const reportCategorySelect = document.querySelector('#reportCategory');
    const reportDateFromInput = document.querySelector('#reportDateFrom');
    const reportDateToInput = document.querySelector('#reportDateTo');
    const reportTable = document.getElementById('reportTable');
    const reportChartCanvas = document.getElementById('reportChart');
    const exportCsvButton = document.querySelector('.btn-export-csv');
    const exportPdfButton = document.querySelector('.btn-export-pdf');
    let chartInstance = null;

    /**
     * Render a table.
     */
    const renderTable = (data) => {
        const tableBody = reportTable.querySelector('tbody');
        tableBody.innerHTML = '';

        if (!data.length) {
            tableBody.innerHTML = `<tr><td colspan="5">Brak danych do wyświetlenia.</td></tr>`;
            return;
        }

        data.forEach(row => {
            const rowElement = document.createElement('tr');
            Object.values(row).forEach(cell => {
                const td = document.createElement('td');
                td.textContent = cell;
                rowElement.appendChild(td);
            });
            tableBody.appendChild(rowElement);
        });
    };

    /**
     * Render a chart.
     */
    const renderChart = (labels, values, label = 'Report Data') => {
        if (chartInstance) chartInstance.destroy();
        chartInstance = new Chart(reportChartCanvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label,
                    data: values,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.2,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: true } },
                scales: { x: { beginAtZero: true }, y: { beginAtZero: true } },
            }
        });
    };

    /**
     * Fetch and render reports.
     */
    const fetchAndRenderReports = async () => {
        try {
            const category = reportCategorySelect.value;
            const dateFrom = reportDateFromInput.value;
            const dateTo = reportDateToInput.value;

            const data = await fetchData('/public/api.php', {
                endpoint: 'report_manager',
                method: 'GET',
                params: {
                    action: 'fetch_reports',
                    category,
                    date_from: dateFrom,
                    date_to: dateTo
                }
            });

            renderTable(data.data);
            renderChart(
                data.data.map(row => row.date),
                data.data.map(row => row.value),
                `Report: ${category}`
            );
        } catch (error) {
            handleApiError(error, 'fetching reports');
        }
    };

    /**
     * Export reports.
     */
    const exportReports = async (format) => {
        try {
            const category = reportCategorySelect.value;
            const dateFrom = reportDateFromInput.value;
            const dateTo = reportDateToInput.value;

            await fetchData('/public/api.php', {
                endpoint: 'report_manager',
                method: 'GET',
                params: {
                    action: `export_${format}`,
                    category,
                    date_from: dateFrom,
                    date_to: dateTo
                },
                showLoader: true
            });
        } catch (error) {
            handleApiError(error, `exporting ${format}`);
        }
    };

    exportCsvButton.addEventListener('click', () => exportReports('csv'));
    exportPdfButton.addEventListener('click', () => exportReports('pdf'));

    /**
     * Initialize fetching and rendering.
     */
    fetchAndRenderReports();
    [reportCategorySelect, reportDateFromInput, reportDateToInput].forEach(el => el.addEventListener('change', fetchAndRenderReports));
});
