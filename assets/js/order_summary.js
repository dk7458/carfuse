// File Path: /assets/js/order_summary.js

document.getElementById('confirmBooking').addEventListener('click', (e) => {
    e.preventDefault();

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const vehicleId = document.getElementById('vehicleId').value;
    const pickupDate = document.getElementById('pickupDate').value;
    const dropoffDate = document.getElementById('dropoffDate').value;
    const totalPrice = document.getElementById('totalPrice').textContent;

    fetch('/public/api.php?endpoint=orders&action=fetch_summary')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch order summary');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('Order Summary:', data.summary);
            } else {
                console.error('Error:', data.error);
            }
        })
        .catch(error => {
            console.error('Unexpected error:', error);
        });

    fetch('/public/api.php?endpoint=booking&action=create_booking', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
        },
        body: JSON.stringify({
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

document.getElementById('filterButton').addEventListener('click', () => {
    const search = document.getElementById('searchInput').value;
    const startDate = document.getElementById('startDateInput').value;
    const endDate = document.getElementById('endDateInput').value;

    fetch(`/public/api.php?endpoint=order_summary&action=fetch_orders&search=${search}&startDate=${startDate}&endDate=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ordersTableBody = document.getElementById('ordersTableBody');
                ordersTableBody.innerHTML = '';
                data.orders.forEach(order => {
                    const row = `<tr>
                        <td>${order.id}</td>
                        <td>${order.customer}</td>
                        <td>${order.date}</td>
                        <td>${order.total}</td>
                    </tr>`;
                    ordersTableBody.innerHTML += row;
                });
            }
        })
        .catch(error => console.error('Error:', error));
});
