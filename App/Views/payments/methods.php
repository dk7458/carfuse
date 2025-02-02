<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payment Methods - CarFuse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Manage Payment Methods</h2>
        <div id="responseMessage" class="mt-3"></div>
        <form id="addMethodForm" class="mt-4">
            <h4>Add New Payment Method</h4>
            <div class="mb-3">
                <label for="method_name" class="form-label">Payment Method Name</label>
                <input type="text" class="form-control" id="method_name" name="method_name" required>
            </div>
            <div class="mb-3">
                <label for="details" class="form-label">Details</label>
                <textarea class="form-control" id="details" name="details" rows="2" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100">Add Payment Method</button>
        </form>

        <h4 class="mt-5">Existing Payment Methods</h4>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Method Name</th>
                    <th>Details</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="paymentMethods">
                <!-- Dynamic Data -->
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function loadPaymentMethods() {
            try {
                const response = await fetch('/payment/methods');
                const data = await response.json();
                const tableBody = document.getElementById('paymentMethods');

                if (data.status === 'success') {
                    tableBody.innerHTML = '';
                    data.methods.forEach(method => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${method.id}</td>
                            <td>${method.name}</td>
                            <td>${method.details}</td>
                            <td>
                                <button class="btn btn-danger btn-sm" onclick="deletePaymentMethod(${method.id})">Delete</button>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                } else {
                    tableBody.innerHTML = `<tr><td colspan="4" class="text-center">No payment methods found.</td></tr>`;
                }
            } catch (error) {
                console.error('Error loading payment methods:', error);
            }
        }

        async function deletePaymentMethod(id) {
            try {
                const response = await fetch(`/payment/methods/delete/${id}`, { method: 'DELETE' });
                const data = await response.json();

                if (data.status === 'success') {
                    alert('Payment method deleted successfully.');
                    loadPaymentMethods();
                } else {
                    alert('Failed to delete payment method.');
                }
            } catch (error) {
                console.error('Error deleting payment method:', error);
            }
        }

        document.getElementById('addMethodForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const payload = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('/payment/methods/add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();

                if (result.status === 'success') {
                    document.getElementById('responseMessage').innerHTML = '<div class="alert alert-success">Payment method added successfully!</div>';
                    loadPaymentMethods();
                } else {
                    document.getElementById('responseMessage').innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
                }
            } catch (error) {
                console.error('Error adding payment method:', error);
            }
        });

        document.addEventListener('DOMContentLoaded', loadPaymentMethods);
    </script>
</body>
</html>
