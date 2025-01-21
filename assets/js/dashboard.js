// File Path: /assets/js/dashboard.js

window.addEventListener('DOMContentLoaded', () => {
    fetch('/controllers/booking_controller.php?action=fetch_bookings', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const bookingsContainer = document.getElementById('userBookings');
                bookingsContainer.innerHTML = '';

                data.bookings.forEach((booking) => {
                    bookingsContainer.innerHTML += `
                        <div class="booking-item">
                            <h3>Booking #${booking.id}</h3>
                            <p>Car: ${booking.vehicle_make} ${booking.vehicle_model}</p>
                            <p>Pickup Date: ${booking.pickup_date}</p>
                            <p>Dropoff Date: ${booking.dropoff_date}</p>
                            <p>Total Price: ${booking.total_price} PLN</p>
                            <a href="/users/user${booking.user_id}/documents/contract_${booking.id}.pdf" target="_blank">Download Contract</a>
                        </div>
                    `;
                });
            } else {
                alert(data.error || 'Failed to fetch bookings.');
            }
        })
        .catch((error) => console.error('Error fetching bookings:', error));
});
