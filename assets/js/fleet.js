document.addEventListener('DOMContentLoaded', function () {
    // Fetch data for visualization
    fetch('/public/api.php?endpoint=fleet&action=visualization_data')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch visualization data');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                drawChart('availabilityChart', 'Dostępność Pojazdów', data.availability);
                drawChart('maintenanceChart', 'Status Przeglądów', data.maintenance);
            } else {
                console.error('Error:', data.error);
            }
        })
        .catch(error => {
            console.error('Unexpected error:', error);
        });

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
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: { display: true, text: title }
                }
            }
        });
    }

    function fetchFilteredData() {
        const search = document.getElementById('searchInput').value;
        const startDate = document.getElementById('startDateInput').value;
        const endDate = document.getElementById('endDateInput').value;

        fetch(`/public/api.php?endpoint=fleet&action=fetch_vehicles&search=${search}&startDate=${startDate}&endDate=${endDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const fleetTableBody = document.getElementById('fleetTableBody');
                    fleetTableBody.innerHTML = '';
                    data.vehicles.forEach(vehicle => {
                        const row = `<tr>
                            <td>${vehicle.make}</td>
                            <td>${vehicle.model}</td>
                            <td>${vehicle.year}</td>
                        </tr>`;
                        fleetTableBody.innerHTML += row;
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    }
});
