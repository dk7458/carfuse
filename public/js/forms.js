document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', function (event) {
            if (!validateForm(form)) {
                event.preventDefault();
            }
        });

        attachRealTimeValidation(form);
    });
});

/**
 * Waliduje formularz przed wysłaniem.
 */
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');

    inputs.forEach(input => {
        if (!validateInput(input)) {
            isValid = false;
        }
    });

    return isValid;
}

/**
 * Dołącza walidację w czasie rzeczywistym dla pól formularza.
 */
function attachRealTimeValidation(form) {
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');

    inputs.forEach(input => {
        input.addEventListener('input', function () {
            validateInput(input);
        });
    });
}

/**
 * Waliduje pojedyncze pole formularza.
 */
function validateInput(input) {
    const value = input.value.trim();
    const type = input.type;

    if (!value) {
        showError(input, 'To pole jest wymagane.');
        return false;
    }

    if (type === 'email' && !isValidEmail(value)) {
        showError(input, 'Wprowadź poprawny adres e-mail.');
        return false;
    }

    if (type === 'password' && value.length < 6) {
        showError(input, 'Hasło musi zawierać co najmniej 6 znaków.');
        return false;
    }

    if (input.dataset.minLength && value.length < input.dataset.minLength) {
        showError(input, `To pole musi mieć co najmniej ${input.dataset.minLength} znaków.`);
        return false;
    }

    clearError(input);
    return true;
}

/**
 * Wyświetla komunikat o błędzie obok pola formularza.
 */
function showError(input, message) {
    let error = input.nextElementSibling;
    if (!error || !error.classList.contains('error-message')) {
        error = document.createElement('div');
        error.classList.add('error-message');
        input.parentNode.insertBefore(error, input.nextSibling);
    }
    error.textContent = message;
    input.classList.add('error');
}

/**
 * Usuwa komunikat o błędzie.
 */
function clearError(input) {
    let error = input.nextElementSibling;
    if (error && error.classList.contains('error-message')) {
        error.remove();
    }
    input.classList.remove('error');
}

/**
 * Sprawdza poprawność adresu e-mail.
 */
function isValidEmail(email) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailPattern.test(email);
}
