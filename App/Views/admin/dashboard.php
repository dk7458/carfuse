<?php require_once __DIR__ . '/layouts/header.php'; ?>

<h1 class="text-center">Admin Dashboard</h1>

<div class="dashboard-container">
    <!-- Overview Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h4>Total Users</h4>
                    <p id="totalUsers" class="display-6">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h4>Total Bookings</h4>
                    <p id="totalBookings" class="display-6">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h4>Total Revenue</h4>
                    <p id="totalRevenue" class="display-6">$0.00</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphs -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="text-center">Monthly Booking Trends</h5>
                    <canvas id="bookingTrends"></canvas>
                </div>
            </div>
        </div>
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

<script src="/js/admin.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
