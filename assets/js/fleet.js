document.addEventListener('DOMContentLoaded', function () {
    // Fetch data for visualization
    fetch('/controllers/fleet_ctrl.php?action=visualization_data')
        .then(response => response.json())
        .then(data => {
            drawChart('availabilityChart', 'Dostępność Pojazdów', data.availability);
            drawChart('maintenanceChart', 'Status Przeglądów', data.maintenance);
        })
        .catch(error => console.error('Error fetching visualization data:', error));

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
});
