<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports - CarFuse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Admin Reports</h1>
        <form id="adminReportForm" class="mt-4">
            <div class="mb-3">
                <label for="reportType" class="form-label">Report Type</label>
                <select class="form-select" id="reportType" name="reportType" required>
                    <option value="" disabled selected>Select Report Type</option>
                    <option value="bookings">Bookings</option>
                    <option value="payments">Payments</option>
                    <option value="users">Users</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="startDate" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="startDate" name="startDate" required>
            </div>
            <div class="mb-3">
                <label for="endDate" class="form-label">End Date</label>
                <input type="date" class="form-control" id="endDate" name="endDate" required>
            </div>
            <div class="mb-3">
                <label for="format" class="form-label">Report Format</label>
                <select class="form-select" id="format" name="format" required>
                    <option value="csv">CSV</option>
                    <option value="pdf">PDF</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Generate Report</button>
        </form>
        <div id="responseMessage" class="mt-3"></div>
    </div>

    <script>
        document.getElementById('adminReportForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const payload = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('/admin/reports/generate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                const messageDiv = document.getElementById('responseMessage');

                if (result.status === 'success') {
                    messageDiv.innerHTML = `<div class="alert alert-success">Report generated: <a href="${result.filePath}" download>Download Report</a></div>`;
                } else {
                    messageDiv.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
                }
            } catch (error) {
                console.error('Error generating report:', error);
                document.getElementById('responseMessage').innerHTML = `<div class="alert alert-danger">An error occurred.</div>`;
            }
        });
    </script>
</body>
</html>
