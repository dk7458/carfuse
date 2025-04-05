/**
 * CarFuse Forms - Validation Module
 * Provides form validation functionality
 */

(function() {
  // Namespaces
  const CarFuse = window.CarFuse || {};
  if (!CarFuse.forms) CarFuse.forms = {};
  
  // Validation module
  const validation = {
    // Configuration
    config: {
      validateOnInput: false,
      validateOnBlur: true,
      validateOnSubmit: true,
      errorClass: 'error',
      errorTextClass: 'text-red-500 text-sm mt-1',
      errorBorderClass: 'border-red-500',
      successClass: 'success',
      asyncTimeout: 3000, // 3 seconds timeout for async validations
      debounceTime: 300
    },
    
    // State
    rules: {}, // Will be populated when rules modules are loaded
    
    /**
     * Initialize validation module
     * @param {Object} options Configuration options
     * @returns {Promise} Promise that resolves when initialization is complete
     */
    init(options = {}) {
      // Apply custom options
      Object.assign(this.config, options);
      
      // Import rule modules
      this._importRules();
      
      return Promise.resolve(this);
    },
    
    /**
     * Import validation rules from submodules
     * @private
     */
    _importRules() {
      // Core rules (bundled)
      this.rules = {
        // Text validation
        required: {
          validate: value => value !== null && value !== undefined && String(value).trim() !== '',
          message: 'To pole jest wymagane.'
        },
        email: {
          validate: value => !value || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
          message: 'Proszę podać prawidłowy adres email.'
        },
        min: {
          validate: (value, param) => !value || String(value).length >= parseInt(param, 10),
          message: 'Wartość musi zawierać co najmniej {0} znaków.'
        },
        max: {
          validate: (value, param) => !value || String(value).length <= parseInt(param, 10),
          message: 'Wartość nie może przekraczać {0} znaków.'
        },
        
        // Numeric validation
        numeric: {
          validate: value => !value || /^-?\d*\.?\d+$/.test(value),
          message: 'Proszę podać wartość liczbową.'
        },
        integer: {
          validate: value => !value || /^-?\d+$/.test(value),
          message: 'Proszę podać liczbę całkowitą.'
        },
        minValue: {
          validate: (value, param) => !value || parseFloat(value) >= parseFloat(param),
          message: 'Wartość musi być większa lub równa {0}.'
        },
        maxValue: {
          validate: (value, param) => !value || parseFloat(value) <= parseFloat(param),
          message: 'Wartość musi być mniejsza lub równa {0}.'
        },
        between: {
          validate: (value, param) => {
            if (!value) return true;
            const [min, max] = param.split(',').map(Number);
            const numValue = parseFloat(value);
            return numValue >= min && numValue <= max;
          },
          message: 'Wartość musi być pomiędzy {0} a {1}.'
        },
        
        // Format validation
        pattern: {
          validate: (value, param) => {
            if (!value) return true;
            try {
              const regex = new RegExp(param);
              return regex.test(value);
            } catch (e) {
              console.error('Invalid regex pattern:', e);
              return false;
            }
          },
          message: 'Wartość ma nieprawidłowy format.'
        },
        url: {
          validate: value => {
            if (!value) return true;
            try {
              new URL(value);
              return true;
            } catch (e) {
              return false;
            }
          },
          message: 'Proszę podać prawidłowy adres URL.'
        },
        
        // Polish specific
        phone: {
          validate: value => !value || /^(?:\+48|48)?[0-9]{9}$/.test(String(value).replace(/\s+/g, '')),
          message: 'Proszę podać prawidłowy numer telefonu.'
        },
        postalCode: {
          validate: value => !value || /^\d{2}-\d{3}$/.test(value),
          message: 'Proszę podać prawidłowy kod pocztowy (XX-XXX).'
        },
        pesel: {
          validate: value => {
            if (!value) return true;
            if (value.length !== 11 || !/^\d+$/.test(value)) return false;
            
            const weights = [1, 3, 7, 9, 1, 3, 7, 9, 1, 3];
            let sum = 0;
            
            for (let i = 0; i < weights.length; i++) {
              sum += weights[i] * parseInt(value[i]);
            }
            
            const controlDigit = (10 - (sum % 10)) % 10;
            return controlDigit === parseInt(value[10]);
          },
          message: 'Podany numer PESEL jest nieprawidłowy.'
        },
        nip: {
          validate: value => {
            if (!value) return true;
            if (value.length !== 10 || !/^\d+$/.test(value)) return false;
            
            const weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
            let sum = 0;
            
            for (let i = 0; i < weights.length; i++) {
              sum += weights[i] * parseInt(value[i]);
            }
            
            const controlDigit = sum % 11;
            return controlDigit === parseInt(value[9]);
          },
          message: 'Podany numer NIP jest nieprawidłowy.'
        },
        
        // Advanced rules
        confirmed: {
          validate: (value, param, form) => {
            if (!value) return true;
            const confirmField = form.querySelector(`[name="${param}"]`);
            return confirmField && value === confirmField.value;
          },
          message: 'Wartości nie są zgodne.'
        },
        different: {
          validate: (value, param, form) => {
            if (!value) return true;
            const otherField = form.querySelector(`[name="${param}"]`);
            return otherField && value !== otherField.value;
          },
          message: 'Wartości muszą być różne.'
        },
        date: {
          validate: value => {
            if (!value) return true;
            const date = new Date(value);
            return !isNaN(date.getTime());
          },
          message: 'Proszę podać prawidłową datę.'
        },
        
        // Async validation (example)
        uniqueEmail: {
          async: true,
          validate: (value, param, form, options) => {
            if (!value) return Promise.resolve(true);
            
            const endpoint = options?.endpoint || '/api/validate/email';
            
            return fetch(`${endpoint}?email=${encodeURIComponent(value)}`)
              .then(response => response.json())
              .then(data => data.valid)
              .catch(err => {
                console.error('Async validation error:', err);
                return false;
              });
          },
          message: 'Ten adres email jest już zajęty.'
        }
      };
      
      // Try to load advanced rules if available
      if (CarFuse.forms.validation && CarFuse.forms.validation.rules) {
        Object.assign(this.rules, CarFuse.forms.validation.rules);
      }
    },
    
    /**
     * Create a validator for a specific form
     * @param {HTMLElement} form Form element
     * @param {Object} options Validation options
     * @returns {Object} Validator instance
     */
    createValidator(form, options = {}) {
      // Local options (combine global config with form-specific options)
      const config = { ...this.config, ...options };
      
      // Debounce function for input validation
      const debounce = (fn, delay) => {
        let timeout;
        return function(...args) {
          clearTimeout(timeout);
          timeout = setTimeout(() => fn.apply(this, args), delay);
        };
      };
      
      // Validator methods
      const validator = {
        form,
        config,
        errors: {},
        pendingValidations: new Map(),
        
        /**
         * Initialize the validator
         */
        init() {
          this._setupEventListeners();
          return this;
        },
        
        /**
         * Set up event listeners for validation
         * @private
         */
        _setupEventListeners() {
          const form = this.form;
          
          if (this.config.validateOnInput) {
            form.addEventListener('input', e => {
              if (e.target.hasAttribute('data-validate')) {
                const debouncedValidate = debounce(
                  () => this.validateField(e.target), 
                  this.config.debounceTime
                );
                debouncedValidate();
              }
            });
          }
          
          if (this.config.validateOnBlur) {
            form.addEventListener('blur', e => {
              if (e.target.hasAttribute('data-validate')) {
                this.validateField(e.target);
              }
            }, true);
          }
          
          if (this.config.validateOnSubmit) {
            form.addEventListener('submit', e => {
              if (!this.config.skipDefaultBehavior) {
                e.preventDefault();
              }
              
              this.validateAll().then(isValid => {
                if (isValid) {
                  if (this.config.skipDefaultBehavior) {
                    return;
                  }
                  
                  // Dispatch custom event to allow form submission to be handled
                  const submitEvent = new CustomEvent('cf:form:validated', {
                    bubbles: true,
                    cancelable: true,
                    detail: { valid: true, form }
                  });
                  
                  const shouldContinue = form.dispatchEvent(submitEvent);
                  
                  if (shouldContinue) {
                    // If no handler prevented the event, submit the form
                    form.submit();
                  }
                } else {
                  // Dispatch invalid event
                  form.dispatchEvent(new CustomEvent('cf:form:invalid', {
                    bubbles: true,
                    detail: { errors: this.errors, form }
                  }));
                }
              });
            });
          }
        },
        
        /**
         * Validate a specific field
         * @param {HTMLElement} field Field to validate
         * @returns {Promise<boolean>} Promise that resolves with validation result
         */
        validateField(field) {
          // Get validation rules from data attribute
          const rules = field.dataset.validate?.split('|') || [];
          if (rules.length === 0) return Promise.resolve(true);
          
          // Get field name and value
          const name = field.name;
          const value = field.value;
          
          // Clear existing errors for this field
          this.clearFieldError(field);
          
          // Track pending validations
          const pendingKey = `field-${name}`;
          if (this.pendingValidations.has(pendingKey)) {
            clearTimeout(this.pendingValidations.get(pendingKey).timeout);
          }
          
          return new Promise(resolve => {
            // Process synchronous validations first
            let isValid = true;
            let errorMessage = '';
            let asyncRules = [];
            
            // Check each rule
            for (const ruleStr of rules) {
              // Parse rule name and parameters
              const [ruleName, param] = ruleStr.includes(':') 
                ? ruleStr.split(':', 2) 
                : [ruleStr, null];
              
              // Get validation rule
              const rule = this.rules[ruleName];
              
              if (!rule) {
                console.warn(`Unknown validation rule: ${ruleName}`);
                continue;
              }
              
              // Skip optional fields that are empty (except for 'required' rule)
              if ((value === '' || value === null || value === undefined) && ruleName !== 'required') {
                continue;
              }
              
              // If it's an async rule, collect it for later
              if (rule.async) {
                asyncRules.push({ ruleName, rule, param });
                continue;
              }
              
              // Run synchronous validation
              if (!rule.validate(value, param, this.form, this.config)) {
                isValid = false;
                errorMessage = this._formatErrorMessage(rule.message, param);
                break;
              }
            }
            
            // If already invalid from sync validation or no async validations needed
            if (!isValid || asyncRules.length === 0) {
              if (!isValid) {
                this.showFieldError(field, errorMessage);
              }
              resolve(isValid);
              return;
            }
            
            // Process async validations
            const asyncPromises = asyncRules.map(({ rule, param }) => 
              rule.validate(value, param, this.form, this.config)
            );
            
            // Create a timeout promise
            const timeoutPromise = new Promise(resolve => {
              const timeoutId = setTimeout(() => {
                resolve({ timedOut: true });
              }, this.config.asyncTimeout);
              
              // Store timeout ID to allow cancellation
              this.pendingValidations.set(pendingKey, {
                timeout: timeoutId,
                field
              });
            });
            
            // Race async validations with timeout
            Promise.race([Promise.all(asyncPromises), timeoutPromise])
              .then(results => {
                // Clean up
                this.pendingValidations.delete(pendingKey);
                
                // If timed out, show timeout error
                if (results.timedOut) {
                  this.showFieldError(field, 'Walidacja przekroczyła limit czasu.');
                  resolve(false);
                  return;
                }
                
                // Check if all async validations passed
                const allValid = results.every(result => result === true);
                
                if (!allValid) {
                  // Find the first failing rule to get its message
                  for (let i = 0; i < results.length; i++) {
                    if (results[i] !== true) {
                      const { rule, param } = asyncRules[i];
                      const message = this._formatErrorMessage(rule.message, param);
                      this.showFieldError(field, message);
                      break;
                    }
                  }
                }
                
                resolve(allValid);
              })
              .catch(err => {
                console.error('Async validation error:', err);
                this.showFieldError(field, 'Wystąpił błąd podczas walidacji.');
                resolve(false);
              });
          });
        },
        
        /**
         * Validate all fields in the form
         * @returns {Promise<boolean>} Promise that resolves with overall validation result
         */
        validateAll() {
          // Get all fields with validation rules
          const fields = Array.from(this.form.querySelectorAll('[data-validate]'));
          
          // Clear all errors
          this.clearAllErrors();
          
          // Validate each field
          const validations = fields.map(field => this.validateField(field));
          
          // Wait for all validations to complete
          return Promise.all(validations).then(results => {
            return results.every(result => result === true);
          });
        },
        
        /**
         * Show validation error for a field
         * @param {HTMLElement} field Field with error
         * @param {string} message Error message
         */
        showFieldError(field, message) {
          const name = field.name;
          this.errors[name] = message;
          
          // Add error classes to field
          field.classList.add(this.config.errorClass);
          field.classList.add(this.config.errorBorderClass);
          field.setAttribute('aria-invalid', 'true');
          
          // Create or update error message element
          let errorElement = this.form.querySelector(`[data-error-for="${name}"]`);
          
          if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = this.config.errorTextClass;
            errorElement.setAttribute('data-error-for', name);
            
            // Find the best place to insert the error
            const formGroup = field.closest('.form-group') || field.parentNode;
            formGroup.appendChild(errorElement);
          }
          
          errorElement.textContent = message;
          
          // Set ARIA attributes
          const errorId = `error-${name}`;
          errorElement.id = errorId;
          field.setAttribute('aria-describedby', errorId);
          
          // Dispatch error event
          field.dispatchEvent(new CustomEvent('cf:field:error', {
            bubbles: true,
            detail: { field, name, message }
          }));
        },
        
        /**
         * Clear validation error for a field
         * @param {HTMLElement} field Field to clear error for
         */
        clearFieldError(field) {
          const name = field.name;
          delete this.errors[name];
          
          // Remove error classes
          field.classList.remove(this.config.errorClass);
          field.classList.remove(this.config.errorBorderClass);
          field.removeAttribute('aria-invalid');
          
          // Remove error message element
          const errorElement = this.form.querySelector(`[data-error-for="${name}"]`);
          if (errorElement) {
            errorElement.remove();
          }
          
          // Remove ARIA attributes
          field.removeAttribute('aria-describedby');
        },
        
        /**
         * Clear all validation errors
         */
        clearAllErrors() {
          this.errors = {};
          
          // Clear all field errors
          const fields = Array.from(this.form.querySelectorAll('[data-validate]'));
          fields.forEach(field => this.clearFieldError(field));
          
          // Clear any remaining error elements
          const errorElements = this.form.querySelectorAll('[data-error-for]');
          errorElements.forEach(el => el.remove());
        },
        
        /**
         * Format error message with parameters
         * @private
         * @param {string} message Error message template
         * @param {string} param Parameter string
         * @returns {string} Formatted error message
         */
        _formatErrorMessage(message, param) {
          if (!param) return message;
          
          const params = param.split(',');
          return message.replace(/\{(\d+)\}/g, (match, index) => {
            return params[parseInt(index, 10)] || '';
          });
        }
      };
      
      return validator;
    },
    
    /**
     * Register a custom validation rule
     * @param {string} name Rule name
     * @param {Function|Object} rule Validation function or rule object
     * @param {string} [message] Error message
     * @returns {Object} This validation module for chaining
     */
    registerRule(name, rule, message) {
      if (typeof rule === 'function') {
        this.rules[name] = {
          validate: rule,
          message: message || `Validation failed: ${name}`
        };
      } else if (typeof rule === 'object') {
        this.rules[name] = { ...rule };
      }
      
      return this;
    }
  };
  
  // Register with CarFuse
  if (!CarFuse.forms) CarFuse.forms = {};
  CarFuse.forms.validation = validation;
  
  // Export to window in case CarFuse is not available
  if (!window.CarFuse) {
    window.CarFuseForms = window.CarFuseForms || {};
    window.CarFuseForms.validation = validation;
  }
})();
