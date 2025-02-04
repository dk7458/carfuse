<?php require_once __DIR__ . '/layouts/header.php'; ?>

<h1 class="text-center">Audit Logs</h1>

<div class="admin-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>System Audit Logs</h3>
    </div>

    <!-- Filters -->
    <form id="filterForm" class="row mb-4">
        <?= csrf_field() ?>
        <div class="col-md-3">
            <input type="text" class="form-control" name="user_id" placeholder="User ID">
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control" name="booking_id" placeholder="Booking ID">
        </div>
        <div class="col-md-3">
            <input type="date" class="form-control" name="start_date" placeholder="Start Date">
        </div>
        <div class="col-md-3">
            <input type="date" class="form-control" name="end_date" placeholder="End Date">
        </div>
        <div class="col-md-12 mt-2 text-end">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>

    <!-- Logs Table -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Action</th>
                <th>Details</th>
                <th>User ID</th>
                <th>Booking ID</th>
                <th>IP Address</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody id="auditLogs">
            <!-- Audit log data will be loaded dynamically -->
        </tbody>
    </table>
</div>

<script src="/js/admin.js"></script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
