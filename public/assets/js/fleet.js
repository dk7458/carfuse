import { fetchData, handleApiError } from './shared/utils.js';

/**
 * File Path: /frontend/assets/js/fleet.js
 * Description: Manages fleet-related actions like visualizations, filtering, and fetching vehicle data.
 * Changelog:
 * - Implemented vehicle availability and maintenance visualizations using Chart.js.
 * - Added filtering functionality for fleet data.
 */

document.addEventListener('DOMContentLoaded', async () => {
  try {
    const fleetData = await fetchData('/public/api.php', {
      endpoint: 'fleet',
      method: 'GET',
      params: { action: 'visualization_data' }
    });
    
    drawChart('availabilityChart', 'Vehicle Availability', fleetData.availability);
    drawChart('maintenanceChart', 'Maintenance Status', fleetData.maintenance);
  } catch (error) {
    handleApiError(error, 'fetching fleet data');
  }

  // Helper function to draw pie charts
  function drawChart(canvasId, title, chartData) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    new Chart(ctx, {
      type: 'pie',
      data: {
        labels: chartData.map(item => item.status),
        datasets: [{
          data: chartData.map(item => item.count),
          backgroundColor: ['rgba(75, 192, 192, 0.6)', 'rgba(255, 99, 132, 0.6)'],
          borderColor: ['rgba(75, 192, 192, 1)', 'rgba(255, 99, 132, 1)'],
          borderWidth: 1,
        }],
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom' },
          title: { display: true, text: title },
        },
      },
    });
  }

  // Handle fleet filtering
  document.getElementById('filterButton')?.addEventListener('click', async () => {
    try {
      const filterParams = {
        search: document.getElementById('searchInput').value,
        startDate: document.getElementById('startDateInput').value,
        endDate: document.getElementById('endDateInput').value
      };

      const fleetResults = await fetchData('/public/api.php', {
        endpoint: 'fleet',
        method: 'GET',
        params: {
          action: 'fetch_vehicles',
          ...filterParams
        }
      });

      updateFleetTable(fleetResults.vehicles);
    } catch (error) {
      handleApiError(error, 'filtering fleet data');
    }
  });

  // Helper function to update fleet table
  function updateFleetTable(vehicles) {
    const fleetTableBody = document.getElementById('fleetTableBody');
    fleetTableBody.innerHTML = vehicles.map(vehicle => `
      <tr>
        <td>${vehicle.make}</td>
        <td>${vehicle.model}</td>
        <td>${vehicle.year}</td>
      </tr>`
    ).join('');
  }
});
