/**
 * CarFuse Form Validation Component
 * Provides standardized form validation with error handling
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Create form validation component
    CarFuse.createComponent('formValidation', {
        // Component dependencies
        dependencies: ['core', 'events', 'errorHandler'],
        
        // Component properties
        props: {
            validateOnBlur: CarFuse.utils.PropValidator.types.boolean({ default: true }),
            validateOnInput: CarFuse.utils.PropValidator.types.boolean({ default: false }),
            validateOnSubmit: CarFuse.utils.PropValidator.types.boolean({ default: true }),
            showInlineErrors: CarFuse.utils.PropValidator.types.boolean({ default: true }),
            debounceTime: CarFuse.utils.PropValidator.types.number({ default: 300 })
        },
        
        // Component state
        state: {
            validators: {},
            errorMessages: {},
            debounceTimers: {}
        },
        
        // Lifecycle: Prepare
        prepare() {
            // Bind methods
            this.handleInput = this.handleInput.bind(this);
            this.handleBlur = this.handleBlur.bind(this);
            this.handleSubmit = this.handleSubmit.bind(this);
            
            // Create logger
            this.logger = CarFuse.errorHandler.createLogger('FormValidation');
            
            // Set up validators
            this.setupValidators();
            
            // Set up error messages
            this.setupErrorMessages();
        },
        
        // Lifecycle: Initialize
        initialize() {
            // All setup is done in prepare
            return Promise.resolve();
        },
        
        // Lifecycle: Mount elements
        mountElements(elements) {
            elements.forEach(form => {
                // Parse options from data attribute
                let options = {};
                try {
                    if (form.dataset.options) {
                        options = JSON.parse(form.dataset.options);
                    }
                } catch (e) {
                    this.logError('Invalid options JSON', e);
                }
                
                // Store validated props on the form element
                form.formValidationProps = this.setProps({
                    ...options
                });
                
                // Set up form validation
                this.setupFormValidation(form);
            });
            
            return Promise.resolve();
        },
        
        // Set up validators
        setupValidators() {
            this.state.validators = {
                required: (value) => {
                    return value !== null && value !== undefined && String(value).trim() !== '';
                },
                email: (value) => {
                    return !value || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                },
                min: (value, param) => {
                    return !value || String(value).length >= parseInt(param, 10);
                },
                max: (value, param) => {
                    return !value || String(value).length <= parseInt(param, 10);
                },
                minValue: (value, param) => {
                    return !value || parseFloat(value) >= parseFloat(param);
                },
                maxValue: (value, param) => {
                    return !value || parseFloat(value) <= parseFloat(param);
                },
                between: (value, param) => {
                    if (!value) return true;
                    const [min, max] = param.split(',').map(Number);
                    const numValue = parseFloat(value);
                    return numValue >= min && numValue <= max;
                },
                numeric: (value) => {
                    return !value || /^-?\d*\.?\d+$/.test(value);
                },
                integer: (value) => {
                    return !value || /^-?\d+$/.test(value);
                },
                alphanumeric: (value) => {
                    return !value || /^[a-zA-Z0-9]+$/.test(value);
                },
                phone: (value) => {
                    return !value || /^(?:\+?[0-9]{1,3})?[-. (]?[0-9]{3}[-. )]?[0-9]{3}[-. ]?[0-9]{3,4}$/.test(value);
                },
                postalCode: (value) => {
                    return !value || /^[0-9]{2}-[0-9]{3}$/.test(value);
                },
                regex: (value, param) => {
                    if (!value) return true;
                    try {
                        const regex = new RegExp(param);
                        return regex.test(value);
                    } catch (e) {
                        this.logger.error('Invalid regex pattern', e);
                        return false;
                    }
                },
                confirmed: (value, param, form) => {
                    if (!value) return true;
                    
                    const confirmField = form.querySelector(`[name="${param}"]`);
                    return confirmField && value === confirmField.value;
                },
                date: (value) => {
                    if (!value) return true;
                    const date = new Date(value);
                    return !isNaN(date.getTime());
                },
                futureDate: (value) => {
                    if (!value) return true;
                    const date = new Date(value);
                    return !isNaN(date.getTime()) && date > new Date();
                },
                pastDate: (value) => {
                    if (!value) return true;
                    const date = new Date(value);
                    return !isNaN(date.getTime()) && date < new Date();
                },
                url: (value) => {
                    if (!value) return true;
                    try {
                        new URL(value);
                        return true;
                    } catch (e) {
                        return false;
                    }
                },
                creditCard: (value) => {
                    if (!value) return true;
                    
                    // Remove spaces and dashes
                    const cardNumber = value.replace(/[\s-]/g, '');
                    
                    // Check if all characters are digits
                    if (!/^\d+$/.test(cardNumber)) return false;
                    
                    // Luhn algorithm (checksum)
                    let sum = 0;
                    let double = false;
                    
                    for (let i = cardNumber.length - 1; i >= 0; i--) {
                        let digit = parseInt(cardNumber.charAt(i), 10);
                        
                        if (double) {
                            digit *= 2;
                            if (digit > 9) digit -= 9;
                        }
                        
                        sum += digit;
                        double = !double;
                    }
                    
                    return sum % 10 === 0;
                }
            };
        },
        
        // Set up error messages
        setupErrorMessages() {
            this.state.errorMessages = {
                required: 'To pole jest wymagane.',
                email: 'Proszę podać prawidłowy adres e-mail.',
                min: 'Wartość musi zawierać minimum {0} znaków.',
                max: 'Wartość nie może przekraczać {0} znaków.',
                minValue: 'Wartość musi być większa lub równa {0}.',
                maxValue: 'Wartość musi być mniejsza lub równa {0}.',
                between: 'Wartość musi być pomiędzy {0} a {1}.',
                numeric: 'Proszę podać wartość liczbową.',
                integer: 'Proszę podać liczbę całkowitą.',
                alphanumeric: 'Dozwolone są tylko litery i cyfry.',
                phone: 'Proszę podać prawidłowy numer telefonu.',
                postalCode: 'Proszę podać prawidłowy kod pocztowy.',
                regex: 'Wartość ma nieprawidłowy format.',
                confirmed: 'Wartości nie są zgodne.',
                date: 'Proszę podać prawidłową datę.',
                futureDate: 'Data musi być w przyszłości.',
                pastDate: 'Data musi być w przeszłości.',
                url: 'Proszę podać prawidłowy adres URL.',
                creditCard: 'Proszę podać prawidłowy numer karty kredytowej.'
            };
        },
        
        // Set up form validation
        setupFormValidation(form) {
            const props = form.formValidationProps;
            
            // Add submit handler
            if (props.validateOnSubmit) {
                form.addEventListener('submit', this.handleSubmit);
            }
            
            // Find all validation fields
            const fields = Array.from(form.querySelectorAll('[data-validate]'));
            
            fields.forEach(field => {
                // Add input handler
                if (props.validateOnInput) {
                    field.addEventListener('input', e => this.handleInput(e, form));
                }
                
                // Add blur handler
                if (props.validateOnBlur) {
                    field.addEventListener('blur', e => this.handleBlur(e, form));
                }
            });
        },
        
        // Handle input event
        handleInput(event, form) {
            const field = event.target;
            const name = field.name;
            const props = form.formValidationProps;
            
            // Clear existing timer
            if (this.state.debounceTimers[name]) {
                clearTimeout(this.state.debounceTimers[name]);
            }
            
            // Debounce validation
            this.state.debounceTimers[name] = setTimeout(() => {
                this.validateField(field, form);
                delete this.state.debounceTimers[name];
            }, props.debounceTime);
        },
        
        // Handle blur event
        handleBlur(event, form) {
            const field = event.target;
            
            // Clear existing timer
            const name = field.name;
            if (this.state.debounceTimers[name]) {
                clearTimeout(this.state.debounceTimers[name]);
                delete this.state.debounceTimers[name];
            }
            
            // Validate immediately
            this.validateField(field, form);
        },
        
        // Handle submit event
        handleSubmit(event) {
            const form = event.target;
            const isValid = this.validateForm(form);
            
            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
                
                // Emit form validation error event
                const errorEvent = new CustomEvent('form:validation-error', {
                    bubbles: true,
                    detail: { 
                        form,
                        errors: this.getFormErrors(form)
                    }
                });
                
                form.dispatchEvent(errorEvent);
                
                // Scroll to first error
                this.scrollToFirstError(form);
                
                // If we have the errorHandler, process this as a validation error
                if (CarFuse.errorHandler) {
                    CarFuse.errorHandler.processError({
                        type: CarFuse.errorHandler.ErrorTypes.DATA.VALIDATION,
                        message: 'Form validation failed',
                        source: 'form',
                        silent: true, // Don't show toast for validation errors
                        context: {
                            formId: form.id,
                            formAction: form.action,
                            errors: this.getFormErrors(form)
                        }
                    });
                }
            }
        },
        
        // Validate a single field
        validateField(field, form) {
            // Skip disabled fields
            if (field.disabled) return true;
            
            const name = field.name;
            const value = field.value;
            const validations = field.dataset.validate?.split('|') || [];
            
            // Clear existing error
            this.clearFieldError(field, form);
            
            let isValid = true;
            let errorMessage = '';
            
            // Process each validation rule
            for (const validation of validations) {
                // Parse rule and parameter
                const [rule, parameter] = validation.includes(':') 
                    ? validation.split(':') 
                    : [validation, null];
                
                // Skip validation for empty fields unless it's 'required'
                if ((!value || value.trim() === '') && rule !== 'required') {
                    continue;
                }
                
                // Check if validator exists
                if (!this.state.validators[rule]) {
                    this.logger.warn(`Unknown validator: ${rule}`);
                    continue;
                }
                
                // Run the validator
                const validatorFn = this.state.validators[rule];
                const passed = validatorFn(value, parameter, form);
                
                if (!passed) {
                    isValid = false;
                    
                    // Get appropriate error message
                    errorMessage = this.getErrorMessage(rule, parameter);
                    break;
                }
            }
            
            // Handle validation result
            if (!isValid) {
                this.showFieldError(field, errorMessage, form);
                
                // Emit field validation error event
                const errorEvent = new CustomEvent('field:validation-error', {
                    bubbles: true,
                    detail: { 
                        field, 
                        name,
                        value,
                        error: errorMessage
                    }
                });
                
                field.dispatchEvent(errorEvent);
            }
            
            return isValid;
        },
        
        // Validate entire form
        validateForm(form) {
            const fields = Array.from(form.querySelectorAll('[data-validate]'));
            let isValid = true;
            
            fields.forEach(field => {
                // Validate each field and update overall validity
                if (!this.validateField(field, form)) {
                    isValid = false;
                }
            });
            
            return isValid;
        },
        
        // Show field error
        showFieldError(field, message, form) {
            const props = form.formValidationProps;
            if (!props.showInlineErrors) return;
            
            // Add error class to field
            field.classList.add('error', 'border-red-500');
            
            // Add aria attributes
            field.setAttribute('aria-invalid', 'true');
            
            // Create or update error message element
            let errorElement = this.getErrorElement(field);
            
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.className = 'validation-error text-red-500 text-sm mt-1';
                errorElement.setAttribute('data-validation-error', field.name);
                
                // Insert after field or field's parent if it's inside a form group
                const formGroup = field.closest('.form-group') || field.closest('.input-group');
                if (formGroup) {
                    formGroup.appendChild(errorElement);
                } else {
                    field.insertAdjacentElement('afterend', errorElement);
                }
            }
            
            // Set error message
            errorElement.textContent = message;
            
            // Connect with ARIA
            const id = `error-${field.name}-${Date.now()}`;
            errorElement.id = id;
            field.setAttribute('aria-describedby', id);
        },
        
        // Clear field error
        clearFieldError(field, form) {
            // Remove error classes
            field.classList.remove('error', 'border-red-500');
            field.removeAttribute('aria-invalid');
            
            // Remove error element if exists
            const errorElement = this.getErrorElement(field);
            if (errorElement) {
                // Remove the connection with aria
                field.removeAttribute('aria-describedby');
                
                // Remove the error element
                errorElement.parentNode.removeChild(errorElement);
            }
        },
        
        // Get error element for a field
        getErrorElement(field) {
            return document.querySelector(`[data-validation-error="${field.name}"]`);
        },
        
        // Get form errors
        getFormErrors(form) {
            const errors = {};
            const errorElements = form.querySelectorAll('[data-validation-error]');
            
            errorElements.forEach(el => {
                const fieldName = el.getAttribute('data-validation-error');
                errors[fieldName] = el.textContent;
            });
            
            return errors;
        },
        
        // Get error message
        getErrorMessage(rule, parameter) {
            let message = this.state.errorMessages[rule] || `Validation error: ${rule}`;
            
            // Replace parameters in the message
            if (parameter) {
                const params = parameter.split(',');
                message = message.replace(/\{(\d+)\}/g, (match, index) => {
                    return params[parseInt(index, 10)] || '';
                });
            }
            
            return message;
        },
        
        // Scroll to first error
        scrollToFirstError(form) {
            const firstError = form.querySelector('.error, .border-red-500');
            if (!firstError) return;
            
            // Scroll into view with smooth behavior
            firstError.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
            
            // Focus the first error field
            firstError.focus();
        },
        
        // Register custom validator
        registerValidator(name, validatorFn, errorMessage) {
            if (typeof validatorFn !== 'function') {
                throw new Error('Validator must be a function');
            }
            
            this.state.validators[name] = validatorFn;
            
            if (errorMessage) {
                this.state.errorMessages[name] = errorMessage;
            }
        },
        
        // Lifecycle: Destroy
        destroyComponent() {
            // Clean up any event listeners
            document.querySelectorAll('[data-component="formValidation"]').forEach(form => {
                form.removeEventListener('submit', this.handleSubmit);
                
                // Remove field event listeners
                form.querySelectorAll('[data-validate]').forEach(field => {
                    field.removeEventListener('input', this.handleInput);
                    field.removeEventListener('blur', this.handleBlur);
                });
            });
            
            // Clear any pending debounce timers
            Object.values(this.state.debounceTimers).forEach(timer => {
                clearTimeout(timer);
            });
            
            this.state.debounceTimers = {};
        }
    });
})();
