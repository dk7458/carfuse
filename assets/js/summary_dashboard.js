document.addEventListener('DOMContentLoaded', () => {
    const totalBookings = document.getElementById('totalBookings');
    const totalRevenue = document.getElementById('totalRevenue');
    const fleetAvailability = document.getElementById('fleetAvailability');
    const totalUsers = document.getElementById('totalUsers');
    const activityChartCanvas = document.getElementById('activityChart');

    let chartInstance = null;

    const fetchSummaryData = async () => {
        try {
            const response = await fetch('/public/api.php?endpoint=summary_dashboard&action=get_summary');
            const data = await response.json();

            if (data.success) {
                const metrics = data.metrics;

                totalBookings.textContent = metrics.bookings;
                totalRevenue.textContent = `${metrics.revenue.toFixed(2)} PLN`;
                fleetAvailability.textContent = `${metrics.fleet.available} / ${metrics.fleet.total}`;
                totalUsers.textContent = metrics.users;

                renderActivityChart(metrics);
            } else {
                alert('Nie udało się załadować danych podsumowania.');
            }
        } catch (error) {
            console.error('Błąd podczas pobierania danych:', error);
            alert('Wystąpił błąd.');
        }
    };

    const renderActivityChart = (metrics) => {
        if (chartInstance) {
            chartInstance.destroy();
        }

        const labels = ['Rezerwacje', 'Przychody', 'Flota (Dostępne)', 'Użytkownicy'];
        const data = [
            metrics.bookings,
            metrics.revenue,
            metrics.fleet.available,
            metrics.users
        ];

        chartInstance = new Chart(activityChartCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Podsumowanie Aktywności',
                    data: data,
                    backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    };

    fetchSummaryData();

    function fetchFilteredData() {
        const search = document.getElementById('searchInput').value;
        const startDate = document.getElementById('startDateInput').value;
        const endDate = document.getElementById('endDateInput').value;

        fetch(`/public/api.php?endpoint=summary_dashboard&action=fetch_summary&search=${search}&startDate=${startDate}&endDate=${endDate}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const summaryTableBody = document.getElementById('summaryTableBody');
                    summaryTableBody.innerHTML = '';
                    data.summary.forEach(item => {
                        const row = `<tr>
                            <td>${item.metric}</td>
                            <td>${item.value}</td>
                        </tr>`;
                        summaryTableBody.innerHTML += row;
                    });
                }
            })
            .catch(error => console.error('Error:', error));
    }
});
