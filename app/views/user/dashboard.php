<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - CarFuse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">User Dashboard</h2>
        <div class="card shadow mt-3">
            <div class="card-body">
                <h4 class="mb-4">My Bookings</h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Vehicle</th>
                            <th>Pickup Date</th>
                            <th>Dropoff Date</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bookingHistory">
                        <!-- Dynamic data will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function loadBookingHistory() {
            try {
                const response = await fetch('/api/bookings');
                const data = await response.json();

                if (data.status === 'success') {
                    const bookings = data.bookings;
                    const tableBody = document.getElementById('bookingHistory');
                    tableBody.innerHTML = '';

                    bookings.forEach(booking => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${booking.id}</td>
                            <td>${booking.vehicle_name}</td>
                            <td>${booking.pickup_date}</td>
                            <td>${booking.dropoff_date}</td>
                            <td>${booking.status}</td>
                            <td>${booking.payment_status}</td>
                            <td>
                                <button onclick="viewBooking(${booking.id})" class="btn btn-info btn-sm">Details</button>
                                <button onclick="rescheduleBooking(${booking.id})" class="btn btn-warning btn-sm">Reschedule</button>
                                <button onclick="cancelBooking(${booking.id})" class="btn btn-danger btn-sm">Cancel</button>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                } else {
                    alert('Failed to fetch booking history');
                }
            } catch (error) {
                console.error('Error loading booking history:', error);
            }
        }

        async function viewBooking(id) {
            try {
                const response = await fetch(`/api/bookings/${id}`);
                const booking = await response.json();
                if (booking.status === 'success') {
                    alert(JSON.stringify(booking.data, null, 2));
                } else {
                    alert('Failed to fetch booking details');
                }
            } catch (error) {
                console.error('Error viewing booking:', error);
            }
        }

        async function rescheduleBooking(id) {
            const pickupDate = prompt("Enter new pickup date (YYYY-MM-DD):");
            const dropoffDate = prompt("Enter new dropoff date (YYYY-MM-DD):");

            if (!pickupDate || !dropoffDate) return;

            try {
                const response = await fetch(`/api/bookings/${id}/reschedule`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ pickup_date: pickupDate, dropoff_date: dropoffDate })
                });
                const result = await response.json();

                if (result.status === 'success') {
                    alert('Booking rescheduled successfully');
                    loadBookingHistory();
                } else {
                    alert(result.message || 'Failed to reschedule booking');
                }
            } catch (error) {
                console.error('Error rescheduling booking:', error);
            }
        }

        async function cancelBooking(id) {
            if (!confirm('Are you sure you want to cancel this booking?')) return;

            try {
                const response = await fetch(`/api/bookings/${id}/cancel`, { method: 'DELETE' });
                const result = await response.json();

                if (result.status === 'success') {
                    alert('Booking canceled successfully');
                    loadBookingHistory();
                } else {
                    alert(result.message || 'Failed to cancel booking');
                }
            } catch (error) {
                console.error('Error canceling booking:', error);
            }
        }

        document.addEventListener('DOMContentLoaded', loadBookingHistory);
    </script>
</body>
</html>
