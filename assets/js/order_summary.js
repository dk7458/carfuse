// File Path: /assets/js/order_summary.js

document.getElementById('confirmBooking').addEventListener('click', (e) => {
    e.preventDefault();

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const vehicleId = document.getElementById('vehicleId').value;
    const pickupDate = document.getElementById('pickupDate').value;
    const dropoffDate = document.getElementById('dropoffDate').value;
    const totalPrice = document.getElementById('totalPrice').textContent;

    fetch('/controllers/booking_controller.php', {
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
