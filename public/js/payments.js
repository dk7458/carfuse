document.addEventListener('DOMContentLoaded', function() {
    const paymentForm = document.getElementById('paymentForm');

    paymentForm.addEventListener('submit', function(event) {
        event.preventDefault();
        if (validateForm()) {
            processPayment();
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

    function processPayment() {
        const formData = new FormData(paymentForm);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/payment/process', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    window.location.href = '/booking/confirmation';
                } else {
                    displayErrors(response.errors);
                }
            } else {
                displayErrors(['An error occurred while processing the payment. Please try again.']);
            }
        };
        xhr.send(formData);
    }

    function displayErrors(errors) {
        const errorMessage = document.getElementById('errorMessage');
        errorMessage.innerHTML = '';
        errors.forEach(function(error) {
            errorMessage.innerHTML += `<p>${error}</p>`;
        });
    }
});
