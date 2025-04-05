/**
 * CarFuse Forms - Utilities Module
 * Helper functions for form handling
 */

(function() {
  // Namespaces
  const CarFuse = window.CarFuse || {};
  if (!CarFuse.forms) CarFuse.forms = {};
  
  // Form utilities
  const utils = {
    /**
     * Serialize form data to JSON
     * @param {HTMLFormElement|string} form Form element or selector
     * @param {boolean} flattenArrays Whether to flatten arrays to comma-separated strings
     * @returns {Object} Form data as JSON object
     */
    serializeForm(form, flattenArrays = false) {
      const formElement = typeof form === 'string' 
        ? document.querySelector(form) 
        : form;
        
      if (!formElement || !(formElement instanceof HTMLFormElement)) {
        throw new Error('Invalid form element');
      }
      
      const formData = new FormData(formElement);
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
      
      // Flatten arrays if requested
      if (flattenArrays) {
        Object.keys(json).forEach(key => {
          if (Array.isArray(json[key])) {
            json[key] = json[key].join(',');
          }
        });
      }
      
      return json;
    },
    
    /**
     * Fill form with data
     * @param {HTMLFormElement|string} form Form element or selector
     * @param {Object} data Data to fill form with
     * @param {Object} options Options for filling the form
     * @returns {HTMLFormElement} The form element
     */
    fillForm(form, data, options = {}) {
      const formElement = typeof form === 'string' 
        ? document.querySelector(form) 
        : form;
        
      if (!formElement || !(formElement instanceof HTMLFormElement)) {
        throw new Error('Invalid form element');
      }
      
      const defaults = {
        triggerEvents: true, // Trigger input/change events
        clearFirst: false,   // Clear form before filling
        ignoreEmpty: true,   // Ignore empty/null values
        prefixPath: ''       // Prefix for form field names when using nested data
      };
      
      const settings = { ...defaults, ...options };
      
      // Clear form if requested
      if (settings.clearFirst) {
        this.clearForm(formElement);
      }
      
      // Helper for handling nested paths
      const processObject = (obj, path = '') => {
        for (const [key, value] of Object.entries(obj)) {
          const fieldPath = path ? `${path}[${key}]` : key;
          
          // Skip if value is null/undefined and ignoreEmpty is true
          if (settings.ignoreEmpty && (value === null || value === undefined)) {
            continue;
          }
          
          // If value is an object (and not a File), process recursively
          if (value !== null && typeof value === 'object' && !(value instanceof File) && !Array.isArray(value)) {
            processObject(value, fieldPath);
          } else {
            this._setFieldValue(formElement, fieldPath, value, settings.triggerEvents);
          }
        }
      };
      
      // Process data with optional prefix path
      processObject(data, settings.prefixPath);
      
      return formElement;
    },
    
    /**
     * Clear form data
     * @param {HTMLFormElement|string} form Form element or selector
     * @param {boolean} triggerEvents Whether to trigger input/change events
     * @returns {HTMLFormElement} The form element
     */
    clearForm(form, triggerEvents = true) {
      const formElement = typeof form === 'string' 
        ? document.querySelector(form) 
        : form;
        
      if (!formElement || !(formElement instanceof HTMLFormElement)) {
        throw new Error('Invalid form element');
      }
      
      // Get all form controls
      const inputs = formElement.querySelectorAll('input, select, textarea');
      
      inputs.forEach(input => {
        switch (input.type) {
          case 'checkbox':
          case 'radio':
            input.checked = false;
            break;
            
          case 'select-one':
          case 'select-multiple':
            input.selectedIndex = -1;
            break;
            
          case 'file':
            input.value = '';
            
            // If using CarFuse file uploader, clear it
            if (CarFuse.forms.uploads && input.dataset.cfUpload !== undefined) {
              // Find uploader instance from data attribute
              const uploaderId = input.dataset.cfUploaderId;
              if (uploaderId && CarFuse.forms.uploads.instances && CarFuse.forms.uploads.instances[uploaderId]) {
                CarFuse.forms.uploads.instances[uploaderId].clear();
              }
            }
            break;
            
          default:
            input.value = '';
        }
        
        // Trigger events if requested
        if (triggerEvents) {
          input.dispatchEvent(new Event('input', { bubbles: true }));
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
      
      return formElement;
    },
    
    /**
     * Create form from JSON schema
     * @param {Object} schema Form schema
     * @param {Object} options Form generation options
     * @returns {HTMLFormElement} Generated form element
     */
    createForm(schema, options = {}) {
      const defaults = {
        theme: 'default',
        formClass: 'cf-form',
        submitButtonText: 'Submit',
        cancelButtonText: 'Cancel',
        showCancel: false,
        formId: '',
        formMethod: 'POST',
        formAction: '',
        fieldDefaults: {}
      };
      
      const settings = { ...defaults, ...options };
      
      // Create form element
      const form = document.createElement('form');
      form.className = settings.formClass;
      form.setAttribute('data-cf-form', '');
      
      if (settings.formId) {
        form.id = settings.formId;
      }
      
      if (settings.formMethod) {
        form.method = settings.formMethod;
      }
      
      if (settings.formAction) {
        form.action = settings.formAction;
      }
      
      // Add schema fields
      if (schema.fields && Array.isArray(schema.fields)) {
        schema.fields.forEach(field => {
          const fieldElement = this._createFormField(field, settings);
          form.appendChild(fieldElement);
        });
      }
      
      // Add buttons container
      const buttonsContainer = document.createElement('div');
      buttonsContainer.className = 'cf-form-buttons flex justify-end space-x-3 mt-6';
      
      // Add cancel button if requested
      if (settings.showCancel) {
        const cancelButton = document.createElement('button');
        cancelButton.type = 'button';
        cancelButton.className = 'btn btn-secondary';
        cancelButton.textContent = settings.cancelButtonText;
        cancelButton.addEventListener('click', () => {
          form.dispatchEvent(new CustomEvent('cf:form:cancel', { bubbles: true }));
        });
        buttonsContainer.appendChild(cancelButton);
      }
      
      // Add submit button
      const submitButton = document.createElement('button');
      submitButton.type = 'submit';
      submitButton.className = 'btn btn-primary';
      submitButton.innerHTML = `<span class="btn-text">${settings.submitButtonText}</span>`;
      buttonsContainer.appendChild(submitButton);
      
      form.appendChild(buttonsContainer);
      
      // Initialize form with CarFuse forms system
      if (CarFuse.forms && CarFuse.forms.create) {
        CarFuse.forms.create(form, schema.options || {});
      }
      
      return form;
    },
    
    /**
     * Add dynamic inputs to form based on a template
     * @param {HTMLElement} container Container for dynamic inputs
     * @param {HTMLElement|string} template Template element or HTML string
     * @param {number} initialCount Initial number of inputs
     * @param {Object} options Options for dynamic inputs
     */
    addDynamicInputs(container, template, initialCount = 1, options = {}) {
      const containerElement = typeof container === 'string' 
        ? document.querySelector(container) 
        : container;
        
      if (!containerElement) {
        throw new Error('Invalid container element');
      }
      
      const defaults = {
        maxItems: 10,
        minItems: 0,
        addButtonText: 'Add Item',
        removeButtonText: '×',
        itemClass: 'dynamic-item',
        addButtonClass: 'btn btn-secondary btn-sm mt-2',
        removeButtonClass: 'btn btn-danger btn-sm ml-2',
        reindexNames: true,
        onChange: null
      };
      
      const settings = { ...defaults, ...options };
      
      // Create template element from string if needed
      let templateElement;
      if (typeof template === 'string') {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.trim();
        templateElement = wrapper.firstChild;
      } else {
        templateElement = template;
      }
      
      // Hide the template
      if (templateElement.parentElement) {
        templateElement.remove();
      }
      
      // Data to keep track of items
      containerElement.dynamicInputs = {
        template: templateElement,
        items: [],
        settings: settings,
        
        addItem(data = {}) {
          // Check max items
          if (this.items.length >= this.settings.maxItems) {
            return null;
          }
          
          // Clone template
          const item = this.template.cloneNode(true);
          item.classList.add(this.settings.itemClass);
          
          // Add remove button
          const removeBtn = document.createElement('button');
          removeBtn.type = 'button';
          removeBtn.className = this.settings.removeButtonClass;
          removeBtn.innerHTML = this.settings.removeButtonText;
          removeBtn.addEventListener('click', () => this.removeItem(item));
          
          // Find a suitable location for the remove button within the item
          const buttonContainer = item.querySelector('[data-remove-button-container]') || item;
          buttonContainer.appendChild(removeBtn);
          
          // Fill with data if provided
          if (Object.keys(data).length > 0) {
            const inputs = item.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
              if (input.name && data[input.name]) {
                input.value = data[input.name];
              }
            });
          }
          
          // Add to container
          containerElement.appendChild(item);
          
          // Add to items array
          this.items.push(item);
          
          // Reindex if needed
          if (this.settings.reindexNames) {
            this.reindexItems();
          }
          
          // Update add button state
          this.updateAddButtonState();
          
          // Trigger change event
          this.triggerChange();
          
          // Initialize form components if CarFuse forms is available
          if (window.CarFuse && window.CarFuse.forms && window.CarFuse.forms.components) {
            window.CarFuse.forms.components._initializeComponents(item);
          }
          
          return item;
        },
        
        removeItem(item) {
          // Check min items
          if (this.items.length <= this.settings.minItems) {
            return false;
          }
          
          // Remove from DOM
          item.remove();
          
          // Remove from items array
          const index = this.items.indexOf(item);
          if (index !== -1) {
            this.items.splice(index, 1);
          }
          
          // Reindex if needed
          if (this.settings.reindexNames) {
            this.reindexItems();
          }
          
          // Update add button state
          this.updateAddButtonState();
          
          // Trigger change event
          this.triggerChange();
          
          return true;
        },
        
        reindexItems() {
          this.items.forEach((item, index) => {
            const inputs = item.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
              if (input.name) {
                // Replace array index in name
                input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
                
                // Update ID if it has a consistent pattern with the name
                if (input.id && input.id.match(/^(.+)_\d+$/)) {
                  input.id = input.id.replace(/_\d+$/, `_${index}`);
                  
                  // Update associated label if exists
                  const label = item.querySelector(`label[for="${input.id}"]`);
                  if (label) {
                    label.htmlFor = input.id;
                  }
                }
              }
            });
          });
        },
        
        updateAddButtonState() {
          const addBtn = containerElement.nextElementSibling;
          if (addBtn && addBtn.classList.contains(this.settings.addButtonClass)) {
            addBtn.disabled = this.items.length >= this.settings.maxItems;
          }
        },
        
        triggerChange() {
          if (typeof this.settings.onChange === 'function') {
            this.settings.onChange(this.items);
          }
          
          // Dispatch custom event
          containerElement.dispatchEvent(new CustomEvent('cf:dynamicInputs:change', {
            bubbles: true,
            detail: { items: this.items }
          }));
        },
        
        getValues() {
          const values = [];
          
          this.items.forEach(item => {
            const itemData = {};
            const inputs = item.querySelectorAll('input, select, textarea');
            
            inputs.forEach(input => {
              if (input.name) {
                // Extract field name without array notation
                const fieldName = input.name.replace(/\[\d+\]\[([^\]]+)\]/, '$1');
                
                switch (input.type) {
                  case 'checkbox':
                    itemData[fieldName] = input.checked;
                    break;
                  case 'radio':
                    if (input.checked) {
                      itemData[fieldName] = input.value;
                    }
                    break;
                  default:
                    itemData[fieldName] = input.value;
                }
              }
            });
            
            values.push(itemData);
          });
          
          return values;
        }
      };
      
      // Add initial items
      for (let i = 0; i < initialCount; i++) {
        containerElement.dynamicInputs.addItem();
      }
      
      // Create Add button
      const addButton = document.createElement('button');
      addButton.type = 'button';
      addButton.className = settings.addButtonClass;
      addButton.textContent = settings.addButtonText;
      addButton.addEventListener('click', () => containerElement.dynamicInputs.addItem());
      
      // Add button after container
      containerElement.parentNode.insertBefore(addButton, containerElement.nextSibling);
      
      // Set initial state of add button
      containerElement.dynamicInputs.updateAddButtonState();
      
      return containerElement.dynamicInputs;
    },
    
    /**
     * Create a schema-based field group
     * @param {Object} field Field schema
     * @param {Object} options Options
     * @returns {HTMLElement} Field group element
     * @private
     */
    _createFormField(field, options) {
      // Combine with defaults
      const fieldData = { ...options.fieldDefaults, ...field };
      
      // Create form group
      const formGroup = document.createElement('div');
      formGroup.className = 'form-group';
      
      // Check CarFuse components availability
      const useComponents = CarFuse.forms && CarFuse.forms.components && CarFuse.forms.components.create;
      
      // Add label if needed
      if (fieldData.label) {
        const label = document.createElement('label');
        label.className = 'form-label';
        label.textContent = fieldData.label;
        
        if (fieldData.required) {
          label.classList.add('required');
        }
        
        formGroup.appendChild(label);
      }
      
      // Create input based on field type
      let input;
      
      if (useComponents) {
        // Use CarFuse components if available
        switch (fieldData.type) {
          case 'select':
            input = CarFuse.forms.components.create('select', {
              name: fieldData.name,
              id: fieldData.id || `field-${fieldData.name}`,
              required: fieldData.required,
              disabled: fieldData.disabled,
              options: fieldData.options || [],
              placeholder: fieldData.placeholder,
              validate: fieldData.validation,
              className: fieldData.inputClass,
              searchable: fieldData.searchable
            });
            break;
            
          case 'textarea':
            input = CarFuse.forms.components.create('textarea', {
              name: fieldData.name,
              id: fieldData.id || `field-${fieldData.name}`,
              value: fieldData.value,
              rows: fieldData.rows || 3,
              required: fieldData.required,
              disabled: fieldData.disabled,
              placeholder: fieldData.placeholder,
              validate: fieldData.validation,
              className: fieldData.inputClass,
              autoResize: fieldData.autoResize
            });
            break;
            
          case 'checkbox':
            input = CarFuse.forms.components.create('checkbox', {
              name: fieldData.name,
              id: fieldData.id || `field-${fieldData.name}`,
              checked: fieldData.checked,
              required: fieldData.required,
              disabled: fieldData.disabled,
              validate: fieldData.validation,
              className: fieldData.inputClass,
              custom: fieldData.custom,
              label: fieldData.checkboxLabel
            });
            break;
            
          case 'radio':
            // For radio buttons, create a container
            const radioContainer = document.createElement('div');
            radioContainer.className = 'cf-radio-group space-y-2';
            
            if (fieldData.options && Array.isArray(fieldData.options)) {
              fieldData.options.forEach((option, index) => {
                const radio = CarFuse.forms.components.create('radio', {
                  name: fieldData.name,
                  id: `${fieldData.name}_${index}`,
                  value: option.value,
                  checked: fieldData.value === option.value,
                  required: fieldData.required,
                  disabled: fieldData.disabled || option.disabled,
                  custom: fieldData.custom,
                  label: option.label
                });
                
                radioContainer.appendChild(radio);
              });
            }
            
            input = radioContainer;
            break;
            
          case 'file':
            input = CarFuse.forms.components.create('file', {
              name: fieldData.name,
              id: fieldData.id || `field-${fieldData.name}`,
              required: fieldData.required,
              disabled: fieldData.disabled,
              validate: fieldData.validation,
              className: fieldData.inputClass,
              accept: fieldData.accept,
              multiple: fieldData.multiple
            });
            
            // Add file upload functionality if enabled
            if (CarFuse.forms.uploads && fieldData.upload) {
              const uploadOptions = typeof fieldData.upload === 'object' ? fieldData.upload : {};
              setTimeout(() => {
                CarFuse.forms.uploads.createUploader(input, uploadOptions);
              }, 0);
            }
            break;
            
          default: // Default to text input
            input = CarFuse.forms.components.create('text-input', {
              type: fieldData.type || 'text',
              name: fieldData.name,
              id: fieldData.id || `field-${fieldData.name}`,
              value: fieldData.value,
              required: fieldData.required,
              disabled: fieldData.disabled,
              placeholder: fieldData.placeholder,
              validate: fieldData.validation,
              className: fieldData.inputClass,
              clearable: fieldData.clearable,
              showCounter: fieldData.showCounter,
              maxlength: fieldData.maxLength
            });
        }
      } else {
        // Fallback to standard HTML elements
        switch (fieldData.type) {
          case 'select':
            input = document.createElement('select');
            input.name = fieldData.name;
            input.id = fieldData.id || `field-${fieldData.name}`;
            input.required = !!fieldData.required;
            input.disabled = !!fieldData.disabled;
            input.className = `form-select ${fieldData.inputClass || ''}`;
            
            if (fieldData.placeholder) {
              const placeholderOption = document.createElement('option');
              placeholderOption.value = '';
              placeholderOption.textContent = fieldData.placeholder;
              placeholderOption.disabled = true;
              placeholderOption.selected = true;
              input.appendChild(placeholderOption);
            }
            
            if (fieldData.options && Array.isArray(fieldData.options)) {
              fieldData.options.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option.value;
                optionElement.textContent = option.label;
                optionElement.disabled = !!option.disabled;
                optionElement.selected = fieldData.value === option.value;
                input.appendChild(optionElement);
              });
            }
            break;
            
          case 'textarea':
            input = document.createElement('textarea');
            input.name = fieldData.name;
            input.id = fieldData.id || `field-${fieldData.name}`;
            input.value = fieldData.value || '';
            input.rows = fieldData.rows || 3;
            input.required = !!fieldData.required;
            input.disabled = !!fieldData.disabled;
            input.placeholder = fieldData.placeholder || '';
            input.className = `form-input ${fieldData.inputClass || ''}`;
            
            if (fieldData.maxLength) {
              input.maxLength = fieldData.maxLength;
            }
            break;
            
          case 'checkbox':
            input = document.createElement('input');
            input.type = 'checkbox';
            input.name = fieldData.name;
            input.id = fieldData.id || `field-${fieldData.name}`;
            input.checked = !!fieldData.checked;
            input.required = !!fieldData.required;
            input.disabled = !!fieldData.disabled;
            input.className = `form-checkbox ${fieldData.inputClass || ''}`;
            
            if (fieldData.checkboxLabel) {
              const checkboxContainer = document.createElement('div');
              checkboxContainer.className = 'flex items-center';
              
              const checkboxLabel = document.createElement('label');
              checkboxLabel.htmlFor = input.id;
              checkboxLabel.className = 'ml-2 text-sm text-gray-700';
              checkboxLabel.textContent = fieldData.checkboxLabel;
              
              checkboxContainer.appendChild(input);
              checkboxContainer.appendChild(checkboxLabel);
              
              input = checkboxContainer;
            }
            break;
            
          case 'radio':
            // For radio buttons, create a container
            const radioContainer = document.createElement('div');
            radioContainer.className = 'space-y-2';
            
            if (fieldData.options && Array.isArray(fieldData.options)) {
              fieldData.options.forEach((option, index) => {
                const radioId = `${fieldData.name}_${index}`;
                
                const radioDiv = document.createElement('div');
                radioDiv.className = 'flex items-center';
                
                const radio = document.createElement('input');
                radio.type = 'radio';
                radio.name = fieldData.name;
                radio.id = radioId;
                radio.value = option.value;
                radio.checked = fieldData.value === option.value;
                radio.required = !!fieldData.required;
                radio.disabled = !!fieldData.disabled || !!option.disabled;
                radio.className = `form-radio ${fieldData.inputClass || ''}`;
                
                const radioLabel = document.createElement('label');
                radioLabel.htmlFor = radioId;
                radioLabel.className = 'ml-2 text-sm text-gray-700';
                radioLabel.textContent = option.label;
                
                radioDiv.appendChild(radio);
                radioDiv.appendChild(radioLabel);
                radioContainer.appendChild(radioDiv);
              });
            }
            
            input = radioContainer;
            break;
            
          case 'file':
            input = document.createElement('input');
            input.type = 'file';
            input.name = fieldData.name;
            input.id = fieldData.id || `field-${fieldData.name}`;
            input.required = !!fieldData.required;
            input.disabled = !!fieldData.disabled;
            input.className = fieldData.inputClass || '';
            
            if (fieldData.accept) {
              input.accept = fieldData.accept;
            }
            
            if (fieldData.multiple) {
              input.multiple = true;
            }
            break;
            
          default: // Default to text input
            input = document.createElement('input');
            input.type = fieldData.type || 'text';
            input.name = fieldData.name;
            input.id = fieldData.id || `field-${fieldData.name}`;
            input.value = fieldData.value || '';
            input.required = !!fieldData.required;
            input.disabled = !!fieldData.disabled;
            input.placeholder = fieldData.placeholder || '';
            input.className = `form-input ${fieldData.inputClass || ''}`;
            
            if (fieldData.maxLength) {
              input.maxLength = fieldData.maxLength;
            }
            
            if (fieldData.min !== undefined) {
              input.min = fieldData.min;
            }
            
            if (fieldData.max !== undefined) {
              input.max = fieldData.max;
            }
        }
      }
      
      // Add validation attribute
      if (fieldData.validation && input.tagName !== 'DIV') {
        input.setAttribute('data-validate', fieldData.validation);
      }
      
      // Add input to form group
      formGroup.appendChild(input);
      
      // Add help text if needed
      if (fieldData.help) {
        const helpText = document.createElement('div');
        helpText.className = 'form-help';
        helpText.textContent = fieldData.help;
        formGroup.appendChild(helpText);
      }
      
      return formGroup;
    },
    
    /**
     * Set field value in form
     * @param {HTMLFormElement} form Form element
     * @param {string} name Field name
     * @param {*} value Field value
     * @param {boolean} triggerEvents Whether to trigger input/change events
     * @private
     */
    _setFieldValue(form, name, value, triggerEvents) {
      // Find field by name
      const fields = form.querySelectorAll(`[name="${name}"], [name="${name}[]"]`);
      
      if (fields.length === 0) return;
      
      fields.forEach(field => {
        switch (field.type) {
          case 'checkbox':
            // Handle array of values for checkboxes
            if (Array.isArray(value)) {
              field.checked = value.includes(field.value);
            } else {
              field.checked = field.value === value || field.value === 'on' && !!value;
            }
            break;
            
          case 'radio':
            field.checked = field.value === String(value);
            break;
            
          case 'select-one':
            field.value = value;
            break;
            
          case 'select-multiple':
            // Reset all options first
            Array.from(field.options).forEach(option => {
              option.selected = false;
            });
            
            // Set selected options
            if (Array.isArray(value)) {
              value.forEach(val => {
                Array.from(field.options).forEach(option => {
                  if (option.value === String(val)) {
                    option.selected = true;
                  }
                });
              });
            } else if (value !== null && value !== undefined) {
              // Handle comma-separated string
              const values = String(value).split(',');
              values.forEach(val => {
                Array.from(field.options).forEach(option => {
                  if (option.value === val.trim()) {
                    option.selected = true;
                  }
                });
              });
            }
            break;
            
          case 'file':
            // Can't set file input value due to security restrictions
            // Just clear it
            field.value = '';
            
            // If using CarFuse file uploader and has a value, try to apply it
            if (CarFuse.forms.uploads && field.dataset.cfUpload !== undefined && value) {
              // This would require server-side coordination and is a placeholder
              console.warn('Cannot directly set file input value for security reasons');
            }
            break;
            
          default:
            field.value = value !== null && value !== undefined ? value : '';
        }
        
        // Trigger events if requested
        if (triggerEvents) {
          field.dispatchEvent(new Event('input', { bubbles: true }));
          field.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
    }
  };
  
  // Register with CarFuse
  if (!CarFuse.forms) CarFuse.forms = {};
  CarFuse.forms.utils = utils;
  
  // Export to window in case CarFuse is not available
  if (!window.CarFuse) {
    window.CarFuseForms = window.CarFuseForms || {};
    window.CarFuseForms.utils = utils;
  }
})();
