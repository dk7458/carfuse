<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Payment Dashboard - CarFuse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Payment Dashboard</h2>

        <!-- Summary Stats -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <h4>Total Payments</h4>
                        <p id="totalPayments" class="display-6">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <h4>Total Refunds</h4>
                        <p id="totalRefunds" class="display-6">0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <h4>Total Transactions</h4>
                        <p id="totalTransactions" class="display-6">0</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphs Section -->
        <div class="row mt-5">
            <!-- Booking Trends -->
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="text-center">Monthly Booking Trends</h5>
                        <canvas id="bookingTrends"></canvas>
                    </div>
                </div>
            </div>

            <!-- Revenue Trends -->
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="text-center">Monthly Revenue Trends</h5>
                        <canvas id="revenueTrends"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function loadDashboardStats() {
            try {
                const response = await fetch('/admin/payments/dashboard/stats');
                const data = await response.json();

                if (data.status === 'success') {
                    // Update stats
                    document.getElementById('totalPayments').textContent = data.totalPayments;
                    document.getElementById('totalRefunds').textContent = data.totalRefunds;
                    document.getElementById('totalTransactions').textContent = data.totalTransactions;

                    // Load graphs
                    loadBookingTrends(data.bookingTrends);
                    loadRevenueTrends(data.revenueTrends);
                } else {
                    console.error('Failed to load dashboard stats');
                }
            } catch (error) {
                console.error('Error loading dashboard stats:', error);
            }
        }

        function loadBookingTrends(data) {
            const ctx = document.getElementById('bookingTrends').getContext('2d');
            const labels = data.map(item => `Month ${item.month}`);
            const values = data.map(item => item.total);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Bookings',
                        data: values,
                        borderColor: 'blue',
                        fill: false,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        }

        function loadRevenueTrends(data) {
            const ctx = document.getElementById('revenueTrends').getContext('2d');
            const labels = data.map(item => `Month ${item.month}`);
            const values = data.map(item => item.total);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: values,
                        borderColor: 'green',
                        fill: false,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', loadDashboardStats);
    </script>
</body>
</html>
