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

    fetch('/public/api.php?endpoint=booking&action=check_availability', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
        },
        body: JSON.stringify({
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

function fetchFilteredData() {
    const search = document.getElementById('searchInput').value;
    const startDate = document.getElementById('startDateInput').value;
    const endDate = document.getElementById('endDateInput').value;

    fetch(`/public/api.php?endpoint=bookings&action=fetch_bookings&search=${search}&startDate=${startDate}&endDate=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const bookingsTableBody = document.getElementById('bookingsTableBody');
                bookingsTableBody.innerHTML = '';
                data.bookings.forEach(booking => {
                    const row = `<tr>
                        <td>${booking.customer}</td>
                        <td>${booking.date}</td>
                        <td>${booking.status}</td>
                    </tr>`;
                    bookingsTableBody.innerHTML += row;
                });
            }
        })
        .catch(error => console.error('Error:', error));
}
