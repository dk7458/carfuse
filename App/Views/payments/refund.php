<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Refund - CarFuse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Process Refund</h2>
        <form id="refundForm">
            <div class="mb-3">
                <label for="transaction_id" class="form-label">Transaction ID</label>
                <input type="number" class="form-control" id="transaction_id" name="transaction_id" required>
            </div>
            <div class="mb-3">
                <label for="amount" class="form-label">Amount</label>
                <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
            </div>
            <button type="submit" class="btn btn-danger w-100">Submit Refund</button>
        </form>
        <div id="responseMessage" class="mt-3"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('refundForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const payload = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('/payment/refund', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();
                const messageDiv = document.getElementById('responseMessage');
                
                if (result.status === 'success') {
                    messageDiv.innerHTML = `<div class="alert alert-success">Refund processed successfully!</div>`;
                } else {
                    messageDiv.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
                }
            } catch (error) {
                console.error('Refund processing failed:', error);
                document.getElementById('responseMessage').innerHTML = `<div class="alert alert-danger">An error occurred.</div>`;
            }
        });
    </script>
</body>
</html>
