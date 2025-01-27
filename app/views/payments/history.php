<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - CarFuse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Transaction History</h2>
        <table class="table table-bordered mt-3">
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
            <tbody id="transactionHistory">
                <!-- Dynamic Data -->
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function loadTransactionHistory() {
            try {
                const response = await fetch('/payment/transactions');
                const data = await response.json();
                const tableBody = document.getElementById('transactionHistory');

                if (data.status === 'success') {
                    tableBody.innerHTML = '';
                    data.transactions.forEach(transaction => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${transaction.id}</td>
                            <td>${transaction.user_id}</td>
                            <td>${transaction.booking_id ?? 'N/A'}</td>
                            <td>${transaction.amount}</td>
                            <td>${transaction.type}</td>
                            <td>${transaction.status}</td>
                            <td>${transaction.created_at}</td>
                        `;
                        tableBody.appendChild(row);
                    });
                } else {
                    tableBody.innerHTML = `<tr><td colspan="7" class="text-center">No transactions found.</td></tr>`;
                }
            } catch (error) {
                console.error('Error loading transaction history:', error);
            }
        }

        document.addEventListener('DOMContentLoaded', loadTransactionHistory);
    </script>
</body>
</html>
