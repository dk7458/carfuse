/**
 * CarFuse Forms - Main entry point
 * A comprehensive form handling system for CarFuse
 */

(function() {
  // Import submodules
  const validation = window.CarFuse.forms.validation;
  const components = window.CarFuse.forms.components;
  const submission = window.CarFuse.forms.submission;
  const uploads = window.CarFuse.forms.uploads;
  
  // CarFuse Forms API
  const forms = {
    // Re-export submodules
    validation,
    components,
    submission,
    uploads,
    
    /**
     * Initialize the forms system
     * @param {Object} options Configuration options
     * @returns {Promise} Promise that resolves when initialization is complete
     */
    init(options = {}) {
      console.log('[CarFuse Forms] Initializing forms system');
      
      // Initialize submodules
      return Promise.all([
        validation.init(options.validation),
        components.init(options.components),
        submission.init(options.submission),
        uploads.init(options.uploads)
      ]).then(() => {
        console.log('[CarFuse Forms] Forms system initialized');
        return this;
      });
    },
    
    /**
     * Create a new form instance with validation and submission handling
     * @param {string|HTMLElement} form Form element or selector
     * @param {Object} options Form options
     * @returns {Object} Form instance
     */
    create(form, options = {}) {
      const formElement = typeof form === 'string' 
        ? document.querySelector(form) 
        : form;
        
      if (!formElement) {
        throw new Error(`Form not found: ${form}`);
      }
      
      return {
        element: formElement,
        validator: validation.createValidator(formElement, options.validation),
        submitter: submission.createSubmitter(formElement, options.submission),
        
        /**
         * Initialize form
         * @returns {Object} This form instance
         */
        init() {
          this.validator.init();
          this.submitter.init();
          return this;
        },
        
        /**
         * Validate the form
         * @returns {Promise<boolean>} Promise that resolves with validation result
         */
        validate() {
          return this.validator.validateAll();
        },
        
        /**
         * Submit the form
         * @param {Event} [event] Optional submit event to prevent default
         * @returns {Promise} Promise that resolves with submission result
         */
        submit(event) {
          if (event) {
            event.preventDefault();
          }
          
          return this.validate()
            .then(isValid => {
              if (isValid) {
                return this.submitter.submit();
              } else {
                return Promise.reject(new Error('Form validation failed'));
              }
            });
        }
      }.init();
    },
    
    /**
     * Add CarFuse form functionality to existing forms
     * @param {string} [selector='form[data-cf-form]'] CSS selector for forms to enhance
     */
    enhance(selector = 'form[data-cf-form]') {
      const forms = document.querySelectorAll(selector);
      
      forms.forEach(form => {
        // Parse options from data attributes
        const options = this._parseDataOptions(form);
        
        // Create form instance
        this.create(form, options);
      });
    },
    
    /**
     * Parse form options from data attributes
     * @private
     * @param {HTMLElement} form Form element
     * @returns {Object} Parsed options
     */
    _parseDataOptions(form) {
      const options = {};
      
      // Get validation options
      if (form.dataset.cfValidation) {
        try {
          options.validation = JSON.parse(form.dataset.cfValidation);
        } catch (e) {
          console.error('Invalid validation options:', e);
        }
      }
      
      // Get submission options
      if (form.dataset.cfSubmission) {
        try {
          options.submission = JSON.parse(form.dataset.cfSubmission);
        } catch (e) {
          console.error('Invalid submission options:', e);
        }
      }
      
      return options;
    }
  };
  
  // Register with CarFuse
  if (window.CarFuse) {
    window.CarFuse.forms = forms;
    
    if (window.CarFuse.registerComponent) {
      window.CarFuse.registerComponent('forms', forms, {
        dependencies: ['core', 'security', 'events']
      });
    }
  } else {
    window.CarFuseForms = forms;
  }
  
  // Auto-initialize on DOMContentLoaded if not using CarFuse loader
  document.addEventListener('DOMContentLoaded', () => {
    if (!window.CarFuse || !window.CarFuse.state || !window.CarFuse.state.loading) {
      forms.init();
      forms.enhance();
      
      // Enhance file upload inputs
      if (forms.uploads) {
        forms.uploads.enhance();
      }
    }
  });
})();
