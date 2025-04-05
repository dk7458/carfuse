/**
 * CarFuse Forms - Submission Module
 * Handles form submission with CSRF protection and loading states
 */

(function() {
  // Namespaces
  const CarFuse = window.CarFuse || {};
  if (!CarFuse.forms) CarFuse.forms = {};
  
  // Submission module
  const submission = {
    // Configuration
    config: {
      method: 'POST',
      ajaxSubmit: true,
      resetOnSuccess: true,
      redirectOnSuccess: false,
      showLoadingIndicator: true,
      showSuccessMessage: true,
      showErrorMessage: true,
      loadingClass: 'loading',
      buttonLoadingClass: 'btn-loading',
      loadingText: 'Przetwarzanie...',
      successRedirectDelay: 1000
    },
    
    /**
     * Initialize submission module
     * @param {Object} options Configuration options
     * @returns {Promise} Promise that resolves when initialization is complete
     */
    init(options = {}) {
      // Apply custom options
      Object.assign(this.config, options);
      
      return Promise.resolve(this);
    },
    
    /**
     * Create a form submitter for a specific form
     * @param {HTMLElement} form Form element
     * @param {Object} options Submission options
     * @returns {Object} Submitter instance
     */
    createSubmitter(form, options = {}) {
      // Local options (combine global config with form-specific options)
      const config = { ...this.config, ...options };
      
      // Submitter methods
      const submitter = {
        form,
        config,
        isSubmitting: false,
        
        /**
         * Initialize the submitter
         */
        init() {
          // Set up form submission event handler
          form.addEventListener('submit', e => {
            if (this.config.ajaxSubmit) {
              e.preventDefault();
              this.submit();
            }
          });
          
          return this;
        },
        
        /**
         * Submit the form
         * @returns {Promise} Promise that resolves with submission result
         */
        submit() {
          if (this.isSubmitting) {
            return Promise.reject(new Error('Form submission already in progress'));
          }
          
          this.isSubmitting = true;
          this._showLoadingState();
          
          // Check if using FormData or JSON
          const isFormData = !this.config.useJson;
          let formData;
          
          if (isFormData) {
            formData = new FormData(this.form);
          } else {
            formData = this._serializeFormToJson();
          }
          
          // Add CSRF token
          this._addCsrfToken(formData, isFormData);
          
          // Get form action and method
          const url = this.form.getAttribute('action') || window.location.href;
          const method = this.form.getAttribute('method')?.toUpperCase() || this.config.method;
          
          // Set up request headers
          const headers = {
            'X-Requested-With': 'XMLHttpRequest'
          };
          
          if (!isFormData) {
            headers['Content-Type'] = 'application/json';
          }
          
          // Add Authorization header if authenticated
          if (window.AuthHelper && window.AuthHelper.isAuthenticated) {
            const token = window.AuthHelper.getToken();
            if (token) {
              headers['Authorization'] = `Bearer ${token}`;
            }
          }
          
          // Make the request
          return fetch(url, {
            method,
            headers,
            body: isFormData ? formData : JSON.stringify(formData)
          })
            .then(response => {
              if (!response.ok) {
                return response.json().then(data => {
                  throw new Error(data.message || `HTTP error: ${response.status}`);
                });
              }
              
              // Check if response is JSON
              const contentType = response.headers.get('content-type');
              if (contentType && contentType.includes('application/json')) {
                return response.json();
              } else {
                return response.text();
              }
            })
            .then(data => {
              // Handle successful submission
              this._handleSuccess(data);
              return data;
            })
            .catch(error => {
              // Handle submission error
              this._handleError(error);
              throw error;
            })
            .finally(() => {
              this.isSubmitting = false;
              this._hideLoadingState();
            });
        },
        
        /**
         * Show loading state on form
         * @private
         */
        _showLoadingState() {
          if (!this.config.showLoadingIndicator) return;
          
          this.form.classList.add(this.config.loadingClass);
          
          // Find submit button
          const submitBtn = this.form.querySelector('button[type="submit"]');
          if (submitBtn) {
            submitBtn.disabled = true;
            
            // Store original text if not already stored
            if (!submitBtn.dataset.originalText) {
              submitBtn.dataset.originalText = submitBtn.textContent;
            }
            
            // Add loading class
            submitBtn.classList.add(this.config.buttonLoadingClass);
            
            // Add spinner if needed
            if (!submitBtn.querySelector('.btn-spinner')) {
              const spinner = document.createElement('span');
              spinner.className = 'btn-spinner mr-2';
              spinner.innerHTML = '<div class="spinner spinner-border-t h-4 w-4"></div>';
              submitBtn.prepend(spinner);
            }
            
            // Update button text
            const textSpan = submitBtn.querySelector('.btn-text');
            if (textSpan) {
              textSpan.textContent = this.config.loadingText;
            } else {
              submitBtn.textContent = this.config.loadingText;
            }
          }
        },
        
        /**
         * Hide loading state on form
         * @private
         */
        _hideLoadingState() {
          if (!this.config.showLoadingIndicator) return;
          
          this.form.classList.remove(this.config.loadingClass);
          
          // Find submit button
          const submitBtn = this.form.querySelector('button[type="submit"]');
          if (submitBtn) {
            submitBtn.disabled = false;
            
            // Remove loading class
            submitBtn.classList.remove(this.config.buttonLoadingClass);
            
            // Remove spinner
            const spinner = submitBtn.querySelector('.btn-spinner');
            if (spinner) {
              spinner.remove();
            }
            
            // Restore original text
            if (submitBtn.dataset.originalText) {
              const textSpan = submitBtn.querySelector('.btn-text');
              if (textSpan) {
                textSpan.textContent = submitBtn.dataset.originalText;
              } else {
                submitBtn.textContent = submitBtn.dataset.originalText;
              }
              
              delete submitBtn.dataset.originalText;
            }
          }
        },
        
        /**
         * Add CSRF token to form data
         * @param {FormData|Object} formData Form data
         * @param {boolean} isFormData Whether formData is a FormData object
         * @private
         */
        _addCsrfToken(formData, isFormData) {
          // First try to get token from security module
          let csrfToken = null;
          
          if (window.CarFuseSecurity && window.CarFuseSecurity.getCsrfToken) {
            csrfToken = window.CarFuseSecurity.getCsrfToken();
          } else if (window.AuthHelper && window.AuthHelper.getCsrfToken) {
            csrfToken = window.AuthHelper.getCsrfToken();
          } else {
            // Fallback to meta tag
            csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          }
          
          if (!csrfToken) {
            console.warn('CSRF token not found');
            return;
          }
          
          // Add token to form data
          if (isFormData) {
            formData.append('_token', csrfToken);
          } else {
            formData._token = csrfToken;
          }
        },
        
        /**
         * Serialize form to JSON object
         * @returns {Object} Form data as JSON
         * @private
         */
        _serializeFormToJson() {
          const formData = new FormData(this.form);
          const json = {};
          
          for (const [key, value] of formData.entries()) {
            // Handle array inputs (e.g., checkboxes with same name)
            if (key.endsWith('[]')) {
              const arrayKey = key.slice(0, -2);
              if (!json[arrayKey]) {
                json[arrayKey] = [];
              }
              json[arrayKey].push(value);
            } else if (json[key] !== undefined) {
              // If key already exists, convert to array
              if (!Array.isArray(json[key])) {
                json[key] = [json[key]];
              }
              json[key].push(value);
            } else {
              json[key] = value;
            }
          }
          
          return json;
        },
        
        /**
         * Handle successful form submission
         * @param {Object|string} data Response data
         * @private
         */
        _handleSuccess(data) {
          // Dispatch success event
          this.form.dispatchEvent(new CustomEvent('cf:form:success', {
            bubbles: true,
            detail: { data, form: this.form }
          }));
          
          // Show success message
          if (this.config.showSuccessMessage) {
            const message = typeof data === 'object' ? data.message : 'Form submitted successfully';
            this._showToast('Success', message, 'success');
          }
          
          // Reset form if configured
          if (this.config.resetOnSuccess) {
            this.form.reset();
          }
          
          // Redirect if configured
          if (this.config.redirectOnSuccess) {
            const redirectUrl = typeof data === 'object' && data.redirect ? 
              data.redirect : this.config.redirectUrl;
              
            if (redirectUrl) {
              setTimeout(() => {
                window.location.href = redirectUrl;
              }, this.config.successRedirectDelay);
            }
          }
        },
        
        /**
         * Handle form submission error
         * @param {Error} error Error object
         * @private
         */
        _handleError(error) {
          // Dispatch error event
          this.form.dispatchEvent(new CustomEvent('cf:form:error', {
            bubbles: true,
            detail: { error, form: this.form }
          }));
          
          // Show error message
          if (this.config.showErrorMessage) {
            this._showToast('Error', error.message, 'error');
          }
          
          // Log error
          console.error('Form submission error:', error);
        },
        
        /**
         * Show toast notification
         * @param {string} title Toast title
         * @param {string} message Toast message
         * @param {string} type Toast type (success, error, warning, info)
         * @private
         */
        _showToast(title, message, type) {
          // Try CarFuse notifications component first
          if (window.CarFuse && window.CarFuse.notifications) {
            window.CarFuse.notifications.showToast({ title, message, type });
            return;
          }
          
          // Try using event-based toast system
          window.dispatchEvent(new CustomEvent('show-toast', {
            detail: { title, message, type }
          }));
        }
      };
      
      return submitter;
    }
  };
  
  // Register with CarFuse
  if (!CarFuse.forms) CarFuse.forms = {};
  CarFuse.forms.submission = submission;
  
  // Export to window in case CarFuse is not available
  if (!window.CarFuse) {
    window.CarFuseForms = window.CarFuseForms || {};
    window.CarFuseForms.submission = submission;
  }
})();
