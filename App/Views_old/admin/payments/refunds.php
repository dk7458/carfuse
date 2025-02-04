<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refunds - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Manage Refunds</h2>
        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th>Refund ID</th>
                    <th>User ID</th>
                    <th>Transaction ID</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Reason</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="refundsList">
                <!-- Dynamic data will be loaded here -->
            </tbody>
        </table>
    </div>

    <script>
        async function loadRefunds() {
            try {
                const response = await fetch('/admin/refunds');
                const data = await response.json();

                const tableBody = document.getElementById('refundsList');
                if (data.status === 'success') {
                    tableBody.innerHTML = '';
                    data.refunds.forEach(refund => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${refund.id}</td>
                            <td>${refund.user_id}</td>
                            <td>${refund.transaction_id}</td>
                            <td>${refund.amount}</td>
                            <td>${refund.status}</td>
                            <td>${refund.reason}</td>
                            <td>${refund.date}</td>
                            <td>
                                <button class="btn btn-success btn-sm" onclick="approveRefund(${refund.id})">Approve</button>
                                <button class="btn btn-danger btn-sm" onclick="rejectRefund(${refund.id})">Reject</button>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="8" class="text-center">No refunds found</td></tr>';
                }
            } catch (error) {
                console.error('Error loading refunds:', error);
            }
        }

        async function approveRefund(id) {
            // Approve refund logic here
        }

        async function rejectRefund(id) {
            // Reject refund logic here
        }

        document.addEventListener('DOMContentLoaded', loadRefunds);
    </script>
</body>
</html>
