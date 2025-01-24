// Wait for the DOM content to load
window.addEventListener('DOMContentLoaded', () => {
    const chartSelector = document.getElementById('chartTypeSelector');
    const chartContainer = document.getElementById('dashboardChart');
    let chartInstance;

    // Fetch and render chart data
    function fetchChartData(type = 'bookings') {
        fetch(`/controllers/dashboard_ctrl.php?action=get_chart_data&type=${type}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (data.labels && data[type]) {
                    renderChart(data.labels, data[type], type);
                } else {
                    showAlert(data.error || 'Nie udało się załadować danych.', 'error');
                }
            })
            .catch((error) => {
                console.error('Error fetching dashboard data:', error);
                showAlert('Wystąpił błąd podczas ładowania danych.', 'error');
            });
    }

    // Render chart
    function renderChart(labels, dataset, type) {
        const chartLabels = type === 'bookings' ? 'Rezerwacje' : 'Przychody (PLN)';
        const chartColor = type === 'bookings' ? 'rgba(75, 192, 192, 1)' : 'rgba(153, 102, 255, 1)';

        if (chartInstance) {
            chartInstance.destroy(); // Destroy previous instance to avoid overlaying charts
        }

        chartInstance = new Chart(chartContainer.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: chartLabels,
                        data: dataset,
                        borderColor: chartColor,
                        backgroundColor: `${chartColor.slice(0, -3)}0.2)`, // Add transparency to background color
                        borderWidth: 2,
                        tension: 0.2,
                        fill: true,
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                    },
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Data',
                        },
                    },
                    y: {
                        title: {
                            display: true,
                            text: type === 'bookings' ? 'Liczba Rezerwacji' : 'Przychody (PLN)',
                        },
                        beginAtZero: true,
                    },
                },
            },
        });
    }

    // Show notification alerts
    function showAlert(message, type) {
        const alertBox = document.createElement('div');
        alertBox.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show`;
        alertBox.role = 'alert';
        alertBox.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.prepend(alertBox);

        setTimeout(() => alertBox.remove(), 5000); // Remove alert after 5 seconds
    }

    // Fetch initial chart data
    fetchChartData();

    // Update chart when chart type is selected
    chartSelector.addEventListener('change', (event) => {
        const selectedType = event.target.value;
        fetchChartData(selectedType);
    });
});
