/**
 * CarFuse Form Validation Framework
 * Provides a robust validation system for forms with consistent error handling
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
     * FormValidator class provides form validation functionality
     */
    class FormValidator {
        /**
         * Create a new form validator
         * @param {Object} options - Validator options
         */
        constructor(options = {}) {
            this.options = {
                validateOnBlur: true,
                validateOnInput: false,
                validateOnChange: true,
                validateOnSubmit: true,
                stopOnFirstError: false,
                errorClass: 'is-invalid',
                validClass: 'is-valid',
                errorMessageClass: 'invalid-feedback',
                errorMessageTag: 'div',
                errorFormClass: 'has-validation-errors',
                errorFieldWrapperClass: 'has-validation-error',
                fieldSelector: '[data-validate]',
                localization: 'pl',
                focusOnError: true,
                scrollToError: true,
                scrollOffset: -100,
                scrollBehavior: 'smooth',
                debounceTime: 350,
                ...options
            };
            
            // Store rules
            this.rules = {};
            this.customRules = {};
            
            // Store error messages
            this.messages = {};
            this.customMessages = {};
            
            // Store fields
            this.fields = new Map();
            this.fieldStatus = new Map();
            this.formElement = null;
            
            // Store debounced validation functions
            this.debouncedValidations = new Map();
            
            // Load default rules
            this.loadDefaultRules();
            this.loadDefaultMessages();
            
            // Create logger if CarFuse errorHandler exists
            this.logger = CarFuse.errorHandler?.createLogger 
                ? CarFuse.errorHandler.createLogger('FormValidator') 
                : console;
        }
        
        /**
         * Load default validation rules
         */
        loadDefaultRules() {
            // Import rules from separate module if available
            if (CarFuse.forms.rules) {
                this.rules = { ...CarFuse.forms.rules };
                return;
            }
            
            // Define built-in validation rules
            this.rules = {
                // Basic validations
                required: (value) => value !== undefined && value !== null && String(value).trim() !== '',
                email: (value) => !value || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
                url: (value) => !value || /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([/\w.-]*)*\/?$/.test(value),
                
                // Length validations
                min: (value, params) => !value || String(value).length >= Number(params),
                max: (value, params) => !value || String(value).length <= Number(params),
                length: (value, params) => !value || String(value).length === Number(params),
                between: (value, params) => {
                    if (!value) return true;
                    const [min, max] = params.split(',').map(Number);
                    return String(value).length >= min && String(value).length <= max;
                },
                
                // Type validations
                numeric: (value) => !value || /^-?\d*\.?\d+$/.test(value),
                integer: (value) => !value || /^-?\d+$/.test(value),
                alpha: (value) => !value || /^[a-zA-Z]+$/.test(value),
                alphanumeric: (value) => !value || /^[a-zA-Z0-9]+$/.test(value),
                
                // Range validations
                min_value: (value, params) => !value || Number(value) >= Number(params),
                max_value: (value, params) => !value || Number(value) <= Number(params),
                between_values: (value, params) => {
                    if (!value) return true;
                    const [min, max] = params.split(',').map(Number);
                    return Number(value) >= min && Number(value) <= max;
                },
                
                // Format validations
                regex: (value, params) => {
                    if (!value) return true;
                    try {
                        const flags = params.includes('/') ? params.split('/').pop() : '';
                        const pattern = params.includes('/')
                            ? params.substring(1, params.lastIndexOf('/'))
                            : params;
                        
                        return new RegExp(pattern, flags).test(value);
                    } catch (e) {
                        this.logger.error('Invalid regex pattern', { pattern: params, error: e });
                        return false;
                    }
                },
                
                // Special validations for PL
                pesel: (value) => !value || this.validatePESEL(value),
                nip: (value) => !value || this.validateNIP(value),
                postal_code: (value) => !value || /^\d{2}-\d{3}$/.test(value),
                phone: (value) => !value || /^(?:\+?48)?[0-9]{9}$/.test(value.replace(/\s+/g, '')),
                
                // Comparison validations
                same: (value, params, form) => {
                    if (!value) return true;
                    const field = form.querySelector(`[name="${params}"]`);
                    return field ? value === field.value : false;
                },
                different: (value, params, form) => {
                    if (!value) return true;
                    const field = form.querySelector(`[name="${params}"]`);
                    return field ? value !== field.value : true;
                },
                
                // File validations
                file: (value) => !value || value instanceof FileList || value instanceof File,
                image: (value) => {
                    if (!value || !(value instanceof FileList || value instanceof File)) return true;
                    const files = value instanceof FileList ? value : [value];
                    return Array.from(files).every(file => file.type.startsWith('image/'));
                },
                mimes: (value, params) => {
                    if (!value || !(value instanceof FileList || value instanceof File)) return true;
                    const allowedTypes = params.split(',');
                    const files = value instanceof FileList ? value : [value];
                    
                    return Array.from(files).every(file => {
                        const ext = file.name.split('.').pop().toLowerCase();
                        return allowedTypes.includes(ext);
                    });
                },
                max_size: (value, params) => {
                    if (!value || !(value instanceof FileList || value instanceof File)) return true;
                    const maxSize = Number(params) * 1024; // Convert to bytes
                    const files = value instanceof FileList ? value : [value];
                    
                    return Array.from(files).every(file => file.size <= maxSize);
                }
            };
        }
        
        /**
         * Load default validation messages
         */
        loadDefaultMessages() {
            // Import messages from separate module if available
            if (CarFuse.forms.messages) {
                this.messages = { ...CarFuse.forms.messages };
                return;
            }
            
            // Define built-in validation messages (polish)
            this.messages = {
                required: 'To pole jest wymagane.',
                email: 'Proszę podać prawidłowy adres email.',
                url: 'Proszę podać prawidłowy adres URL.',
                min: 'To pole musi zawierać co najmniej {0} znaków.',
                max: 'To pole nie może zawierać więcej niż {0} znaków.',
                length: 'To pole musi zawierać dokładnie {0} znaków.',
                between: 'To pole musi zawierać od {0} do {1} znaków.',
                numeric: 'To pole musi być liczbą.',
                integer: 'To pole musi być liczbą całkowitą.',
                alpha: 'To pole może zawierać tylko litery.',
                alphanumeric: 'To pole może zawierać tylko litery i cyfry.',
                min_value: 'Wartość musi być większa lub równa {0}.',
                max_value: 'Wartość musi być mniejsza lub równa {0}.',
                between_values: 'Wartość musi być pomiędzy {0} a {1}.',
                regex: 'Wartość nie jest w prawidłowym formacie.',
                pesel: 'Proszę podać prawidłowy numer PESEL.',
                nip: 'Proszę podać prawidłowy numer NIP.',
                postal_code: 'Proszę podać prawidłowy kod pocztowy (XX-XXX).',
                phone: 'Proszę podać prawidłowy numer telefonu.',
                same: 'To pole musi być zgodne z polem {0}.',
                different: 'To pole nie może być zgodne z polem {0}.',
                file: 'Proszę wybrać plik.',
                image: 'Wybrany plik musi być obrazem.',
                mimes: 'Plik musi być jednym z następujących typów: {0}.',
                max_size: 'Plik nie może być większy niż {0} KB.'
            };
        }
        
        /**
         * Register a custom validation rule
         * @param {string} name - Rule name
         * @param {Function} validator - Validation function
         * @param {string} message - Error message
         * @returns {FormValidator} This validator instance for chaining
         */
        registerRule(name, validator, message) {
            if (typeof validator !== 'function') {
                throw new Error(`Validator for rule "${name}" must be a function`);
            }
            
            this.customRules[name] = validator;
            
            if (message) {
                this.customMessages[name] = message;
            }
            
            return this;
        }
        
        /**
         * Set custom error messages
         * @param {Object} messages - Map of rule names to error messages
         * @returns {FormValidator} This validator instance for chaining
         */
        setMessages(messages) {
            this.customMessages = { ...this.customMessages, ...messages };
            return this;
        }
        
        /**
         * Get error message for a rule
         * @param {string} rule - Rule name
         * @param {string|Array} params - Rule parameters
         * @param {string} fieldName - Field name
         * @returns {string} Error message
         */
        getMessage(rule, params, fieldName) {
            // Get rule name without parameters
            const ruleName = rule.includes(':') ? rule.split(':')[0] : rule;
            
            // Try to get custom message for field and rule
            let message = this.customMessages[`${fieldName}.${ruleName}`] || 
                          this.customMessages[ruleName] || 
                          this.messages[ruleName] || 
                          `Validation error: ${ruleName}`;
            
            // Replace placeholders with parameters
            if (params) {
                const paramArray = Array.isArray(params) ? params : [params];
                message = message.replace(/{(\d+)}/g, (match, index) => {
                    return paramArray[index] !== undefined ? paramArray[index] : match;
                });
            }
            
            return message;
        }
        
        /**
         * Attach validator to a form
         * @param {HTMLFormElement} form - Form element to validate
         * @returns {FormValidator} This validator instance for chaining
         */
        attach(form) {
            if (!(form instanceof HTMLElement) || form.tagName !== 'FORM') {
                throw new Error('First argument must be a form element');
            }
            
            this.detach(); // Detach from previous form if any
            this.formElement = form;
            
            // Store original novalidate value and set to true
            this._originalNoValidate = form.noValidate;
            form.noValidate = true;
            
            // Find fields to validate
            this.discoverFields();
            
            // Bind events
            this.bindEvents();
            
            return this;
        }
        
        /**
         * Detach validator from current form
         * @returns {FormValidator} This validator instance for chaining
         */
        detach() {
            if (!this.formElement) return this;
            
            // Restore original noValidate value
            this.formElement.noValidate = this._originalNoValidate || false;
            
            // Unbind events
            this.unbindEvents();
            
            // Clear fields
            this.fields.clear();
            this.fieldStatus.clear();
            this.formElement = null;
            
            return this;
        }
        
        /**
         * Discover fields to validate in the form
         */
        discoverFields() {
            if (!this.formElement) return;
            
            // Find all fields with data-validate attribute
            const fields = this.formElement.querySelectorAll(this.options.fieldSelector);
            
            fields.forEach(field => {
                const name = field.name;
                if (!name) {
                    this.logger.warn('Field with data-validate must have a name attribute', { field });
                    return;
                }
                
                // Get validation rules from data attribute
                const rules = field.dataset.validate;
                if (!rules) return;
                
                // Store field reference
                this.fields.set(name, {
                    element: field,
                    rules: rules,
                    wrapper: this.findFieldWrapper(field),
                    messageElement: this.findOrCreateMessageElement(field)
                });
            });
        }
        
        /**
         * Find the wrapper element for a field
         * @param {HTMLElement} field - Form field
         * @returns {HTMLElement} Field wrapper element
         */
        findFieldWrapper(field) {
            // Look for parent with form-group class or data-field-wrapper
            let wrapper = field.closest('.form-group') || 
                        field.closest('[data-field-wrapper]') ||
                        field.parentElement;
                        
            return wrapper;
        }
        
        /**
         * Find or create error message element for a field
         * @param {HTMLElement} field - Form field
         * @returns {HTMLElement} Error message element
         */
        findOrCreateMessageElement(field) {
            const wrapper = this.findFieldWrapper(field);
            
            // Check if message element already exists
            let messageElement = wrapper.querySelector(`.${this.options.errorMessageClass}[data-field="${field.name}"]`);
            
            if (!messageElement) {
                // Create new message element
                messageElement = document.createElement(this.options.errorMessageTag);
                messageElement.className = this.options.errorMessageClass;
                messageElement.setAttribute('data-field', field.name);
                
                // Insert after field or at the end of wrapper
                if (field.nextElementSibling) {
                    wrapper.insertBefore(messageElement, field.nextElementSibling);
                } else {
                    wrapper.appendChild(messageElement);
                }
            }
            
            return messageElement;
        }
        
        /**
         * Bind validation events to form and fields
         */
        bindEvents() {
            if (!this.formElement) return;
            
            // Form submit event
            if (this.options.validateOnSubmit) {
                this.formElement.addEventListener('submit', this.handleSubmit.bind(this));
            }
            
            // Field events
            this.fields.forEach((fieldData, name) => {
                const field = fieldData.element;
                
                // Create debounced validation function
                const debouncedValidate = this.debounce(() => {
                    this.validateField(name);
                }, this.options.debounceTime);
                
                this.debouncedValidations.set(name, debouncedValidate);
                
                // Bind input event
                if (this.options.validateOnInput) {
                    field.addEventListener('input', debouncedValidate);
                }
                
                // Bind change event
                if (this.options.validateOnChange) {
                    field.addEventListener('change', () => this.validateField(name));
                }
                
                // Bind blur event
                if (this.options.validateOnBlur) {
                    field.addEventListener('blur', () => this.validateField(name));
                }
            });
        }
        
        /**
         * Unbind validation events from form and fields
         */
        unbindEvents() {
            if (!this.formElement) return;
            
            // Form submit event
            this.formElement.removeEventListener('submit', this.handleSubmit.bind(this));
            
            // Field events
            this.fields.forEach((fieldData, name) => {
                const field = fieldData.element;
                const debouncedValidate = this.debouncedValidations.get(name);
                
                if (debouncedValidate) {
                    field.removeEventListener('input', debouncedValidate);
                }
                
                field.removeEventListener('change', () => this.validateField(name));
                field.removeEventListener('blur', () => this.validateField(name));
            });
            
            // Clear debounced functions
            this.debouncedValidations.clear();
        }
        
        /**
         * Handle form submission
         * @param {Event} e - Submit event
         */
        handleSubmit(e) {
            // Validate all fields
            const isValid = this.validate();
            
            // Prevent submission if not valid
            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
                
                // Focus on first invalid field
                this.focusFirstInvalidField();
                
                // Emit form validation error event
                const errorEvent = new CustomEvent('form:validation-error', {
                    bubbles: true,
                    detail: { 
                        validator: this,
                        form: this.formElement,
                        errors: this.getErrors()
                    }
                });
                
                this.formElement.dispatchEvent(errorEvent);
            } else {
                // Emit form validation success event
                const successEvent = new CustomEvent('form:validation-success', {
                    bubbles: true,
                    detail: { 
                        validator: this,
                        form: this.formElement
                    }
                });
                
                this.formElement.dispatchEvent(successEvent);
            }
        }
        
        /**
         * Validate all form fields
         * @returns {boolean} True if all fields are valid
         */
        validate() {
            if (!this.formElement) return true;
            
            let isValid = true;
            
            // Remove form error class
            this.formElement.classList.remove(this.options.errorFormClass);
            
            // Validate each field
            this.fields.forEach((fieldData, name) => {
                const fieldIsValid = this.validateField(name);
                
                // Update global validity status
                isValid = isValid && fieldIsValid;
                
                // Stop on first error if configured
                if (!fieldIsValid && this.options.stopOnFirstError) {
                    return false;
                }
            });
            
            // Add form error class if not valid
            if (!isValid) {
                this.formElement.classList.add(this.options.errorFormClass);
            }
            
            return isValid;
        }
        
        /**
         * Validate a single field
         * @param {string} fieldName - Field name
         * @returns {boolean} True if field is valid
         */
        validateField(fieldName) {
            const fieldData = this.fields.get(fieldName);
            if (!fieldData) return true;
            
            const field = fieldData.element;
            const wrapper = fieldData.wrapper;
            const messageElement = fieldData.messageElement;
            
            // Get field value (handle different input types)
            let value = this.getFieldValue(field);
            
            // Get rules
            const rulesString = fieldData.rules;
            const rules = this.parseRules(rulesString);
            
            // Reset field status
            this.clearFieldError(fieldName);
            
            let isValid = true;
            let errorMessage = '';
            
            // Check each rule
            for (const [ruleName, params] of rules) {
                // Skip validation for empty values unless it's 'required'
                if ((value === '' || value === null || value === undefined) && ruleName !== 'required') {
                    continue;
                }
                
                // Get validation function
                const validationFn = this.customRules[ruleName] || this.rules[ruleName];
                
                if (!validationFn) {
                    this.logger.warn(`Unknown validation rule: ${ruleName}`);
                    continue;
                }
                
                // Run validation
                const isValidForRule = validationFn(value, params, this.formElement);
                
                // Handle validation result
                if (!isValidForRule) {
                    isValid = false;
                    errorMessage = this.getMessage(ruleName, params, fieldName);
                    break;
                }
            }
            
            // Update field status
            this.fieldStatus.set(fieldName, isValid);
            
            // Show error message if invalid
            if (!isValid) {
                this.showFieldError(fieldName, errorMessage);
                
                // Emit field validation error event
                const errorEvent = new CustomEvent('field:validation-error', {
                    bubbles: true,
                    detail: { 
                        validator: this,
                        field,
                        name: fieldName,
                        error: errorMessage
                    }
                });
                
                field.dispatchEvent(errorEvent);
            } else {
                // Show success state if configured
                if (this.options.validClass) {
                    field.classList.add(this.options.validClass);
                }
                
                // Emit field validation success event
                const successEvent = new CustomEvent('field:validation-success', {
                    bubbles: true,
                    detail: { 
                        validator: this,
                        field,
                        name: fieldName
                    }
                });
                
                field.dispatchEvent(successEvent);
            }
            
            return isValid;
        }
        
        /**
         * Get value from a form field, handling different input types
         * @param {HTMLElement} field - Form field
         * @returns {*} Field value
         */
        getFieldValue(field) {
            const type = field.type;
            
            if (type === 'checkbox') {
                return field.checked;
            } else if (type === 'radio') {
                const checkedField = this.formElement.querySelector(`input[name="${field.name}"]:checked`);
                return checkedField ? checkedField.value : '';
            } else if (type === 'file') {
                return field.files;
            } else if (field.tagName === 'SELECT' && field.multiple) {
                return Array.from(field.selectedOptions).map(option => option.value);
            } else {
                return field.value;
            }
        }
        
        /**
         * Parse validation rules string into array of rules with parameters
         * @param {string} rulesString - Rules string (e.g. "required|min:3|email")
         * @returns {Array} Array of [ruleName, parameters] pairs
         */
        parseRules(rulesString) {
            if (!rulesString) return [];
            
            return rulesString.split('|').map(rule => {
                const [ruleName, params] = rule.includes(':') 
                    ? [rule.split(':')[0], rule.split(':').slice(1).join(':')]
                    : [rule, null];
                
                return [ruleName, params];
            });
        }
        
        /**
         * Show field error
         * @param {string} fieldName - Field name
         * @param {string} message - Error message
         */
        showFieldError(fieldName, message) {
            const fieldData = this.fields.get(fieldName);
            if (!fieldData) return;
            
            const field = fieldData.element;
            const wrapper = fieldData.wrapper;
            const messageElement = fieldData.messageElement;
            
            // Add error class to field
            if (this.options.errorClass) {
                field.classList.add(this.options.errorClass);
            }
            
            // Remove valid class if present
            if (this.options.validClass) {
                field.classList.remove(this.options.validClass);
            }
            
            // Add error class to wrapper
            if (wrapper && this.options.errorFieldWrapperClass) {
                wrapper.classList.add(this.options.errorFieldWrapperClass);
            }
            
            // Show error message
            if (messageElement) {
                messageElement.textContent = message;
                messageElement.style.display = 'block';
            }
            
            // Set aria attributes for accessibility
            field.setAttribute('aria-invalid', 'true');
            if (messageElement) {
                const errorId = `error-${fieldName}`;
                messageElement.id = errorId;
                field.setAttribute('aria-describedby', errorId);
            }
        }
        
        /**
         * Clear field error
         * @param {string} fieldName - Field name
         */
        clearFieldError(fieldName) {
            const fieldData = this.fields.get(fieldName);
            if (!fieldData) return;
            
            const field = fieldData.element;
            const wrapper = fieldData.wrapper;
            const messageElement = fieldData.messageElement;
            
            // Remove error class from field
            if (this.options.errorClass) {
                field.classList.remove(this.options.errorClass);
            }
            
            // Remove error class from wrapper
            if (wrapper && this.options.errorFieldWrapperClass) {
                wrapper.classList.remove(this.options.errorFieldWrapperClass);
            }
            
            // Clear error message
            if (messageElement) {
                messageElement.textContent = '';
                messageElement.style.display = 'none';
            }
            
            // Remove aria attributes
            field.removeAttribute('aria-invalid');
            if (messageElement && messageElement.id) {
                field.removeAttribute('aria-describedby');
            }
        }
        
        /**
         * Focus on first invalid field
         */
        focusFirstInvalidField() {
            if (!this.options.focusOnError || !this.formElement) return;
            
            // Find first invalid field
            for (const [name, isValid] of this.fieldStatus.entries()) {
                if (!isValid) {
                    const fieldData = this.fields.get(name);
                    if (fieldData && fieldData.element) {
                        if (this.options.scrollToError) {
                            // Scroll to field
                            fieldData.element.scrollIntoView({
                                behavior: this.options.scrollBehavior,
                                block: 'center'
                            });
                            
                            // Apply scroll offset if needed
                            if (this.options.scrollOffset) {
                                window.scrollBy(0, this.options.scrollOffset);
                            }
                        }
                        
                        // Focus the field
                        fieldData.element.focus();
                        break;
                    }
                }
            }
        }
        
        /**
         * Get all form errors
         * @returns {Object} Map of field names to error messages
         */
        getErrors() {
            const errors = {};
            
            this.fields.forEach((fieldData, name) => {
                if (this.fieldStatus.has(name) && !this.fieldStatus.get(name)) {
                    const messageElement = fieldData.messageElement;
                    errors[name] = messageElement ? messageElement.textContent : 'Invalid';
                }
            });
            
            return errors;
        }
        
        /**
         * Validate PESEL number
         * @param {string} pesel - PESEL number
         * @returns {boolean} True if PESEL is valid
         */
        validatePESEL(pesel) {
            // Basic format check
            if (!pesel || pesel.length !== 11 || !/^\d{11}$/.test(pesel)) {
                return false;
            }
            
            // Check control digit
            const weights = [1, 3, 7, 9, 1, 3, 7, 9, 1, 3, 1];
            let sum = 0;
            
            for (let i = 0; i < 11; i++) {
                sum += parseInt(pesel.charAt(i), 10) * weights[i];
            }
            
            return sum % 10 === 0;
        }
        
        /**
         * Validate NIP number
         * @param {string} nip - NIP number
         * @returns {boolean} True if NIP is valid
         */
        validateNIP(nip) {
            // Remove spaces and dashes
            nip = nip.replace(/[\s-]/g, '');
            
            // Basic format check
            if (!nip || nip.length !== 10 || !/^\d{10}$/.test(nip)) {
                return false;
            }
            
            // Check control digit
            const weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
            let sum = 0;
            
            for (let i = 0; i < 9; i++) {
                sum += parseInt(nip.charAt(i), 10) * weights[i];
            }
            
            const checkDigit = sum % 11;
            
            // Check digit should never be 10, always 0-9
            if (checkDigit === 10) {
                return false;
            }
            
            return checkDigit === parseInt(nip.charAt(9), 10);
        }
        
        /**
         * Utility: Debounce function
         * @param {Function} func - Function to debounce
         * @param {number} wait - Debounce wait time in ms
         * @returns {Function} Debounced function
         */
        debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }
        
        /**
         * Validate form asynchronously with remote validation
         * @param {Function} asyncValidator - Async validation function
         * @returns {Promise<boolean>} Promise resolving to validation result
         */
        async validateAsync(asyncValidator) {
            // First perform standard validation
            const isValid = this.validate();
            
            if (!isValid) {
                return false;
            }
            
            // If standard validation passes, perform async validation
            if (typeof asyncValidator === 'function') {
                try {
                    const formData = new FormData(this.formElement);
                    const result = await asyncValidator(formData, this.formElement);
                    
                    // Handle validation results
                    if (result && typeof result === 'object') {
                        let hasErrors = false;
                        
                        // Process each field error
                        Object.entries(result).forEach(([field, error]) => {
                            if (error) {
                                this.showFieldError(field, error);
                                hasErrors = true;
                            }
                        });
                        
                        if (hasErrors) {
                            this.focusFirstInvalidField();
                            
                            // Add form error class
                            this.formElement.classList.add(this.options.errorFormClass);
                            
                            // Emit form validation error event
                            const errorEvent = new CustomEvent('form:validation-error', {
                                bubbles: true,
                                detail: { 
                                    validator: this,
                                    form: this.formElement,
                                    errors: result
                                }
                            });
                            
                            this.formElement.dispatchEvent(errorEvent);
                            
                            return false;
                        }
                    }
                    
                    // If we get here, validation passed
                    return true;
                } catch (error) {
                    this.logger.error('Async validation error', error);
                    return false;
                }
            }
            
            // No async validator, standard validation passed
            return true;
        }
        
        /**
         * Reset validation state
         */
        reset() {
            if (!this.formElement) return;
            
            // Clear all field errors
            this.fields.forEach((fieldData, name) => {
                this.clearFieldError(name);
            });
            
            // Clear field status
            this.fieldStatus.clear();
            
            // Remove form error class
            this.formElement.classList.remove(this.options.errorFormClass);
        }
    }
    
    // Register with CarFuse
    CarFuse.forms.Validator = FormValidator;
    
    // Create factory function
    CarFuse.forms.createValidator = function(options) {
        return new FormValidator(options);
    };
})();
