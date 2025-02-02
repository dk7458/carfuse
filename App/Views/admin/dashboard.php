<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CarFuse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/public/css/styles.css">
    <script src="/public/js/dashboard.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Admin Dashboard</h1>
        <div class="row mt-4">
            <!-- Metrics Cards -->
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h5>Total Users</h5>
                        <p class="fs-4" id="totalUsers">0</p>
                        <p>Active: <span id="activeUsers">0</span></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h5>Total Bookings</h5>
                        <p class="fs-4" id="totalBookings">0</p>
                        <p>Completed: <span id="completedBookings">0</span>, Canceled: <span id="canceledBookings">0</span></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h5>Total Revenue</h5>
                        <p class="fs-4" id="totalRevenue">$0.00</p>
                        <p>Refunds: $<span id="totalRefunds">0.00</span></p>
                        <p>Net: $<span id="netRevenue">0.00</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphs Section -->
        <div class="row mt-5">
            <div class="col-md-6">
                <h4>Booking Trends</h4>
                <canvas id="bookingTrends"></canvas>
            </div>
            <div class="col-md-6">
                <h4>Revenue Trends</h4>
                <canvas id="revenueTrends"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Load data dynamically
        async function loadDashboardData() {
            try {
                const response = await fetch('/admin/dashboard/data');
                const data = await response.json();

                if (data.status === 'success') {
                    // Update Metrics
                    document.getElementById('totalUsers').textContent = data.metrics.total_users;
                    document.getElementById('activeUsers').textContent = data.metrics.active_users;

                    document.getElementById('totalBookings').textContent = data.metrics.total_bookings;
                    document.getElementById('completedBookings').textContent = data.metrics.completed_bookings;
                    document.getElementById('canceledBookings').textContent = data.metrics.canceled_bookings;

                    document.getElementById('totalRevenue').textContent = `$${data.metrics.total_revenue.toFixed(2)}`;
                    document.getElementById('totalRefunds').textContent = data.metrics.total_refunds.toFixed(2);
                    document.getElementById('netRevenue').textContent = data.metrics.net_revenue.toFixed(2);

                    // Update Graphs
                    loadGraph('bookingTrends', 'line', data.bookingTrends.labels, data.bookingTrends.data, 'Bookings', 'blue');
                    loadGraph('revenueTrends', 'line', data.revenueTrends.labels, data.revenueTrends.data, 'Revenue', 'green');
                } else {
                    console.error('Failed to fetch dashboard data');
                }
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }

        // Load Graph
        function loadGraph(canvasId, type, labels, data, label, color) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            new Chart(ctx, {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: data,
                        borderColor: color,
                        fill: false,
                        tension: 0.3,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                        }
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', loadDashboardData);
    </script>
</body>
</html>
