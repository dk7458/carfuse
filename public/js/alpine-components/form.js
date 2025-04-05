/**
 * CarFuse Alpine.js Form Component
 * Provides standardized form handling with validation and submission
 */

(function() {
    // Check if Alpine.js is available
    if (typeof Alpine === 'undefined') {
        console.error('Alpine.js is not loaded! Make sure to include Alpine.js before this script.');
        return;
    }
    
    // Check if CarFuseAlpine is available
    if (typeof window.CarFuseAlpine === 'undefined') {
        console.error('CarFuseAlpine is not available! Make sure to load it before this component.');
        return;
    }
    
    // Create form component
    window.CarFuseAlpine.createComponent('form', (options = {}) => {
        return {
            // Form state
            formData: {},
            errors: {},
            success: null,
            message: null,
            touched: {},
            dirty: false,
            submitted: false,
            // Configuration
            config: {
                endpoint: options.endpoint || '',
                method: options.method || 'POST',
                resetOnSuccess: options.resetOnSuccess !== false,
                validateOnBlur: options.validateOnBlur !== false,
                validateOnInput: options.validateOnInput || false,
                validationDebounce: options.validationDebounce || 300,
                scrollToErrors: options.scrollToErrors !== false,
                ...options
            },
            
            // Initialize component
            initialize() {
                // Set initial form data
                this.formData = this.config.initialData || {};
                
                // Set up validation rules
                this.rules = this.config.rules || {};
                
                // Create validation debounce function
                this.debouncedValidate = this.debounce(this.validate.bind(this), this.config.validationDebounce);
                
                // Initialize validation messages store
                this.messages = Alpine.store('validationMessages') || {
                    required: 'To pole jest wymagane.',
                    email: 'Proszę podać prawidłowy adres email.',
                    min: 'Wartość musi zawierać co najmniej {min} znaków.',
                    max: 'Wartość nie może przekraczać {max} znaków.'
                };
            },
            
            // Reset the form
            reset() {
                this.formData = this.config.initialData || {};
                this.errors = {};
                this.success = null;
                this.message = null;
                this.touched = {};
                this.dirty = false;
                this.submitted = false;
            },
            
            // Handle input change
            handleInput(field, event) {
                this.dirty = true;
                this.touched[field] = true;
                
                // Extract value from event or use directly
                const value = event && event.target ? event.target.value : event;
                
                // Update form data
                this.formData[field] = value;
                
                // Validate on input if enabled
                if (this.config.validateOnInput) {
                    this.debouncedValidate(field);
                }
            },
            
            // Handle blur event
            handleBlur(field) {
                this.touched[field] = true;
                
                // Validate on blur if enabled
                if (this.config.validateOnBlur) {
                    this.validate(field);
                }
            },
            
            // Validate entire form or a specific field
            validate(field = null) {
                // Clear existing errors for the field or all errors
                if (field) {
                    delete this.errors[field];
                } else {
                    this.errors = {};
                }
                
                // Determine which fields to validate
                const fieldsToValidate = field ? [field] : Object.keys(this.rules);
                
                // Validate each field
                fieldsToValidate.forEach(fieldName => {
                    const rules = this.rules[fieldName];
                    const value = this.formData[fieldName];
                    
                    if (!rules) return;
                    
                    // Process each validation rule
                    rules.split('|').forEach(ruleString => {
                        const [ruleName, ruleValue] = ruleString.split(':');
                        
                        if (this.validateRule(ruleName, value, ruleValue, fieldName)) {
                            // Rule passed, continue
                            return;
                        }
                        
                        // Rule failed, set error message
                        if (!this.errors[fieldName]) {
                            const message = this.getErrorMessage(ruleName, ruleValue);
                            this.errors[fieldName] = message;
                        }
                    });
                });
                
                return Object.keys(this.errors).length === 0;
            },
            
            // Validate a single rule
            validateRule(rule, value, params, field) {
                // Handle empty values for all except 'required' rule
                if ((value === '' || value === null || value === undefined) && rule !== 'required') {
                    return true;
                }
                
                switch(rule) {
                    case 'required':
                        return value !== '' && value !== null && value !== undefined;
                    case 'email':
                        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                    case 'min':
                        return value.length >= parseInt(params);
                    case 'max':
                        return value.length <= parseInt(params);
                    case 'minValue':
                        return parseFloat(value) >= parseFloat(params);
                    case 'maxValue':
                        return parseFloat(value) <= parseFloat(params);
                    case 'numeric':
                        return /^-?\d*\.?\d+$/.test(value);
                    case 'integer':
                        return /^-?\d+$/.test(value);
                    case 'confirmed':
                        const confirmField = params || `${field}_confirmation`;
                        return value === this.formData[confirmField];
                    default:
                        return true;
                }
            },
            
            // Get localized error message
            getErrorMessage(rule, params) {
                const message = this.messages[rule] || `Validation error: ${rule}`;
                
                return message.replace(/{(\w+)}/g, (match, key) => params || '');
            },
            
            // Handle form submission
            async submit() {
                this.submitted = true;
                
                // Validate all fields
                if (!this.validate()) {
                    this.success = false;
                    
                    // Scroll to first error if enabled
                    if (this.config.scrollToErrors) {
                        this.scrollToFirstError();
                    }
                    
                    return false;
                }
                
                // Use loading wrapper for submission
                return this.withLoading(async () => {
                    try {
                        const response = await fetch(this.config.endpoint, {
                            method: this.config.method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.getCsrfToken()
                            },
                            body: JSON.stringify(this.formData)
                        });
                        
                        const data = await response.json();
                        
                        if (!response.ok) {
                            throw new Error(data.message || 'Form submission failed');
                        }
                        
                        // Handle validation errors from server
                        if (data.errors) {
                            this.errors = data.errors;
                            this.success = false;
                            
                            // Scroll to first error if enabled
                            if (this.config.scrollToErrors) {
                                this.scrollToFirstError();
                            }
                            
                            return false;
                        }
                        
                        // Handle success
                        this.success = true;
                        this.message = data.message || 'Form submitted successfully';
                        
                        // Show success toast
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: {
                                title: 'Success',
                                message: this.message,
                                type: 'success'
                            }
                        }));
                        
                        // Reset form if configured
                        if (this.config.resetOnSuccess) {
                            this.reset();
                        }
                        
                        // Trigger success event
                        this.$dispatch('form-success', { data });
                        
                        return true;
                    } catch (error) {
                        this.success = false;
                        this.message = error.message || 'An unexpected error occurred';
                        
                        // Trigger error event
                        this.$dispatch('form-error', { error });
                        
                        return false;
                    }
                }, 'form submission');
            },
            
            // Get CSRF token from meta tag or AuthHelper
            getCsrfToken() {
                // Try to use Alpine magic if available
                if (typeof this.$csrf === 'function') {
                    return this.$csrf();
                }
                
                // Try to use AuthHelper
                if (window.AuthHelper && typeof window.AuthHelper.getCsrfToken === 'function') {
                    return window.AuthHelper.getCsrfToken();
                }
                
                // Fallback to meta tag
                const token = document.querySelector('meta[name="csrf-token"]');
                return token ? token.getAttribute('content') : '';
            },
            
            // Scroll to the first error in the form
            scrollToFirstError() {
                if (Object.keys(this.errors).length === 0) return;
                
                // Use setTimeout to ensure DOM is updated
                setTimeout(() => {
                    // Find first error element
                    const firstErrorField = document.querySelector(`[data-field="${Object.keys(this.errors)[0]}"]`);
                    
                    if (firstErrorField) {
                        firstErrorField.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                }, 100);
            },
            
            // Utility: Debounce function
            debounce(fn, delay) {
                let timeout;
                
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => fn.apply(this, args), delay);
                };
            }
        };
    }, { autoInit: true });
})();
