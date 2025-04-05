/**
 * CarFuse Validation Component
 * Provides advanced form validation features with Polish-specific rules
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Define component name
    const COMPONENT_NAME = 'validation';
    
    // Check if already initialized
    if (CarFuse[COMPONENT_NAME]) {
        console.warn(`CarFuse ${COMPONENT_NAME} component already initialized.`);
        return;
    }
    
    // Define the component
    const component = {
        // Configuration
        config: {
            defaultLocale: 'pl-PL',
            debug: false
        },
        
        // State
        state: {
            initialized: false,
            validators: {}
        },
        
        /**
         * Initialize Validation functionalities
         * @param {Object} options - Configuration options
         */
        init: function(options = {}) {
            // Apply custom options
            Object.assign(this.config, options);
            
            this.log('Initializing Validation component');
            this.defineValidators();
            this.setupFormListeners();
            this.state.initialized = true;
            this.log('Validation component initialized');
            
            return Promise.resolve();
        },
        
        /**
         * Log a message to the console if debug mode is enabled
         * @param {string} message - Message to log
         * @param {*} [data] - Optional data to include in log
         */
        log: function(message, data) {
            if (this.config.debug || CarFuse.config.debug) {
                console.log(`[CarFuse Validation] ${message}`, data || '');
            }
        },
        
        /**
         * Define custom validators
         */
        defineValidators: function() {
            this.log('Defining custom validators');
            
            this.state.validators = {
                'required': {
                    validate: (value) => !!value && String(value).trim() !== '',
                    message: 'To pole jest wymagane.'
                },
                'email': {
                    validate: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
                    message: 'Proszę podać prawidłowy adres email.'
                },
                'min': {
                    validate: (value, length) => String(value).length >= length,
                    message: 'Wartość musi zawierać co najmniej {length} znaków.'
                },
                'max': {
                    validate: (value, length) => String(value).length <= length,
                    message: 'Wartość nie może przekraczać {length} znaków.'
                },
                'numeric': {
                    validate: (value) => /^-?\d*\.?\d+$/.test(value),
                    message: 'Proszę podać wartość liczbową.'
                },
                'phone': {
                    validate: (value) => /^(?:\+48|48)?[0-9]{9}$/.test(String(value).replace(/\s+/g, '')),
                    message: 'Proszę podać prawidłowy numer telefonu.'
                },
                'postalCode': {
                    validate: (value) => /^\d{2}-\d{3}$/.test(value),
                    message: 'Proszę podać prawidłowy kod pocztowy.'
                },
                'pesel': {
                    validate: (value) => this.validatePESEL(value),
                    message: 'Podany numer PESEL jest nieprawidłowy.'
                },
                'nip': {
                    validate: (value) => this.validateNIP(value),
                    message: 'Podany numer NIP jest nieprawidłowy.'
                },
                'strongPassword': {
                    validate: (value) => /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?~\\/-]).{8,}$/.test(value),
                    message: 'Hasło musi zawierać co najmniej 8 znaków, jedną dużą literę, jedną małą literę, cyfrę i znak specjalny.'
                }
            };
        },
        
        /**
         * Setup form event listeners
         */
        setupFormListeners: function() {
            this.log('Setting up form event listeners');
            
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
                        // Form is valid, dispatch custom event
                        CarFuse.events.dispatchEvent('carfuse:form-submit', form, { form: form });
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
                const name = field.name;
                
                // Reset field error state
                field.classList.remove('border-red-500');
                const errorEl = document.getElementById(`${name}-error`);
                if (errorEl) errorEl.textContent = '';
                
                // Validate each field
                for (const rule of rules) {
                    const [ruleName, ruleArgs] = rule.split(':');
                    const validator = this.state.validators[ruleName];
                    
                    if (validator) {
                        const isValidField = validator.validate(value, ruleArgs);
                        if (!isValidField) {
                            isValid = false;
                            const message = CarFuse.i18n ? 
                                CarFuse.i18n.translate(validator.message, { length: ruleArgs }) : 
                                validator.message.replace('{length}', ruleArgs);
                            
                            field.classList.add('border-red-500');
                            if (errorEl) {
                                errorEl.textContent = message;
                            } else {
                                const newErrorEl = document.createElement('p');
                                newErrorEl.id = `${name}-error`;
                                newErrorEl.className = 'text-red-500 text-sm mt-1';
                                newErrorEl.textContent = message;
                                field.parentNode.appendChild(newErrorEl);
                            }
                            break; // Stop on first error
                        }
                    } else {
                        console.warn(`Validator ${ruleName} not found`);
                    }
                }
            });
            
            return isValid;
        },
        
        /**
         * Validate a Polish PESEL number
         * @param {string} pesel - PESEL number to validate
         * @returns {boolean} True if PESEL is valid
         */
        validatePESEL: function(pesel) {
            if (!pesel || pesel.length !== 11 || !/^\d+$/.test(pesel)) {
                return false;
            }
            
            const weights = [1, 3, 7, 9, 1, 3, 7, 9, 1, 3];
            let sum = 0;
            for (let i = 0; i < weights.length; i++) {
                sum += weights[i] * parseInt(pesel[i]);
            }
            
            const controlDigit = (10 - (sum % 10)) % 10;
            return controlDigit === parseInt(pesel[10]);
        },
        
        /**
         * Validate a Polish NIP number
         * @param {string} nip - NIP number to validate
         * @returns {boolean} True if NIP is valid
         */
        validateNIP: function(nip) {
            if (!nip || nip.length !== 10 || !/^\d+$/.test(nip)) {
                return false;
            }
            
            const weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
            let sum = 0;
            for (let i = 0; i < weights.length; i++) {
                sum += weights[i] * parseInt(nip[i]);
            }
            
            const controlDigit = sum % 11;
            return controlDigit === parseInt(nip[9]);
        }
    };
    
    // Register the component
    CarFuse[COMPONENT_NAME] = component;
    
    // Register with CarFuse if available
    if (CarFuse.registerComponent) {
        CarFuse.registerComponent(COMPONENT_NAME, component);
    }
})();
