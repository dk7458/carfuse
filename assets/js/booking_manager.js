document.querySelectorAll('.refund-button').forEach(button => {
    button.addEventListener('click', () => {
        const bookingId = button.dataset.id;
        const refundAmount = button.dataset.amount;

        if (confirm(`Czy na pewno chcesz zwrócić ${refundAmount} PLN za tę rezerwację?`)) {
            fetch('/public/api.php?endpoint=booking_manager&action=refund', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ booking_id: bookingId, refund_amount: refundAmount })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.success);
                    location.reload();
                } else {
                    alert(data.error || 'Błąd podczas przetwarzania zwrotu.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas przetwarzania żądania.');
            });
        }
    });
});

function fetchFilteredData() {
    const search = document.getElementById('searchInput').value;
    const startDate = document.getElementById('startDateInput').value;
    const endDate = document.getElementById('endDateInput').value;

    fetch(`/public/api.php?endpoint=booking_manager&action=fetch_bookings&search=${search}&startDate=${startDate}&endDate=${endDate}`)
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
