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
 * Validates form before submission.
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
 * Attaches real-time validation to form inputs.
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
 * Validates a single input field.
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

    if (type === 'password') {
        if (value.length < 6) {
            showError(input, 'Hasło musi zawierać co najmniej 6 znaków.');
            return false;
        }
        if (!/\d/.test(value) || !/[A-Za-z]/.test(value)) {
            showError(input, 'Hasło musi zawierać litery i cyfry.');
            return false;
        }
    }

    if (input.dataset.minLength && value.length < input.dataset.minLength) {
        showError(input, `To pole musi mieć co najmniej ${input.dataset.minLength} znaków.`);
        return false;
    }

    clearError(input);
    return true;
}

/**
 * Displays an error message next to the form field.
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
 * Clears an error message when input is corrected.
 */
function clearError(input) {
    let error = input.nextElementSibling;
    if (error && error.classList.contains('error-message')) {
        error.remove();
    }
    input.classList.remove('error');
}

/**
 * Checks if an email is valid.
 */
function isValidEmail(email) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    return emailPattern.test(email);
}
