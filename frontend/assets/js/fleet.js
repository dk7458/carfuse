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
        const data = await fetchData('/public/api.php', {
            endpoint: 'fleet',
            method: 'GET',
            params: { action: 'visualization_data' }
        });
        
        drawChart('availabilityChart', 'Vehicle Availability', data.availability);
        drawChart('maintenanceChart', 'Maintenance Status', data.maintenance);
    } catch (error) {
        handleApiError(error, 'fetching fleet data');
    }

    // Draw chart function
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

    // Apply filters
    document.getElementById('filterButton').addEventListener('click', async () => {
        try {
            const search = document.getElementById('searchInput').value;
            const startDate = document.getElementById('startDateInput').value;
            const endDate = document.getElementById('endDateInput').value;

            const data = await fetchData('/public/api.php', {
                endpoint: 'fleet',
                method: 'GET',
                params: {
                    action: 'fetch_vehicles',
                    search,
                    startDate,
                    endDate
                }
            });

            const fleetTableBody = document.getElementById('fleetTableBody');
            fleetTableBody.innerHTML = '';
            data.vehicles.forEach(vehicle => {
                fleetTableBody.innerHTML += `
                    <tr>
                        <td>${vehicle.make}</td>
                        <td>${vehicle.model}</td>
                        <td>${vehicle.year}</td>
                    </tr>`;
            });
        } catch (error) {
            handleApiError(error, 'filtering fleet data');
        }
    });
});
