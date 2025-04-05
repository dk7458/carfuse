/**
 * CarFuse Form Handler Component
 * Manages form operations including validation, submission, and state
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Define component name
    const COMPONENT_NAME = 'formHandler';
    
    // Check if already initialized
    if (CarFuse[COMPONENT_NAME]) {
        console.warn(`CarFuse ${COMPONENT_NAME} component already initialized.`);
        return;
    }
    
    // Define the component
    const component = {
        // Configuration
        config: {
            debug: false,
            resetOnSuccess: true,
            defaultMethod: 'POST',
            validateBeforeSubmit: true,
            showLoadingIndicator: true
        },
        
        // State
        state: {
            initialized: false,
            activeSubmissions: new Set()
        },
        
        /**
         * Initialize Form Handler functionalities
         * @param {Object} options - Configuration options
         */
        init: function(options = {}) {
            // Apply custom options
            Object.assign(this.config, options);
            
            this.log('Initializing Form Handler component');
            this.setupFormListeners();
            this.state.initialized = true;
            this.log('Form Handler component initialized');
            
            return Promise.resolve();
        },
        
        /**
         * Log a message to the console if debug mode is enabled
         * @param {string} message - Message to log
         * @param {*} [data] - Optional data to include in log
         */
        log: function(message, data) {
            if (this.config.debug || CarFuse.config.debug) {
                console.log(`[CarFuse Form Handler] ${message}`, data || '');
            }
        },
        
        /**
         * Setup form event listeners
         */
        setupFormListeners: function() {
            this.log('Setting up form event listeners');
            
            // Handle form submissions
            document.addEventListener('submit', (event) => {
                const form = event.target;
                
                // Skip if the form has htmx attributes or data-no-validate
                if (form.hasAttribute('hx-post') || 
                    form.hasAttribute('hx-get') || 
                    form.hasAttribute('data-no-validate')) {
                    return;
                }
                
                // Check if the form needs validation
                if (form.hasAttribute('data-validate')) {
                    event.preventDefault();
                    
                    if (this.validateForm(form)) {
                        // Form is valid, submit it programmatically
                        this.submitForm(form);
                    }
                }
            });
        },
        
        /**
         * Validate form based on data attributes
         * @param {HTMLFormElement} form - Form to validate
         * @returns {boolean} True if form is valid
         */
        validateForm: function(form) {
            let isValid = true;
            const fields = form.querySelectorAll('[data-validate]');
            
            fields.forEach(field => {
                const rules = field.dataset.validate.split('|');
                const value = field.value;
                let fieldValid = true;
                let errorMessage = '';
                
                // Reset field error state
                field.classList.remove('border-red-500');
                const errorEl = document.getElementById(`${field.name}-error`);
                if (errorEl) errorEl.textContent = '';
                
                // Process each validation rule
                for (const rule of rules) {
                    if (!fieldValid) break; // Stop on first error
                    
                    if (rule === 'required' && (!value || value.trim() === '')) {
                        fieldValid = false;
                        errorMessage = 'To pole jest wymagane.';
                    } else if (rule === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                        fieldValid = false;
                        errorMessage = 'Proszę podać prawidłowy adres email.';
                    } else if (rule.startsWith('min:') && value) {
                        const min = parseInt(rule.split(':')[1]);
                        if (value.length < min) {
                            fieldValid = false;
                            errorMessage = `Wartość musi zawierać co najmniej ${min} znaków.`;
                        }
                    } else if (rule.startsWith('max:') && value) {
                        const max = parseInt(rule.split(':')[1]);
                        if (value.length > max) {
                            fieldValid = false;
                            errorMessage = `Wartość nie może przekraczać ${max} znaków.`;
                        }
                    } else if (rule === 'numeric' && value && !/^-?\d*\.?\d+$/.test(value)) {
                        fieldValid = false;
                        errorMessage = 'Proszę podać wartość liczbową.';
                    } else if (rule === 'phone' && value && !/^(?:\+48|48)?[0-9]{9}$/.test(value.replace(/\s+/g, ''))) {
                        fieldValid = false;
                        errorMessage = 'Proszę podać prawidłowy numer telefonu.';
                    }
                }
                
                // Mark field as invalid if needed
                if (!fieldValid) {
                    isValid = false;
                    field.classList.add('border-red-500');
                    
                    // Show error message
                    if (!errorEl) {
                        const newErrorEl = document.createElement('p');
                        newErrorEl.id = `${field.name}-error`;
                        newErrorEl.className = 'text-red-500 text-sm mt-1';
                        field.parentNode.appendChild(newErrorEl);
                        newErrorEl.textContent = errorMessage;
                    } else {
                        errorEl.textContent = errorMessage;
                    }
                }
            });
            
            return isValid;
        },
        
        /**
         * Submit form via AJAX
         * @param {HTMLFormElement} form - Form to submit
         */
        submitForm: function(form) {
            const formData = new FormData(form);
            const url = form.action;
            const method = form.method.toUpperCase() || this.config.defaultMethod;
            
            // Add CSRF token if needed
            if (typeof window.AuthHelper !== 'undefined' && typeof window.AuthHelper.getCsrfToken === 'function') {
                const csrfToken = window.AuthHelper.getCsrfToken();
                if (csrfToken) {
                    formData.append('_token', csrfToken);
                }
            } else {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrfToken) {
                    formData.append('_token', csrfToken);
                }
            }
            
            // Show loading indicator
            if (this.config.showLoadingIndicator) {
                this.showLoadingIndicator(form);
            }
            
            // Track the submission
            const submissionId = `form_${Date.now()}`;
            this.state.activeSubmissions.add(submissionId);
            
            fetch(url, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                // Handle success
                this.showToast('Sukces', data.message || 'Operacja zakończona pomyślnie', 'success');
                
                // Reset form if needed
                if (this.config.resetOnSuccess || form.hasAttribute('data-reset-on-success')) {
                    form.reset();
                }
                
                // Dispatch success event
                form.dispatchEvent(new CustomEvent('form:success', { 
                    bubbles: true,
                    detail: { data, form }
                }));
            })
            .catch(error => {
                // Handle error
                this.showToast('Błąd', error.message || 'Wystąpił błąd podczas przetwarzania żądania', 'error');
                
                // Dispatch error event
                form.dispatchEvent(new CustomEvent('form:error', {
                    bubbles: true,
                    detail: { error, form }
                }));
            })
            .finally(() => {
                // Hide loading indicator
                if (this.config.showLoadingIndicator) {
                    this.hideLoadingIndicator(form);
                }
                
                // Remove from active submissions
                this.state.activeSubmissions.delete(submissionId);
            });
        },
        
        /**
         * Show loading indicator on form
         * @param {HTMLFormElement} form - Form to show loading indicator on
         */
        showLoadingIndicator: function(form) {
            form.classList.add('processing');
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('loading');
                
                // Store original text if not already stored
                if (!submitButton.dataset.originalText) {
                    submitButton.dataset.originalText = submitButton.innerText;
                    
                    // Create spinner element
                    const spinner = document.createElement('span');
                    spinner.className = 'btn-spinner mr-2';
                    spinner.innerHTML = `<div class="spinner spinner-border-t h-4 w-4"></div>`;
                    
                    // Add spinner and change text
                    submitButton.prepend(spinner);
                    
                    if (submitButton.querySelector('.btn-text')) {
                        submitButton.querySelector('.btn-text').textContent = 'Przetwarzanie...';
                    } else {
                        const textSpan = document.createElement('span');
                        textSpan.className = 'btn-text';
                        textSpan.textContent = 'Przetwarzanie...';
                        submitButton.appendChild(textSpan);
                    }
                }
            }
        },
        
        /**
         * Hide loading indicator on form
         * @param {HTMLFormElement} form - Form to hide loading indicator on
         */
        hideLoadingIndicator: function(form) {
            form.classList.remove('processing');
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.classList.remove('loading');
                
                // Remove spinner
                const spinner = submitButton.querySelector('.btn-spinner');
                if (spinner) {
                    spinner.remove();
                }
                
                // Restore original text if it was stored
                if (submitButton.dataset.originalText) {
                    if (submitButton.querySelector('.btn-text')) {
                        submitButton.querySelector('.btn-text').textContent = submitButton.dataset.originalText;
                    } else {
                        submitButton.innerText = submitButton.dataset.originalText;
                    }
                    delete submitButton.dataset.originalText;
                }
            }
        },
        
        /**
         * Show toast notification
         * @param {string} title - Toast title
         * @param {string} message - Toast message
         * @param {string} type - Toast type: success, error, warning, info
         */
        showToast: function(title, message, type = 'success') {
            // Check if we have notifications component available
            if (CarFuse.notifications && CarFuse.notifications.showToast) {
                CarFuse.notifications.showToast(title, message, type);
                return;
            }
            
            // Check if we have Alpine.js toast system
            const toastSystem = document.querySelector('[x-data*="toastSystem"]');
            if (toastSystem && window.Alpine) {
                // Use Alpine.js toast system
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { title, message, type }
                }));
            } else {
                // Fallback to simple toast implementation
                alert(`${title}: ${message}`);
            }
        }
    };
    
    // Register the component
    CarFuse[COMPONENT_NAME] = component;
    
    // Register with CarFuse if available
    if (CarFuse.registerComponent) {
        CarFuse.registerComponent(COMPONENT_NAME, component);
    }
})();
