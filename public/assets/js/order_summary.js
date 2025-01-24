// File Path: /assets/js/order_summary.js

document.addEventListener('DOMContentLoaded', function () {
    // Fetch order summary
    function fetchOrderSummary(orderId) {
        fetch(`/public/api.php?endpoint=order&action=fetch_summary&order_id=${orderId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch order summary');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update the UI with order summary
                    console.log('Order Summary:', data.summary);
                } else {
                    console.error('Error:', data.error);
                }
            })
            .catch(error => {
                console.error('Unexpected error:', error);
            });
    }

    // Example usage
    const orderId = 1; // Replace with actual order ID
    fetchOrderSummary(orderId);
});

document.getElementById('confirmBooking').addEventListener('click', (e) => {
    e.preventDefault();

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const vehicleId = document.getElementById('vehicleId').value;
    const pickupDate = document.getElementById('pickupDate').value;
    const dropoffDate = document.getElementById('dropoffDate').value;
    const totalPrice = document.getElementById('totalPrice').textContent;

    fetch('/public/api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
        },
        body: JSON.stringify({
            action: 'create_booking',
            vehicle_id: vehicleId,
            pickup_date: pickupDate,
            dropoff_date: dropoffDate,
            total_price: totalPrice,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                window.location.href = `/views/user/booking_confirmation.php?booking_id=${data.booking_id}`;
            } else {
                alert(data.error || 'Failed to create booking.');
            }
        })
        .catch((error) => console.error('Error creating booking:', error));
});
