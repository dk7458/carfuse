import ajax from './ajax';

document.addEventListener('DOMContentLoaded', function() {
    const paymentForm = document.getElementById('paymentForm');

    paymentForm.addEventListener('submit', function(event) {
        event.preventDefault();
        if (validateForm()) {
            const paymentDetails = new FormData(paymentForm);
            processPayment(paymentDetails);
        }
    });

    function validateForm() {
        let isValid = true;
        const cardNumber = document.getElementById('cardNumber').value;
        const expiryDate = document.getElementById('expiryDate').value;
        const cvv = document.getElementById('cvv').value;
        const errorMessage = document.getElementById('errorMessage');

        errorMessage.innerHTML = '';

        if (!cardNumber.match(/^\d{16}$/)) {
            errorMessage.innerHTML += '<p>Invalid card number. Must be 16 digits.</p>';
            isValid = false;
        }
        if (!expiryDate.match(/^\d{2}\/\d{2}$/)) {
            errorMessage.innerHTML += '<p>Invalid expiry date. Must be in MM/YY format.</p>';
            isValid = false;
        }
        if (!cvv.match(/^\d{3}$/)) {
            errorMessage.innerHTML += '<p>Invalid CVV. Must be 3 digits.</p>';
            isValid = false;
        }

        return isValid;
    }

    async function processPayment(paymentDetails) {
        try {
            const response = await ajax.post('/payments', paymentDetails);
            if (response.success) {
                window.location.href = '/booking/confirmation';
            } else {
                displayErrors(response.errors);
            }
        } catch (error) {
            displayErrors(['An error occurred while processing the payment. Please try again.']);
        }
    }

    function displayErrors(errors) {
        const errorMessage = document.getElementById('errorMessage');
        errorMessage.innerHTML = '';
        errors.forEach(function(error) {
            errorMessage.innerHTML += `<p>${error}</p>`;
        });
    }
});
