<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment - CarFuse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Make a Payment</h2>
        <form id="paymentForm">
            <div class="mb-3">
                <label for="user_id" class="form-label">User ID</label>
                <input type="number" class="form-control" id="user_id" name="user_id" required>
            </div>
            <div class="mb-3">
                <label for="amount" class="form-label">Amount</label>
                <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
            </div>
            <div class="mb-3">
                <label for="payment_method_id" class="form-label">Payment Method</label>
                <select class="form-select" id="payment_method_id" name="payment_method_id" required>
                    <option value="" disabled selected>Select a Payment Method</option>
                    <option value="1">Credit Card</option>
                    <option value="2">PayPal</option>
                    <option value="3">Bank Transfer</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-100">Submit Payment</button>
        </form>
        <div id="responseMessage" class="mt-3"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('paymentForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const payload = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('/payment/process', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                
                const result = await response.json();
                const messageDiv = document.getElementById('responseMessage');
                
                if (result.status === 'success') {
                    messageDiv.innerHTML = `<div class="alert alert-success">Payment processed successfully!</div>`;
                } else {
                    messageDiv.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
                }
            } catch (error) {
                console.error('Payment processing failed:', error);
                document.getElementById('responseMessage').innerHTML = `<div class="alert alert-danger">An error occurred.</div>`;
            }
        });
    </script>
</body>
</html>
