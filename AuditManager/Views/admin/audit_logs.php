<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Audit Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Audit Logs</h1>
        
        <!-- Filters -->
        <form id="filterForm" class="row mb-4">
            <div class="col-md-3">
                <input type="text" class="form-control" name="user_id" placeholder="User ID" value="<?= $_GET['user_id'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" name="booking_id" placeholder="Booking ID" value="<?= $_GET['booking_id'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <input type="date" class="form-control" name="start_date" placeholder="Start Date" value="<?= $_GET['start_date'] ?? '' ?>">
            </div>
            <div class="col-md-3">
                <input type="date" class="form-control" name="end_date" placeholder="End Date" value="<?= $_GET['end_date'] ?? '' ?>">
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
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= $log['id'] ?></td>
                            <td><?= $log['action'] ?></td>
                            <td><?= $log['details'] ?></td>
                            <td><?= $log['user_id'] ?? '-' ?></td>
                            <td><?= $log['booking_id'] ?? '-' ?></td>
                            <td><?= $log['ip_address'] ?? '-' ?></td>
                            <td><?= $log['created_at'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No logs found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
