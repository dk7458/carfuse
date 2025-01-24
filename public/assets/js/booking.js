// File Path: /assets/js/booking.js

document.getElementById('checkAvailability').addEventListener('click', (e) => {
    e.preventDefault();

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const pickupDate = document.getElementById('pickupDate').value;
    const dropoffDate = document.getElementById('dropoffDate').value;

    if (!pickupDate || !dropoffDate) {
        alert('Please enter both pickup and drop-off dates.');
        return;
    }

    fetch('/public/api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
        },
        body: JSON.stringify({
            endpoint: 'booking',
            action: 'check_availability',
            pickup_date: pickupDate,
            dropoff_date: dropoffDate,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const carsContainer = document.getElementById('availableCars');
                carsContainer.innerHTML = '';

                data.cars.forEach((car) => {
                    carsContainer.innerHTML += `
                        <div class="car-tile">
                            <img src="${car.image_path}" alt="${car.make} ${car.model}">
                            <h3>${car.make} ${car.model} (${car.year})</h3>
                            <p>Price per day: ${car.price_per_day} PLN</p>
                            ${car.has_promo ? '<p class="promo-label">Promo Available!</p>' : ''}
                            <button class="select-car" data-id="${car.id}">Select Car</button>
                        </div>
                    `;
                });
            } else {
                alert(data.error || 'Failed to fetch available cars.');
            }
        })
        .catch((error) => console.error('Error fetching cars:', error));
});

document.addEventListener('DOMContentLoaded', function () {
    // Fetch booking details
    function fetchBookingDetails(bookingId) {
        fetch(`/public/api.php?endpoint=booking&action=fetch_details&booking_id=${bookingId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch booking details');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update the UI with booking details
                    console.log('Booking Details:', data.booking);
                } else {
                    console.error('Error:', data.error);
                }
            })
            .catch(error => {
                console.error('Unexpected error:', error);
            });
    }

    // Example usage
    const bookingId = 1; // Replace with actual booking ID
    fetchBookingDetails(bookingId);
});
