/**
 * CarFuse Form Error Display
 * Provides utilities for displaying validation errors
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
     * ErrorDisplay class handles displaying validation errors
     */
    class ErrorDisplay {
        /**
         * Create a new error display
         * @param {Object} options - Display options
         */
        constructor(options = {}) {
            this.options = {
                errorClass: 'is-invalid',
                validClass: 'is-valid',
                errorMessageClass: 'invalid-feedback',
                errorMessageTag: 'div',
                errorFormClass: 'has-validation-errors',
                errorFieldWrapperClass: 'has-validation-error',
                fieldSelector: '[name]',
                summarySelectorId: null,
                summaryClass: 'validation-summary alert alert-danger',
                summaryTitle: 'Formularz zawiera błędy:',
                showSummary: false,
                inlineErrors: true,
                ...options
            };
            
            // Store form element
            this.formElement = null;
            
            // Create logger if CarFuse errorHandler exists
            this.logger = CarFuse.errorHandler?.createLogger 
                ? CarFuse.errorHandler.createLogger('ErrorDisplay') 
                : console;
        }
        
        /**
         * Attach error display to a form
         * @param {HTMLFormElement} form - Form element
         * @returns {ErrorDisplay} This instance for chaining
         */
        attach(form) {
            if (!(form instanceof HTMLElement) || form.tagName !== 'FORM') {
                throw new Error('First argument must be a form element');
            }
            
            this.formElement = form;
            return this;
        }
        
        /**
         * Show validation errors
         * @param {Object} errors - Map of field names to error messages
         * @returns {ErrorDisplay} This instance for chaining
         */
        showErrors(errors) {
            if (!this.formElement) {
                throw new Error('Error display is not attached to a form');
            }
            
            // Clear previous errors
            this.clearErrors();
            
            // Add form error class
            if (Object.keys(errors).length > 0) {
                this.formElement.classList.add(this.options.errorFormClass);
                
                // Show summary if enabled
                if (this.options.showSummary) {
                    this.showErrorSummary(errors);
                }
            }
            
            // Show inline errors if enabled
            if (this.options.inlineErrors) {
                // Process each error
                for (const [fieldName, message] of Object.entries(errors)) {
                    this.showFieldError(fieldName, message);
                }
            }
            
            return this;
        }
        
        /**
         * Show error for a specific field
         * @param {string} fieldName - Field name
         * @param {string} message - Error message
         * @returns {ErrorDisplay} This instance for chaining
         */
        showFieldError(fieldName, message) {
            // Find field
            const field = this.formElement.querySelector(`[name="${fieldName}"]`);
            if (!field) {
                this.logger.warn(`Field not found: ${fieldName}`);
                return this;
            }
            
            // Find or create wrapper
            const wrapper = this.findFieldWrapper(field);
            
            // Add error class to field
            field.classList.add(this.options.errorClass);
            if (this.options.validClass) {
                field.classList.remove(this.options.validClass);
            }
            
            // Add error class to wrapper
            if (wrapper) {
                wrapper.classList.add(this.options.errorFieldWrapperClass);
            }
            
            // Find or create message element
            const messageElement = this.findOrCreateMessageElement(field, wrapper);
            
            // Set error message
            messageElement.textContent = message;
            messageElement.style.display = 'block';
            
            // Set ARIA attributes
            field.setAttribute('aria-invalid', 'true');
            messageElement.id = messageElement.id || `error-${fieldName}`;
            field.setAttribute('aria-describedby', messageElement.id);
            
            return this;
        }
        
        /**
         * Show a summary of all errors
         * @param {Object} errors - Map of field names to error messages
         */
        showErrorSummary(errors) {
            let summaryElement;
            
            // Find existing summary element
            if (this.options.summarySelectorId) {
                summaryElement = document.getElementById(this.options.summarySelectorId);
            }
            
            // Create a new summary element if not found
            if (!summaryElement) {
                summaryElement = document.createElement('div');
                summaryElement.id = this.options.summarySelectorId || 'validation-summary';
                summaryElement.className = this.options.summaryClass;
                summaryElement.setAttribute('role', 'alert');
                
                // Insert at the top of the form
                this.formElement.insertBefore(summaryElement, this.formElement.firstChild);
            }
            
            // Clear previous content
            summaryElement.innerHTML = '';
            
            // Add title
            if (this.options.summaryTitle) {
                const title = document.createElement('h5');
                title.textContent = this.options.summaryTitle;
                summaryElement.appendChild(title);
            }
            
            // Create error list
            const errorList = document.createElement('ul');
            
            // Add each error
            for (const [fieldName, message] of Object.entries(errors)) {
                const errorItem = document.createElement('li');
                
                // Try to find field label
                let fieldLabel = fieldName;
                const field = this.formElement.querySelector(`[name="${fieldName}"]`);
                
                if (field) {
                    // Find label by for attribute
                    const labelElement = document.querySelector(`label[for="${field.id}"]`);
                    
                    if (labelElement) {
                        fieldLabel = labelElement.textContent.trim();
                    } else {
                        // Try to find label as a sibling of a wrapper
                        const wrapper = this.findFieldWrapper(field);
                        const siblingLabel = wrapper?.querySelector('label');
                        
                        if (siblingLabel) {
                            fieldLabel = siblingLabel.textContent.trim();
                        }
                    }
                }
                
                // Create error text with field name and message
                errorItem.innerHTML = `<strong>${fieldLabel}:</strong> ${message}`;
                
                // Add click handler to focus field
                errorItem.style.cursor = 'pointer';
                errorItem.addEventListener('click', () => {
                    const field = this.formElement.querySelector(`[name="${fieldName}"]`);
                    if (field) {
                        field.focus();
                        
                        // Scroll into view if not visible
                        field.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                });
                
                errorList.appendChild(errorItem);
            }
            
            summaryElement.appendChild(errorList);
        }
        
        /**
         * Find the wrapper element for a field
         * @param {HTMLElement} field - Form field
         * @returns {HTMLElement} Field wrapper element
         */
        findFieldWrapper(field) {
            // Look for parent with form-group class or data-field-wrapper
            return field.closest('.form-group') || 
                  field.closest('[data-field-wrapper]') ||
                  field.parentElement;
        }
        
        /**
         * Find or create error message element for a field
         * @param {HTMLElement} field - Form field
         * @param {HTMLElement} wrapper - Field wrapper
         * @returns {HTMLElement} Error message element
         */
        findOrCreateMessageElement(field, wrapper) {
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
         * Clear all error messages
         * @returns {ErrorDisplay} This instance for chaining
         */
        clearErrors() {
            if (!this.formElement) return this;
            
            // Remove form error class
            this.formElement.classList.remove(this.options.errorFormClass);
            
            // Clear inline errors
            if (this.options.inlineErrors) {
                // Find all fields with error class
                const errorFields = this.formElement.querySelectorAll(`.${this.options.errorClass}`);
                
                errorFields.forEach(field => {
                    // Remove error class
                    field.classList.remove(this.options.errorClass);
                    
                    // Remove aria attributes
                    field.removeAttribute('aria-invalid');
                    field.removeAttribute('aria-describedby');
                    
                    // Find and clean up wrapper
                    const wrapper = this.findFieldWrapper(field);
                    if (wrapper) {
                        wrapper.classList.remove(this.options.errorFieldWrapperClass);
                        
                        // Find and remove error message
                        const messageElement = wrapper.querySelector(`.${this.options.errorMessageClass}[data-field="${field.name}"]`);
                        if (messageElement) {
                            messageElement.textContent = '';
                            messageElement.style.display = 'none';
                        }
                    }
                });
            }
            
            // Clear error summary
            if (this.options.showSummary) {
                const summaryId = this.options.summarySelectorId || 'validation-summary';
                const summaryElement = document.getElementById(summaryId);
                
                if (summaryElement) {
                    summaryElement.innerHTML = '';
                    summaryElement.style.display = 'none';
                }
            }
            
            return this;
        }
        
        /**
         * Add success indication to a field
         * @param {string} fieldName - Field name
         * @returns {ErrorDisplay} This instance for chaining
         */
        showSuccess(fieldName) {
            if (!this.options.validClass) return this;
            
            const field = this.formElement.querySelector(`[name="${fieldName}"]`);
            if (field) {
                // Remove error class if present
                field.classList.remove(this.options.errorClass);
                
                // Add valid class
                field.classList.add(this.options.validClass);
            }
            
            return this;
        }
    }
    
    // Register with CarFuse
    CarFuse.forms.ErrorDisplay = ErrorDisplay;
    
    // Create factory function
    CarFuse.forms.createErrorDisplay = function(options) {
        return new ErrorDisplay(options);
    };
})();
