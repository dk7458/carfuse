document.addEventListener('DOMContentLoaded', function () {
    document.body.addEventListener('submit', function (event) {
        const form = event.target;
        if (form.tagName === 'FORM') {
            event.preventDefault();
            if (validateForm(form)) {
                submitForm(form);
            }
        }
    });

    document.body.addEventListener('input', function (event) {
        const input = event.target;
        if (input.closest('form')) {
            validateInput(input);
        }
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
 * Validates a single input field.
 */
function validateInput(input) {
    const value = input.value.trim();
    const type = input.type;

    if (!value) {
        showError(input, 'This field is required.');
        return false;
    }

    if (type === 'email' && !isValidEmail(value)) {
        showError(input, 'Please enter a valid email address.');
        return false;
    }

    if (type === 'password') {
        if (value.length < 6) {
            showError(input, 'Password must be at least 6 characters long.');
            return false;
        }
        if (!/\d/.test(value) || !/[A-Za-z]/.test(value)) {
            showError(input, 'Password must contain both letters and numbers.');
            return false;
        }
    }

    if (input.dataset.minLength && value.length < input.dataset.minLength) {
        showError(input, `This field must be at least ${input.dataset.minLength} characters long.`);
        return false;
    }

    clearError(input);
    return true;
}

/**
 * Submits the form via AJAX.
 */
function submitForm(form) {
    const formData = new FormData(form);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    formData.append('_csrf', csrfToken);

    fetch(form.action, {
        method: form.method,
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            form.reset();
            showSuccessMessage(form, data.message);
        } else {
            showErrorMessages(form, data.errors);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
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
 * Displays success message after form submission.
 */
function showSuccessMessage(form, message) {
    const successMessage = document.createElement('div');
    successMessage.classList.add('success-message');
    successMessage.textContent = message;
    form.appendChild(successMessage);
    setTimeout(() => {
        successMessage.remove();
    }, 5000);
}

/**
 * Displays multiple error messages after form submission.
 */
function showErrorMessages(form, errors) {
    for (const [inputName, message] of Object.entries(errors)) {
        const input = form.querySelector(`[name="${inputName}"]`);
        if (input) {
            showError(input, message);
        }
    }
}

/**
 * Checks if an email is valid.
 */
function isValidEmail(email) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    return emailPattern.test(email);
}
