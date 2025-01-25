import { fetchData, handleApiError } from './shared/utils.js';

// File Path: /assets/js/reports.js
// Description: Handles fetching, filtering, and displaying data for reports.
// Changelog:
// - Merged report-related functionalities into a single file.
// - Centralized report table and chart rendering logic.
// - Added support for exporting reports.

document.addEventListener('DOMContentLoaded', () => {
  const REPORT_CONFIG = {
    chart: {
      type: 'line',
      defaultOptions: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: { x: { beginAtZero: true }, y: { beginAtZero: true } }
      }
    },
    endpoints: {
      fetch: '/public/api.php?endpoint=report_manager&action=fetch_reports',
      export: '/public/api.php?endpoint=report_manager&action=export'
    }
  };

  const elements = {
    categorySelect: document.querySelector('#reportCategory'),
    dateInputs: {
      from: document.querySelector('#reportDateFrom'),
      to: document.querySelector('#reportDateTo')
    },
    table: document.getElementById('reportTable'),
    chartCanvas: document.getElementById('reportChart'),
    exportButtons: {
      csv: document.querySelector('.btn-export-csv'),
      pdf: document.querySelector('.btn-export-pdf')
    }
  };

  let activeChart = null;

  /**
   * Render a table.
   */
  const renderTable = (data) => {
    const tableBody = elements.table.querySelector('tbody');
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

  function createChartConfig(labels, values, label) {
    return {
      type: REPORT_CONFIG.chart.type,
      data: {
        labels,
        datasets: [{
          label,
          data: values,
          borderColor: '#007bff',
          backgroundColor: 'rgba(0, 123, 255, 0.1)',
          tension: 0.2
        }]
      },
      options: REPORT_CONFIG.chart.defaultOptions
    };
  }

  /**
   * Render a chart.
   */
  const renderChart = (labels, values, label = 'Report Data') => {
    if (activeChart) activeChart.destroy();
    activeChart = new Chart(elements.chartCanvas, createChartConfig(labels, values, label));
  };

  function getReportParams() {
    return {
      category: elements.categorySelect.value,
      date_from: elements.dateInputs.from.value,
      date_to: elements.dateInputs.to.value
    };
  }

  /**
   * Fetch and render reports.
   */
  const fetchAndRenderReports = async () => {
    try {
      const params = getReportParams();

      const data = await fetchData(REPORT_CONFIG.endpoints.fetch, {
        method: 'GET',
        params
      });

      renderTable(data.data);
      renderChart(
        data.data.map(row => row.date),
        data.data.map(row => row.value),
        `Report: ${params.category}`
      );
    } catch (error) {
      handleApiError(error, 'fetching reports');
    }
  };

  async function handleExport(format) {
    try {
      const params = getReportParams();
      await fetchData(REPORT_CONFIG.endpoints.export, {
        method: 'GET',
        params: { ...params, format },
        showLoader: true
      });
    } catch (error) {
      handleApiError(error, `exporting ${format} report`);
    }
  }

  // Initialize event listeners
  function initializeEventListeners() {
    elements.exportButtons.csv?.addEventListener('click', () => handleExport('csv'));
    elements.exportButtons.pdf?.addEventListener('click', () => handleExport('pdf'));
    
    [elements.categorySelect, ...Object.values(elements.dateInputs)]
      .forEach(el => el?.addEventListener('change', fetchAndRenderReports));
  }

  // Initialize
  initializeEventListeners();
  fetchAndRenderReports();
});
