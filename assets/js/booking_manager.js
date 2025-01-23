document.querySelectorAll('.refund-button').forEach(button => {
    button.addEventListener('click', () => {
        const bookingId = button.dataset.id;
        const refundAmount = button.dataset.amount;

        if (confirm(`Czy na pewno chcesz zwrócić ${refundAmount} PLN za tę rezerwację?`)) {
            fetch('/controllers/payment_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'refund', booking_id: bookingId, refund_amount: refundAmount })
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
