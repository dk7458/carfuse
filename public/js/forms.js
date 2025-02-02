document.addEventListener('DOMContentLoaded', function() {
    // Handle input validation across all forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    showError(input, 'This field is required.');
                } else {
                    clearError(input);
                }
            });
            if (!isValid) {
                event.preventDefault();
            }
        });

        // Real-time field validation
        const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                if (input.value.trim()) {
                    clearError(input);
                } else {
                    showError(input, 'This field is required.');
                }
            });
        });
    });

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

    function clearError(input) {
        let error = input.nextElementSibling;
        if (error && error.classList.contains('error-message')) {
            error.remove();
        }
        input.classList.remove('error');
    }
});
