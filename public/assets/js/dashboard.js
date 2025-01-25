/**
 * File Path: /frontend/assets/js/dashboard_manager.js
 * Description: Handles summaries and dashboard functionalities, including data fetching, chart rendering, filtering, and notifications.
 * Changelog:
 * - Merged functionalities from summaries.js and dashboard.js.
 * - Modularized chart rendering and data fetching.
 * - Centralized error handling and notifications.
 */

import { fetchData, handleApiError } from './shared/utils.js';

document.addEventListener('DOMContentLoaded', () => {
  const dashboardMetrics = {
    totalBookings: document.getElementById('totalBookings'),
    totalRevenue: document.getElementById('totalRevenue'),
    fleetAvailability: document.getElementById('fleetAvailability'),
    totalUsers: document.getElementById('totalUsers'),
  };
  const summaryTable = document.getElementById('summaryTable');
  const chartContainer = document.getElementById('chartContainer');
  const chartTypeSelector = document.getElementById('chartTypeSelector');
  let chartInstance = null;

  /**
   * Render a table.
   */
  const renderTable = (data, tableElement) => {
    const tableBody = tableElement.querySelector('tbody');
    tableBody.innerHTML = '';

    if (!data.length) {
      tableBody.innerHTML = `<tr><td colspan="4">No data available.</td></tr>`;
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
   * Render a chart using Chart.js.
   */
  const renderChart = (labels, data, label = 'Data Overview', type = 'bar') => {
    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(chartContainer.getContext('2d'), {
      type,
      data: {
        labels,
        datasets: [{
          label,
          data,
          backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545'],
          borderColor: '#000',
          borderWidth: 1,
        }],
      },
      options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: {
          x: { beginAtZero: true, title: { display: true, text: 'Metrics' } },
          y: { beginAtZero: true, title: { display: true, text: 'Values' } },
        },
      },
    });
  };

  /**
   * Show alert notifications.
   */
  const showAlert = (message, type) => {
    const alertBox = document.createElement('div');
    alertBox.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show`;
    alertBox.role = 'alert';
    alertBox.innerHTML = `
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.body.prepend(alertBox);
    setTimeout(() => alertBox.remove(), 5000);
  };

  /**
   * Fetch and render summary metrics and table.
   */
  const fetchDashboardSummary = async () => {
    try {
      const summaryData = await fetchData('/public/api.php', {
        endpoint: 'summary',
        method: 'GET',
        params: { action: 'get_summary' }
      });

      updateDashboardMetrics(summaryData.metrics);
      renderSummaryChart(summaryData.metrics);
      renderSummaryTable(summaryData.summary);
    } catch (error) {
      handleApiError(error, 'fetching dashboard summary');
    }
  };

  const updateDashboardMetrics = (metrics) => {
    dashboardMetrics.totalBookings.textContent = metrics.bookings || 0;
    dashboardMetrics.totalRevenue.textContent = `${metrics.revenue.toFixed(2)} PLN`;
    dashboardMetrics.fleetAvailability.textContent = 
      `${metrics.fleet.available} / ${metrics.fleet.total}`;
    dashboardMetrics.totalUsers.textContent = metrics.users || 0;
  };

  /**
   * Fetch and render dashboard chart based on type.
   */
  const fetchAndRenderDashboardChart = async (type = 'bookings') => {
    try {
      const data = await fetchData('/public/api.php', {
        endpoint: 'dashboard',
        method: 'GET',
        params: { action: 'get_chart_data', type }
      });

      const chartLabel = type === 'bookings' ? 'Number of Bookings' : 'Revenue (PLN)';
      renderChart(data.labels, data[type], chartLabel, 'line');
    } catch (error) {
      handleApiError(error, 'fetching dashboard data');
    }
  };

  // Event Listeners
  chartTypeSelector?.addEventListener('change', (event) => {
    fetchAndRenderDashboardChart(event.target.value);
  });

  // Initialize fetching and rendering
  fetchDashboardSummary();
  fetchAndRenderDashboardChart();
});
