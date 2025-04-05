/**
 * CarFuse Forms - Templates Module
 * Provides pre-built form templates for common use cases
 */

(function() {
  // Namespaces
  const CarFuse = window.CarFuse || {};
  if (!CarFuse.forms) CarFuse.forms = {};
  
  // Templates module
  const templates = {
    // Configuration
    config: {
      baseClass: 'cf-form',
      defaultTheme: 'default'
    },
    
    /**
     * Initialize templates module
     * @param {Object} options Configuration options
     * @returns {Promise} Promise that resolves when initialization is complete
     */
    init(options = {}) {
      // Apply custom options
      Object.assign(this.config, options);
      
      return Promise.resolve(this);
    },
    
    /**
     * Create a login form
     * @param {Object} options Form options
     * @returns {HTMLFormElement} The generated form
     */
    createLoginForm(options = {}) {
      const config = {
        formId: 'login-form',
        formAction: options.action || '/auth/login',
        formMethod: 'POST',
        submitButtonText: options.submitText || 'Log In',
        includeRememberMe: options.rememberMe !== false,
        includeForgotPassword: options.forgotPassword !== false,
        redirectUrl: options.redirectUrl || '',
        emailLabel: options.emailLabel || 'Email',
        passwordLabel: options.passwordLabel || 'Password',
        rememberMeLabel: options.rememberMeLabel || 'Remember me',
        forgotPasswordText: options.forgotPasswordText || 'Forgot password?',
        forgotPasswordUrl: options.forgotPasswordUrl || '/auth/forgot-password',
        ...options
      };
      
      // Create the login form schema
      const loginSchema = {
        fields: [
          {
            type: 'text',
            name: 'email',
            label: config.emailLabel,
            inputClass: 'w-full',
            placeholder: 'name@example.com',
            required: true,
            validation: 'required|email'
          },
          {
            type: 'password',
            name: 'password',
            label: config.passwordLabel,
            inputClass: 'w-full',
            required: true,
            validation: 'required|min:6'
          }
        ],
        options: {
          validation: {
            validateOnBlur: true,
            validateOnSubmit: true
          },
          submission: {
            resetOnSuccess: false,
            redirectOnSuccess: config.redirectUrl || true
          }
        }
      };
      
      // Create form using the forms.utils module
      const form = CarFuse.forms.utils.createForm(loginSchema, {
        formId: config.formId,
        formAction: config.formAction,
        formMethod: config.formMethod,
        submitButtonText: config.submitButtonText
      });
      
      // Add remember me checkbox if required
      if (config.includeRememberMe) {
        const buttonsContainer = form.querySelector('.cf-form-buttons');
        const rememberMeContainer = document.createElement('div');
        rememberMeContainer.className = 'flex items-center space-x-2';
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = 'remember';
        checkbox.id = `${config.formId}-remember`;
        checkbox.className = 'form-checkbox';
        
        const label = document.createElement('label');
        label.htmlFor = checkbox.id;
        label.className = 'text-sm text-gray-600';
        label.textContent = config.rememberMeLabel;
        
        rememberMeContainer.appendChild(checkbox);
        rememberMeContainer.appendChild(label);
        
        // Insert before the buttons
        form.insertBefore(rememberMeContainer, buttonsContainer);
      }
      
      // Add forgot password link if required
      if (config.includeForgotPassword) {
        const buttonsContainer = form.querySelector('.cf-form-buttons');
        const forgotContainer = document.createElement('div');
        forgotContainer.className = 'text-sm text-right mt-2';
        
        const link = document.createElement('a');
        link.href = config.forgotPasswordUrl;
        link.className = 'text-primary-600 hover:text-primary-800';
        link.textContent = config.forgotPasswordText;
        
        forgotContainer.appendChild(link);
        
        // Insert after the buttons
        buttonsContainer.parentNode.insertBefore(forgotContainer, buttonsContainer.nextSibling);
      }
      
      // Add a hidden redirect field if redirectUrl is specified
      if (config.redirectUrl) {
        const redirectInput = document.createElement('input');
        redirectInput.type = 'hidden';
        redirectInput.name = 'redirect';
        redirectInput.value = config.redirectUrl;
        form.appendChild(redirectInput);
      }
      
      return form;
    },
    
    /**
     * Create a registration form
     * @param {Object} options Form options
     * @returns {HTMLFormElement} The generated form
     */
    createRegistrationForm(options = {}) {
      const config = {
        formId: 'registration-form',
        formAction: options.action || '/auth/register',
        formMethod: 'POST',
        submitButtonText: options.submitText || 'Register',
        includeNameField: options.nameField !== false,
        passwordConfirmation: options.passwordConfirmation !== false,
        termsAndConditions: options.termsAndConditions !== false,
        redirectUrl: options.redirectUrl || '',
        nameLabel: options.nameLabel || 'Name',
        emailLabel: options.emailLabel || 'Email',
        passwordLabel: options.passwordLabel || 'Password',
        confirmPasswordLabel: options.confirmPasswordLabel || 'Confirm Password',
        termsLabel: options.termsLabel || 'I agree to the Terms and Conditions',
        termsUrl: options.termsUrl || '/terms',
        ...options
      };
      
      // Create the registration form fields
      const fields = [];
      
      // Add name field if required
      if (config.includeNameField) {
        fields.push({
          type: 'text',
          name: 'name',
          label: config.nameLabel,
          inputClass: 'w-full',
          required: true,
          validation: 'required|min:2'
        });
      }
      
      // Add standard fields
      fields.push(
        {
          type: 'text',
          name: 'email',
          label: config.emailLabel,
          inputClass: 'w-full',
          placeholder: 'name@example.com',
          required: true,
          validation: 'required|email'
        },
        {
          type: 'password',
          name: 'password',
          label: config.passwordLabel,
          inputClass: 'w-full',
          required: true,
          validation: 'required|min:8'
        }
      );
      
      // Add password confirmation if required
      if (config.passwordConfirmation) {
        fields.push({
          type: 'password',
          name: 'password_confirmation',
          label: config.confirmPasswordLabel,
          inputClass: 'w-full',
          required: true,
          validation: 'required|confirmed:password'
        });
      }
      
      // Create the registration form schema
      const registrationSchema = {
        fields: fields,
        options: {
          validation: {
            validateOnBlur: true,
            validateOnSubmit: true
          },
          submission: {
            resetOnSuccess: false,
            redirectOnSuccess: config.redirectUrl || true
          }
        }
      };
      
      // Create form using the forms.utils module
      const form = CarFuse.forms.utils.createForm(registrationSchema, {
        formId: config.formId,
        formAction: config.formAction,
        formMethod: config.formMethod,
        submitButtonText: config.submitButtonText
      });
      
      // Add terms and conditions checkbox if required
      if (config.termsAndConditions) {
        const buttonsContainer = form.querySelector('.cf-form-buttons');
        const termsContainer = document.createElement('div');
        termsContainer.className = 'flex items-center space-x-2 mb-4';
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = 'terms';
        checkbox.id = `${config.formId}-terms`;
        checkbox.className = 'form-checkbox';
        checkbox.required = true;
        checkbox.setAttribute('data-validate', 'required');
        
        const label = document.createElement('label');
        label.htmlFor = checkbox.id;
        label.className = 'text-sm text-gray-600';
        
        // Create label with link to terms
        const labelText = document.createTextNode(config.termsLabel.split('Terms and Conditions')[0]);
        label.appendChild(labelText);
        
        const link = document.createElement('a');
        link.href = config.termsUrl;
        link.className = 'text-primary-600 hover:text-primary-800';
        link.textContent = 'Terms and Conditions';
        label.appendChild(link);
        
        if (config.termsLabel.split('Terms and Conditions')[1]) {
          const remainingText = document.createTextNode(config.termsLabel.split('Terms and Conditions')[1]);
          label.appendChild(remainingText);
        }
        
        termsContainer.appendChild(checkbox);
        termsContainer.appendChild(label);
        
        // Insert before the buttons
        form.insertBefore(termsContainer, buttonsContainer);
      }
      
      // Add a hidden redirect field if redirectUrl is specified
      if (config.redirectUrl) {
        const redirectInput = document.createElement('input');
        redirectInput.type = 'hidden';
        redirectInput.name = 'redirect';
        redirectInput.value = config.redirectUrl;
        form.appendChild(redirectInput);
      }
      
      return form;
    },
    
    /**
     * Create a contact form
     * @param {Object} options Form options
     * @returns {HTMLFormElement} The generated form
     */
    createContactForm(options = {}) {
      const config = {
        formId: 'contact-form',
        formAction: options.action || '/contact',
        formMethod: 'POST',
        submitButtonText: options.submitText || 'Send Message',
        includePhoneField: options.phoneField !== false,
        includeSubjectField: options.subjectField !== false,
        nameLabel: options.nameLabel || 'Name',
        emailLabel: options.emailLabel || 'Email',
        phoneLabel: options.phoneLabel || 'Phone',
        subjectLabel: options.subjectLabel || 'Subject',
        messageLabel: options.messageLabel || 'Message',
        ...options
      };
      
      // Create the contact form fields
      const fields = [
        {
          type: 'text',
          name: 'name',
          label: config.nameLabel,
          inputClass: 'w-full',
          required: true,
          validation: 'required|min:2'
        },
        {
          type: 'text',
          name: 'email',
          label: config.emailLabel,
          inputClass: 'w-full',
          required: true,
          validation: 'required|email'
        }
      ];
      
      // Add phone field if required
      if (config.includePhoneField) {
        fields.push({
          type: 'tel',
          name: 'phone',
          label: config.phoneLabel,
          inputClass: 'w-full',
          validation: 'phone'
        });
      }
      
      // Add subject field if required
      if (config.includeSubjectField) {
        fields.push({
          type: 'text',
          name: 'subject',
          label: config.subjectLabel,
          inputClass: 'w-full',
          required: true,
          validation: 'required|min:3'
        });
      }
      
      // Add message field
      fields.push({
        type: 'textarea',
        name: 'message',
        label: config.messageLabel,
        rows: 5,
        inputClass: 'w-full',
        required: true,
        validation: 'required|min:10',
        autoResize: true
      });
      
      // Create the contact form schema
      const contactSchema = {
        fields: fields,
        options: {
          validation: {
            validateOnBlur: true,
            validateOnSubmit: true
          },
          submission: {
            resetOnSuccess: true,
            showSuccessMessage: true
          }
        }
      };
      
      // Create form using the forms.utils module
      const form = CarFuse.forms.utils.createForm(contactSchema, {
        formId: config.formId,
        formAction: config.formAction,
        formMethod: config.formMethod,
        submitButtonText: config.submitButtonText
      });
      
      return form;
    },
    
    /**
     * Create a profile edit form
     * @param {Object} options Form options
     * @param {Object} userData User data to populate the form
     * @returns {HTMLFormElement} The generated form
     */
    createProfileForm(options = {}, userData = {}) {
      const config = {
        formId: 'profile-form',
        formAction: options.action || '/profile',
        formMethod: 'POST',
        submitButtonText: options.submitText || 'Update Profile',
        includeAvatarUpload: options.avatarUpload !== false,
        includeBioField: options.bioField !== false,
        nameLabel: options.nameLabel || 'Name',
        emailLabel: options.emailLabel || 'Email',
        phoneLabel: options.phoneLabel || 'Phone',
        bioLabel: options.bioLabel || 'Bio',
        avatarLabel: options.avatarLabel || 'Profile Picture',
        ...options
      };
      
      // Create the profile form fields
      const fields = [
        {
          type: 'text',
          name: 'name',
          label: config.nameLabel,
          value: userData.name || '',
          inputClass: 'w-full',
          required: true,
          validation: 'required|min:2'
        },
        {
          type: 'text',
          name: 'email',
          label: config.emailLabel,
          value: userData.email || '',
          inputClass: 'w-full',
          required: true,
          validation: 'required|email'
        },
        {
          type: 'tel',
          name: 'phone',
          label: config.phoneLabel,
          value: userData.phone || '',
          inputClass: 'w-full',
          validation: 'phone'
        }
      ];
      
      // Add bio field if required
      if (config.includeBioField) {
        fields.push({
          type: 'textarea',
          name: 'bio',
          label: config.bioLabel,
          value: userData.bio || '',
          rows: 4,
          inputClass: 'w-full',
          autoResize: true,
          maxLength: 500,
          showCounter: true
        });
      }
      
      // Add avatar upload if required
      if (config.includeAvatarUpload) {
        fields.push({
          type: 'file',
          name: 'avatar',
          label: config.avatarLabel,
          accept: 'image/*',
          upload: {
            maxFileSize: 2 * 1024 * 1024, // 2MB
            allowedTypes: ['image/jpeg', 'image/png', 'image/gif'],
            showPreview: true,
            imageCompression: true
          }
        });
      }
      
      // Create the profile form schema
      const profileSchema = {
        fields: fields,
        options: {
          validation: {
            validateOnBlur: true,
            validateOnSubmit: true
          },
          submission: {
            resetOnSuccess: false,
            showSuccessMessage: true
          }
        }
      };
      
      // Create form using the forms.utils module
      const form = CarFuse.forms.utils.createForm(profileSchema, {
        formId: config.formId,
        formAction: config.formAction,
        formMethod: config.formMethod,
        submitButtonText: config.submitButtonText
      });
      
      return form;
    },
    
    /**
     * Create a search form
     * @param {Object} options Form options
     * @returns {HTMLFormElement} The generated form
     */
    createSearchForm(options = {}) {
      const config = {
        formId: 'search-form',
        formAction: options.action || '/search',
        formMethod: 'GET',
        submitButtonText: options.submitText || 'Search',
        advancedSearch: options.advancedSearch || false,
        placeholder: options.placeholder || 'Search...',
        searchLabel: options.searchLabel || 'Search',
        ...options
      };
      
      // Basic search field
      const fields = [
        {
          type: 'text',
          name: 'q',
          label: config.advancedSearch ? config.searchLabel : '',
          placeholder: config.placeholder,
          inputClass: 'w-full',
          clearable: true
        }
      ];
      
      // Add advanced search fields if required
      if (config.advancedSearch && options.filters) {
        options.filters.forEach(filter => {
          fields.push({
            type: filter.type || 'text',
            name: filter.name,
            label: filter.label,
            placeholder: filter.placeholder,
            options: filter.options,
            inputClass: 'w-full'
          });
        });
      }
      
      // Create the search form schema
      const searchSchema = {
        fields: fields
      };
      
      // Create form using the forms.utils module
      const form = CarFuse.forms.utils.createForm(searchSchema, {
        formId: config.formId,
        formAction: config.formAction,
        formMethod: config.formMethod,
        submitButtonText: config.submitButtonText
      });
      
      // If it's a simple search form, make it inline
      if (!config.advancedSearch) {
        form.classList.add('flex');
        
        // Adjust layout
        const inputGroup = form.querySelector('.form-group');
        inputGroup.classList.add('flex-grow');
        
        const buttonsContainer = form.querySelector('.cf-form-buttons');
        buttonsContainer.classList.remove('justify-end', 'mt-6');
        buttonsContainer.classList.add('ml-2');
        
        // Remove label if it exists
        const label = form.querySelector('label');
        if (label) {
          label.remove();
        }
      }
      
      return form;
    }
  };
  
  // Register with CarFuse
  if (!CarFuse.forms) CarFuse.forms = {};
  CarFuse.forms.templates = templates;
  
  // Export to window in case CarFuse is not available
  if (!window.CarFuse) {
    window.CarFuseForms = window.CarFuseForms || {};
    window.CarFuseForms.templates = templates;
  }
})();
