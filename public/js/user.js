document.addEventListener("DOMContentLoaded", function () {
    // Fetch and manage user dashboard data
    function loadUserDashboard() {
        fetch("/api/user/dashboard.php")
            .then(response => response.json())
            .then(data => {
                document.getElementById("totalBookings").textContent = data.totalBookings || 0;
                document.getElementById("totalPayments").textContent = data.totalPayments + " PLN" || "0 PLN";
                document.getElementById("totalDocuments").textContent = data.totalDocuments || 0;
            })
            .catch(error => console.error("Błąd ładowania dashboardu:", error));
    }

    // Fetch and manage bookings
    function fetchBookings() {
        fetch("/api/user/bookings.php")
            .then(response => response.json())
            .then(data => {
                const bookingTable = document.getElementById("bookingList");
                bookingTable.innerHTML = "";

                if (data.length === 0) {
                    bookingTable.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Brak rezerwacji.</td></tr>`;
                } else {
                    data.forEach(booking => {
                        bookingTable.innerHTML += `
                            <tr>
                                <td>${booking.id}</td>
                                <td>${booking.vehicle}</td>
                                <td>${booking.start_date}</td>
                                <td>${booking.end_date}</td>
                                <td>${booking.status}</td>
                                <td><button class="btn btn-sm btn-info" onclick="viewBooking(${booking.id})">Podgląd</button></td>
                            </tr>
                        `;
                    });
                }
            })
            .catch(error => console.error("Błąd ładowania rezerwacji:", error));
    }

    loadUserDashboard();
    fetchBookings();
});
