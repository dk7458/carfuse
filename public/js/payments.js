import ajax from './ajax';
import { showErrorToast, showSuccessToast } from './toasts';

document.addEventListener('DOMContentLoaded', function () {
    const paymentForm = document.getElementById('paymentForm');

    if (paymentForm) {
        paymentForm.addEventListener('submit', function (event) {
            event.preventDefault();
            if (validateForm()) {
                const paymentDetails = new FormData(paymentForm);
                processPayment(paymentDetails);
            }
        });
    }

    initRealTimeValidation();
});

/**
 * Validates payment form before submission
 */
function validateForm() {
    let isValid = true;
    const cardNumber = document.getElementById('cardNumber').value.trim();
    const expiryDate = document.getElementById('expiryDate').value.trim();
    const cvv = document.getElementById('cvv').value.trim();

    if (!isValidCardNumber(cardNumber)) {
        showErrorToast('Nieprawidłowy numer karty. Wprowadź 16 cyfr.');
        isValid = false;
    }

    if (!isValidExpiryDate(expiryDate)) {
        showErrorToast('Nieprawidłowa data ważności. Użyj formatu MM/YY.');
        isValid = false;
    }

    if (!isValidCVV(cvv)) {
        showErrorToast('Nieprawidłowy kod CVV. Wprowadź 3 cyfry.');
        isValid = false;
    }

    return isValid;
}

/**
 * Sends payment request to the API
 */
async function processPayment(paymentDetails) {
    try {
        const response = await ajax.post('/payments', paymentDetails);
        if (response.success) {
            showSuccessToast('Płatność zakończona sukcesem! Przekierowywanie...');
            setTimeout(() => window.location.href = '/booking/confirmation', 1500);
        } else {
            displayErrors(response.errors);
        }
    } catch (error) {
        showErrorToast('Wystąpił błąd podczas przetwarzania płatności.');
    }
}

/**
 * Displays error messages
 */
function displayErrors(errors) {
    errors.forEach(error => showErrorToast(error));
}

/**
 * Validates card number format
 */
function isValidCardNumber(cardNumber) {
    return /^\d{16}$/.test(cardNumber);
}

/**
 * Validates expiry date format (MM/YY)
 */
function isValidExpiryDate(expiryDate) {
    return /^(0[1-9]|1[0-2])\/\d{2}$/.test(expiryDate);
}

/**
 * Validates CVV format (3 digits)
 */
function isValidCVV(cvv) {
    return /^\d{3}$/.test(cvv);
}

/**
 * Initializes real-time validation for payment fields
 */
function initRealTimeValidation() {
    document.getElementById('cardNumber')?.addEventListener('input', function () {
        if (!isValidCardNumber(this.value)) {
            showErrorToast('Numer karty powinien zawierać 16 cyfr.');
        }
    });

    document.getElementById('expiryDate')?.addEventListener('input', function () {
        if (!isValidExpiryDate(this.value)) {
            showErrorToast('Nieprawidłowy format daty ważności (MM/YY).');
        }
    });

    document.getElementById('cvv')?.addEventListener('input', function () {
        if (!isValidCVV(this.value)) {
            showErrorToast('Kod CVV powinien zawierać 3 cyfry.');
        }
    });
}
