document.addEventListener('DOMContentLoaded', function () {
    // Refund booking
    document.querySelectorAll('.refund-button').forEach(button => {
        button.addEventListener('click', function () {
            const bookingId = this.dataset.id;
            const amount = this.dataset.amount;

            if (confirm(`Are you sure you want to refund ${amount} PLN for this booking?`)) {
                fetch(`/public/api.php?endpoint=booking&action=refund`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ booking_id: bookingId })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to process refund');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Unexpected error:', error);
                });
            }
        });
    });
});
