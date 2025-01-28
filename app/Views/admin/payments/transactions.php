<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Transaction History</h2>
        <table class="table table-bordered mt-4">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>User ID</th>
                    <th>Booking ID</th>
                    <th>Amount</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody id="transactionList">
                <!-- Dynamic data will be loaded here -->
            </tbody>
        </table>
    </div>

    <script>
        async function loadTransactions() {
            try {
                const response = await fetch('/admin/transactions');
                const data = await response.json();

                const tableBody = document.getElementById('transactionList');
                if (data.status === 'success') {
                    tableBody.innerHTML = '';
                    data.transactions.forEach(transaction => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${transaction.id}</td>
                            <td>${transaction.user_id}</td>
                            <td>${transaction.booking_id || 'N/A'}</td>
                            <td>${transaction.amount}</td>
                            <td>${transaction.type}</td>
                            <td>${transaction.status}</td>
                            <td>${transaction.created_at}</td>
                        `;
                        tableBody.appendChild(row);
                    });
                } else {
                    tableBody.innerHTML = '<tr><td colspan="7" class="text-center">No transactions found</td></tr>';
                }
            } catch (error) {
                console.error('Error loading transactions:', error);
            }
        }

        document.addEventListener('DOMContentLoaded', loadTransactions);
    </script>
</body>
</html>
