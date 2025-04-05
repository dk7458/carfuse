/**
 * CarFuse Async Form Validator
 * Provides asynchronous validation capabilities for form validation
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
     * AsyncValidator class handles asynchronous validation tasks
     */
    class AsyncValidator {
        /**
         * Create a new async validator
         * @param {Object} options - Validator options
         */
        constructor(options = {}) {
            this.options = {
                endpoint: null, // Remote validation endpoint
                method: 'POST', // HTTP method for remote validation
                headers: {}, // Additional headers
                debounceTime: 300, // Debounce time in ms
                includeFormData: true, // Whether to include all form data in requests
                fieldParam: 'field', // Parameter name for field
                valueParam: 'value', // Parameter name for value
                csrfTokenHeader: 'X-CSRF-TOKEN', // CSRF token header name
                ...options
            };
            
            this.pending = new Map(); // Map of pending validation requests
            this.debounceTimers = new Map(); // Map of debounce timers
            
            // Create logger if CarFuse errorHandler exists
            this.logger = CarFuse.errorHandler?.createLogger 
                ? CarFuse.errorHandler.createLogger('AsyncValidator') 
                : console;
        }
        
        /**
         * Validate a field asynchronously
         * @param {string} field - Field name
         * @param {*} value - Field value
         * @param {HTMLFormElement} form - Form element
         * @param {Object} options - Additional options
         * @returns {Promise} Resolves with validation result
         */
        validate(field, value, form, options = {}) {
            // Cancel any pending validation for this field
            this.cancel(field);
            
            // Get CSRF token
            const csrfToken = this.getCsrfToken();
            
            // Prepare validation options
            const validateOptions = {
                ...this.options,
                ...options
            };
            
            // Create a promise for this validation
            const validationPromise = new Promise((resolve, reject) => {
                // Create debounce function
                const performValidation = () => {
                    // Prepare request data
                    let requestData;
                    
                    if (validateOptions.includeFormData && form instanceof HTMLFormElement) {
                        requestData = new FormData(form);
                    } else {
                        requestData = new FormData();
                        requestData.append(validateOptions.fieldParam, field);
                        requestData.append(validateOptions.valueParam, value);
                    }
                    
                    // Prepare headers
                    const headers = {
                        'X-Requested-With': 'XMLHttpRequest',
                        ...validateOptions.headers
                    };
                    
                    // Add CSRF token if available
                    if (csrfToken) {
                        headers[validateOptions.csrfTokenHeader] = csrfToken;
                    }
                    
                    // Make the request
                    const controller = new AbortController();
                    const { signal } = controller;
                    
                    // Store the controller
                    this.pending.set(field, controller);
                    
                    fetch(validateOptions.endpoint, {
                        method: validateOptions.method,
                        headers,
                        body: requestData,
                        signal
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Validation request failed: ${response.status} ${response.statusText}`);
                        }
                        return response.json();
                    })
                    .then(result => {
                        // Remove from pending
                        this.pending.delete(field);
                        
                        // Resolve with validation result
                        resolve(result);
                    })
                    .catch(error => {
                        // Ignore aborted requests
                        if (error.name === 'AbortError') {
                            resolve({ valid: true }); // Assume valid if aborted
                            return;
                        }
                        
                        // Remove from pending
                        this.pending.delete(field);
                        
                        // Log error
                        this.logger.error('Async validation error', error);
                        
                        // Reject with error
                        reject(error);
                    });
                };
                
                // Debounce the validation request
                if (validateOptions.debounceTime > 0) {
                    // Clear any existing timer
                    if (this.debounceTimers.has(field)) {
                        clearTimeout(this.debounceTimers.get(field));
                    }
                    
                    // Set new timer
                    const timerId = setTimeout(() => {
                        this.debounceTimers.delete(field);
                        performValidation();
                    }, validateOptions.debounceTime);
                    
                    this.debounceTimers.set(field, timerId);
                } else {
                    // Execute immediately
                    performValidation();
                }
            });
            
            return validationPromise;
        }
        
        /**
         * Cancel pending validation for a field
         * @param {string} field - Field name
         */
        cancel(field) {
            // Cancel debounce timer if exists
            if (this.debounceTimers.has(field)) {
                clearTimeout(this.debounceTimers.get(field));
                this.debounceTimers.delete(field);
            }
            
            // Abort pending request if exists
            if (this.pending.has(field)) {
                this.pending.get(field).abort();
                this.pending.delete(field);
            }
        }
        
        /**
         * Cancel all pending validations
         */
        cancelAll() {
            // Cancel all debounce timers
            for (const timerId of this.debounceTimers.values()) {
                clearTimeout(timerId);
            }
            this.debounceTimers.clear();
            
            // Abort all pending requests
            for (const controller of this.pending.values()) {
                controller.abort();
            }
            this.pending.clear();
        }
        
        /**
         * Check if validation is pending for a field
         * @param {string} field - Field name
         * @returns {boolean} True if validation is pending
         */
        isPending(field) {
            return this.debounceTimers.has(field) || this.pending.has(field);
        }
        
        /**
         * Get CSRF token from meta tag
         * @returns {string|null} CSRF token or null if not found
         */
        getCsrfToken() {
            // Try to use AuthHelper
            if (window.AuthHelper && typeof window.AuthHelper.getCsrfToken === 'function') {
                return window.AuthHelper.getCsrfToken();
            }
            
            // Fallback to meta tag
            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            return tokenMeta ? tokenMeta.getAttribute('content') : null;
        }
        
        /**
         * Validate entire form asynchronously
         * @param {HTMLFormElement} form - Form element
         * @param {Object} options - Validation options
         * @returns {Promise} Resolves with validation result
         */
        validateForm(form, options = {}) {
            if (!(form instanceof HTMLFormElement)) {
                throw new Error('First argument must be a form element');
            }
            
            // Cancel all pending validations
            this.cancelAll();
            
            // Get CSRF token
            const csrfToken = this.getCsrfToken();
            
            // Prepare validation options
            const validateOptions = {
                ...this.options,
                ...options
            };
            
            // Prepare request data
            const requestData = new FormData(form);
            
            // Prepare headers
            const headers = {
                'X-Requested-With': 'XMLHttpRequest',
                ...validateOptions.headers
            };
            
            // Add CSRF token if available
            if (csrfToken) {
                headers[validateOptions.csrfTokenHeader] = csrfToken;
            }
            
            // Make the request
            return fetch(validateOptions.endpoint, {
                method: validateOptions.method,
                headers,
                body: requestData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Validation request failed: ${response.status} ${response.statusText}`);
                }
                return response.json();
            })
            .catch(error => {
                // Log error
                this.logger.error('Async form validation error', error);
                throw error;
            });
        }
    }
    
    // Register with CarFuse
    CarFuse.forms.AsyncValidator = AsyncValidator;
    
    // Create factory function
    CarFuse.forms.createAsyncValidator = function(options) {
        return new AsyncValidator(options);
    };
})();
