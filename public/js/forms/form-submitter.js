/**
 * CarFuse Form Submitter
 * Handles AJAX form submissions with built-in CSRF protection and file uploads
 */
(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    if (!CarFuse.forms) {
        CarFuse.forms = {};
    }
    
    /**
     * FormSubmitter class handles form submissions
     */
    class FormSubmitter {
        /**
         * Create a new form submitter
         * @param {Object} options - Submitter options
         */
        constructor(options = {}) {
            this.options = {
                ajaxEnabled: true,
                csrfToken: null,
                csrfTokenSelector: 'meta[name="csrf-token"]',
                csrfTokenHeader: 'X-CSRF-TOKEN',
                csrfTokenParam: '_token',
                defaultMethod: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                beforeSubmit: null,
                afterSubmit: null,
                onSuccess: null,
                onError: null,
                loadingClass: 'is-submitting',
                loadingText: 'Submitting...',
                disableSubmitButton: true,
                resetOnSuccess: false,
                useButtonLoadingState: true,
                responseType: 'json',
                redirectOnSuccess: true,
                ...options
            };
            
            // Create logger if CarFuse errorHandler exists
            this.logger = CarFuse.errorHandler?.createLogger 
                ? CarFuse.errorHandler.createLogger('FormSubmitter') 
                : console;
        }
        
        /**
         * Initialize the submitter with a form
         * @param {HTMLFormElement} form - Form element
         */
        init(form) {
            if (!(form instanceof HTMLFormElement)) {
                throw new Error('Form element is required');
            }
            
            this.form = form;
            
            // Store original submit handler
            this.originalSubmitHandler = form.onsubmit;
            
            // Override submit handler
            form.onsubmit = this.handleSubmit.bind(this);
            
            // Set up data attributes
            if (form.dataset.ajaxForm !== undefined) {
                this.options.ajaxEnabled = form.dataset.ajaxForm !== 'false';
            }
            
            if (form.dataset.resetOnSuccess !== undefined) {
                this.options.resetOnSuccess = form.dataset.resetOnSuccess !== 'false';
            }
            
            if (form.dataset.redirectOnSuccess !== undefined) {
                this.options.redirectOnSuccess = form.dataset.redirectOnSuccess !== 'false';
            }
            
            if (form.dataset.responseType) {
                this.options.responseType = form.dataset.responseType;
            }
            
            return this;
        }
        
        /**
         * Handle form submission
         * @param {Event} event - Submit event
         */
        handleSubmit(event) {
            // Call original submit handler if it exists
            if (typeof this.originalSubmitHandler === 'function') {
                // If it returns false, don't proceed
                if (this.originalSubmitHandler(event) === false) {
                    event.preventDefault();
                    return false;
                }
            }
            
            // Skip AJAX handling if disabled
            if (!this.options.ajaxEnabled) {
                return true;
            }
            
            // Prevent default form submission
            event.preventDefault();
            
            // Submit form via AJAX
            this.submitForm();
            
            return false;
        }
        
        /**
         * Submit the form via AJAX
         * @returns {Promise} Submission promise
         */
        submitForm() {
            const form = this.form;
            const options = this.options;
            
            // Get form details
            const method = (form.getAttribute('method') || options.defaultMethod).toUpperCase();
            const action = form.getAttribute('action') || form.action || window.location.href;
            
            // Call beforeSubmit callback if provided
            if (typeof options.beforeSubmit === 'function') {
                const shouldContinue = options.beforeSubmit(form);
                if (shouldContinue === false) {
                    return Promise.reject(new Error('Submission cancelled by beforeSubmit'));
                }
            }
            
            // Show loading state
            this.showLoading();
            
            // Add CSRF token to form data
            const formData = this.prepareFormData(form);
            
            // Prepare request options
            const fetchOptions = {
                method: method,
                headers: {
                    ...options.headers
                },
                credentials: 'same-origin'
            };
            
            // Add CSRF token to headers
            const csrfToken = this.getCsrfToken();
            if (csrfToken) {
                fetchOptions.headers[options.csrfTokenHeader] = csrfToken;
            }
            
            // Handle different request content types based on form data
            const hasFile = this.formHasFileInputs(form);
            
            if (hasFile) {
                // Use FormData for file uploads
                fetchOptions.body = formData;
            } else {
                // Use JSON for regular submissions
                const formObject = this.formDataToObject(formData);
                fetchOptions.body = JSON.stringify(formObject);
                fetchOptions.headers['Content-Type'] = 'application/json';
            }
            
            // Perform AJAX request
            return fetch(action, fetchOptions)
                .then(response => {
                    // Call afterSubmit callback
                    if (typeof options.afterSubmit === 'function') {
                        options.afterSubmit(form, response);
                    }
                    
                    // Clear loading state
                    this.clearLoading();
                    
                    // Process response based on status
                    if (!response.ok) {
                        return this.handleErrorResponse(response);
                    }
                    
                    // Process response based on content type
                    return this.processSuccessResponse(response);
                })
                .catch(error => {
                    // Clear loading state
                    this.clearLoading();
                    
                    // Log error
                    this.logger.error('Form submission error', error);
                    
                    // Call onError callback
                    if (typeof options.onError === 'function') {
                        options.onError(error, form);
                    }
                    
                    // Show error message using errorHandler if available
                    if (window.CarFuse.errorHandler) {
                        window.CarFuse.errorHandler.processError({
                            type: window.CarFuse.errorHandler.ErrorTypes.NETWORK.REQUEST_FAILED,
                            originalError: error,
                            message: error.message || 'Form submission failed',
                            source: 'form-submitter',
                            context: {
                                form: form.id || form.name || 'unnamed-form',
                                action: action
                            }
                        });
                    }
                    
                    // Re-throw error for further processing
                    throw error;
                });
        }
        
        /**
         * Process successful form submission response
         * @param {Response} response - Fetch response
         * @returns {Promise} Processed response
         */
        processSuccessResponse(response) {
            const options = this.options;
            const form = this.form;
            
            // Parse response based on type
            let responsePromise;
            
            switch (options.responseType) {
                case 'json':
                    responsePromise = response.json().catch(error => {
                        this.logger.warn('Failed to parse JSON response', error);
                        return { success: true };
                    });
                    break;
                case 'text':
                    responsePromise = response.text();
                    break;
                case 'blob':
                    responsePromise = response.blob();
                    break;
                default:
                    responsePromise = response.json().catch(() => response.text());
            }
            
            return responsePromise.then(result => {
                // Reset form if configured
                if (options.resetOnSuccess) {
                    form.reset();
                }
                
                // Call onSuccess callback
                if (typeof options.onSuccess === 'function') {
                    options.onSuccess(result, form, response);
                }
                
                // Dispatch success event
                const successEvent = new CustomEvent('form:submit-success', {
                    bubbles: true,
                    detail: {
                        form: form,
                        result: result,
                        response: response
                    }
                });
                form.dispatchEvent(successEvent);
                
                // Handle result.redirect for server-side redirects
                if (options.redirectOnSuccess && result && result.redirect) {
                    window.location.href = result.redirect;
                }
                
                return result;
            });
        }
        
        /**
         * Handle error response
         * @param {Response} response - Fetch response
         * @returns {Promise} Error promise
         */
        handleErrorResponse(response) {
            const options = this.options;
            const form = this.form;
            
            // Try to parse response as JSON
            return response.json()
                .catch(() => {
                    // If not JSON, return simple error object
                    return {
                        success: false,
                        message: `Request failed with status ${response.status}`
                    };
                })
                .then(error => {
                    // Call onError callback
                    if (typeof options.onError === 'function') {
                        options.onError(error, form, response);
                    }
                    
                    // Handle validation errors (Laravel format)
                    if (error.errors && typeof error.errors === 'object') {
                        this.handleValidationErrors(error.errors);
                    }
                    
                    // Dispatch error event
                    const errorEvent = new CustomEvent('form:submit-error', {
                        bubbles: true,
                        detail: {
                            form: form,
                            error: error,
                            response: response
                        }
                    });
                    form.dispatchEvent(errorEvent);
                    
                    // Show error message using errorHandler if available
                    if (window.CarFuse.errorHandler) {
                        const errorType = response.status === 422 
                            ? window.CarFuse.errorHandler.ErrorTypes.DATA.VALIDATION 
                            : window.CarFuse.errorHandler.ErrorTypes.NETWORK.REQUEST_FAILED;
                            
                        window.CarFuse.errorHandler.processError({
                            type: errorType,
                            message: error.message || 'Form submission failed',
                            source: 'form-submitter',
                            context: {
                                form: form.id || form.name || 'unnamed-form',
                                status: response.status,
                                errors: error.errors
                            }
                        });
                    }
                    
                    // Return rejected promise
                    return Promise.reject(error);
                });
        }
        
        /**
         * Handle validation errors
         * @param {Object} errors - Validation errors object
         */
        handleValidationErrors(errors) {
            const form = this.form;
            
            // Use FormValidator if available
            if (window.CarFuse.forms.Validator) {
                // Try to find existing validator
                if (form._validator && typeof form._validator.showFieldError === 'function') {
                    // Display each error
                    Object.entries(errors).forEach(([field, messages]) => {
                        const message = Array.isArray(messages) ? messages[0] : messages;
                        form._validator.showFieldError(field, message);
                    });
                    return;
                }
            }
            
            // Use ErrorDisplay if available
            if (window.CarFuse.forms.ErrorDisplay) {
                const errorDisplay = new window.CarFuse.forms.ErrorDisplay();
                errorDisplay.attach(form).showErrors(this.normalizeErrors(errors));
                return;
            }
            
            // Fallback to basic error display
            Object.entries(errors).forEach(([field, messages]) => {
                const message = Array.isArray(messages) ? messages[0] : messages;
                const fieldElement = form.querySelector(`[name="${field}"]`);
                
                if (fieldElement) {
                    // Add error class
                    fieldElement.classList.add('is-invalid');
                    
                    // Create error message element if doesn't exist
                    let errorElement = form.querySelector(`[data-validation-error="${field}"]`);
                    if (!errorElement) {
                        errorElement = document.createElement('div');
                        errorElement.className = 'invalid-feedback';
                        errorElement.setAttribute('data-validation-error', field);
                        fieldElement.parentNode.appendChild(errorElement);
                    }
                    
                    errorElement.textContent = message;
                    errorElement.style.display = 'block';
                }
            });
        }
        
        /**
         * Normalize different error formats to a consistent format
         * @param {Object} errors - Error object
         * @returns {Object} Normalized errors
         */
        normalizeErrors(errors) {
            const normalized = {};
            
            Object.entries(errors).forEach(([field, messages]) => {
                normalized[field] = Array.isArray(messages) ? messages[0] : messages;
            });
            
            return normalized;
        }
        
        /**
         * Prepare form data for submission
         * @param {HTMLFormElement} form - Form element
         * @returns {FormData} Form data object
         */
        prepareFormData(form) {
            const formData = new FormData(form);
            
            // Add CSRF token if not already present
            const csrfToken = this.getCsrfToken();
            if (csrfToken && !formData.has(this.options.csrfTokenParam)) {
                formData.append(this.options.csrfTokenParam, csrfToken);
            }
            
            return formData;
        }
        
        /**
         * Convert FormData to plain object
         * @param {FormData} formData - Form data
         * @returns {Object} Form data as object
         */
        formDataToObject(formData) {
            const object = {};
            
            formData.forEach((value, key) => {
                // Handle array inputs (e.g., select multiple)
                if (key.endsWith('[]')) {
                    const arrayKey = key.slice(0, -2);
                    if (!object[arrayKey]) {
                        object[arrayKey] = [];
                    }
                    object[arrayKey].push(value);
                } else if (object[key] !== undefined) {
                    // If key already exists, convert to array
                    if (!Array.isArray(object[key])) {
                        object[key] = [object[key]];
                    }
                    object[key].push(value);
                } else {
                    object[key] = value;
                }
            });
            
            return object;
        }
        
        /**
         * Check if form contains file inputs
         * @param {HTMLFormElement} form - Form element
         * @returns {boolean} True if form has file inputs
         */
        formHasFileInputs(form) {
            return form.querySelector('input[type="file"]') !== null;
        }
        
        /**
         * Get CSRF token from options or DOM
         * @returns {string|null} CSRF token
         */
        getCsrfToken() {
            // Use token from options if available
            if (this.options.csrfToken) {
                return this.options.csrfToken;
            }
            
            // Use AuthHelper if available
            if (window.AuthHelper && typeof window.AuthHelper.getCsrfToken === 'function') {
                return window.AuthHelper.getCsrfToken();
            }
            
            // Try to get from meta tag
            const metaToken = document.querySelector(this.options.csrfTokenSelector);
            return metaToken ? metaToken.getAttribute('content') : null;
        }
        
        /**
         * Show loading state on form
         */
        showLoading() {
            const form = this.form;
            const options = this.options;
            
            // Add loading class to form
            form.classList.add(options.loadingClass);
            
            // Handle submit button loading state
            if (options.disableSubmitButton) {
                const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
                
                if (submitButton) {
                    submitButton.disabled = true;
                    
                    // Store original button text
                    if (options.useButtonLoadingState) {
                        submitButton._originalText = submitButton.innerHTML;
                        submitButton.innerHTML = options.loadingText;
                    }
                }
            }
        }
        
        /**
         * Clear loading state on form
         */
        clearLoading() {
            const form = this.form;
            const options = this.options;
            
            // Remove loading class
            form.classList.remove(options.loadingClass);
            
            // Handle submit button loading state
            if (options.disableSubmitButton) {
                const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
                
                if (submitButton) {
                    submitButton.disabled = false;
                    
                    // Restore original button text
                    if (options.useButtonLoadingState && submitButton._originalText) {
                        submitButton.innerHTML = submitButton._originalText;
                    }
                }
            }
        }
    }
    
    // Register with CarFuse
    CarFuse.forms.FormSubmitter = FormSubmitter;
    
    // Create factory function
    CarFuse.forms.createSubmitter = function(options) {
        return new FormSubmitter(options);
    };
    
    // Automatic initialization
    document.addEventListener('DOMContentLoaded', () => {
        // Auto-initialize for forms with data-ajax-form attribute
        document.querySelectorAll('form[data-ajax-form]').forEach(form => {
            const submitter = new FormSubmitter();
            submitter.init(form);
            
            // Store reference to submitter
            form._submitter = submitter;
        });
    });
})();
