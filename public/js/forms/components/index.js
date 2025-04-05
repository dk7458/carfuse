/**
 * CarFuse Forms - Components Module
 * Provides reusable form component functionality
 */

(function() {
  // Namespaces
  const CarFuse = window.CarFuse || {};
  if (!CarFuse.forms) CarFuse.forms = {};
  
  // Components module
  const components = {
    // Configuration
    config: {
      baseClass: 'cf-form',
      componentPrefix: 'cf-',
      defaultTheme: 'default'
    },
    
    // Component registry
    registry: {},
    
    /**
     * Initialize components module
     * @param {Object} options Configuration options
     * @returns {Promise} Promise that resolves when initialization is complete
     */
    init(options = {}) {
      // Apply custom options
      Object.assign(this.config, options);
      
      // Register built-in components
      this._registerBuiltInComponents();
      
      // Initialize registered components
      this._initializeComponents();
      
      return Promise.resolve(this);
    },
    
    /**
     * Register built-in form components
     * @private
     */
    _registerBuiltInComponents() {
      // Text input
      this.register('text-input', {
        selector: '[data-cf-text-input]',
        render: (element, options) => {
          this._enhanceInputElement(element, options);
        }
      });
      
      // Select input
      this.register('select', {
        selector: '[data-cf-select]',
        render: (element, options) => {
          this._enhanceSelectElement(element, options);
        }
      });
      
      // Textarea
      this.register('textarea', {
        selector: '[data-cf-textarea]',
        render: (element, options) => {
          this._enhanceTextareaElement(element, options);
        }
      });
      
      // Checkbox
      this.register('checkbox', {
        selector: '[data-cf-checkbox]',
        render: (element, options) => {
          this._enhanceCheckboxElement(element, options);
        }
      });
      
      // Radio buttons
      this.register('radio', {
        selector: '[data-cf-radio]',
        render: (element, options) => {
          this._enhanceRadioElement(element, options);
        }
      });
      
      // File input
      this.register('file', {
        selector: '[data-cf-file]',
        render: (element, options) => {
          this._enhanceFileElement(element, options);
        }
      });
      
      // Date picker
      this.register('date', {
        selector: '[data-cf-date]',
        render: (element, options) => {
          this._enhanceDateElement(element, options);
        }
      });
      
      // Form group (label + input + help text)
      this.register('form-group', {
        selector: '[data-cf-form-group]',
        render: (element, options) => {
          this._enhanceFormGroup(element, options);
        }
      });
    },
    
    /**
     * Initialize all components on the page
     * @private
     */
    _initializeComponents() {
      // Process each registered component
      Object.entries(this.registry).forEach(([name, component]) => {
        const elements = document.querySelectorAll(component.selector);
        elements.forEach(element => {
          // Parse options from data attributes
          const options = this._parseDataOptions(element, name);
          
          // Render component
          component.render(element, options);
          
          // Mark as initialized
          element.setAttribute('data-cf-initialized', 'true');
        });
      });
    },
    
    /**
     * Register a new component
     * @param {string} name Component name
     * @param {Object} component Component definition
     * @returns {Object} This components module for chaining
     */
    register(name, component) {
      if (!component.selector || typeof component.render !== 'function') {
        console.error(`Invalid component definition for "${name}"`);
        return this;
      }
      
      this.registry[name] = component;
      return this;
    },
    
    /**
     * Create a form component
     * @param {string} type Component type
     * @param {Object} options Component options
     * @returns {HTMLElement} Created component
     */
    create(type, options = {}) {
      const component = this.registry[type];
      if (!component) {
        throw new Error(`Component type "${type}" not registered`);
      }
      
      // Create base element based on type
      let element;
      
      switch (type) {
        case 'text-input':
          element = document.createElement('input');
          element.type = options.type || 'text';
          element.setAttribute('data-cf-text-input', '');
          break;
          
        case 'select':
          element = document.createElement('select');
          element.setAttribute('data-cf-select', '');
          
          // Add options if provided
          if (options.options) {
            options.options.forEach(opt => {
              const option = document.createElement('option');
              option.value = opt.value;
              option.textContent = opt.label;
              if (opt.selected) option.selected = true;
              element.appendChild(option);
            });
          }
          break;
          
        case 'textarea':
          element = document.createElement('textarea');
          element.setAttribute('data-cf-textarea', '');
          if (options.rows) element.rows = options.rows;
          break;
          
        case 'checkbox':
          element = document.createElement('input');
          element.type = 'checkbox';
          element.setAttribute('data-cf-checkbox', '');
          if (options.checked) element.checked = true;
          break;
          
        case 'radio':
          element = document.createElement('input');
          element.type = 'radio';
          element.setAttribute('data-cf-radio', '');
          if (options.checked) element.checked = true;
          break;
          
        case 'file':
          element = document.createElement('input');
          element.type = 'file';
          element.setAttribute('data-cf-file', '');
          if (options.accept) element.accept = options.accept;
          if (options.multiple) element.multiple = true;
          break;
          
        case 'date':
          element = document.createElement('input');
          element.type = 'date';
          element.setAttribute('data-cf-date', '');
          break;
          
        case 'form-group':
          element = document.createElement('div');
          element.setAttribute('data-cf-form-group', '');
          element.className = 'form-group';
          
          // Create label if needed
          if (options.label) {
            const label = document.createElement('label');
            label.className = 'form-label';
            label.textContent = options.label;
            element.appendChild(label);
          }
          
          // Create input if needed
          if (options.input) {
            const input = this.create(options.input.type, options.input.options);
            element.appendChild(input);
          }
          
          // Create help text if needed
          if (options.help) {
            const help = document.createElement('div');
            help.className = 'form-help';
            help.textContent = options.help;
            element.appendChild(help);
          }
          break;
          
        default:
          throw new Error(`Unsupported component type: ${type}`);
      }
      
      // Set common attributes
      if (options.name) element.name = options.name;
      if (options.id) element.id = options.id;
      if (options.value) element.value = options.value;
      if (options.placeholder) element.placeholder = options.placeholder;
      if (options.required) element.required = true;
      if (options.disabled) element.disabled = true;
      if (options.readOnly) element.readOnly = true;
      if (options.className) element.className += ' ' + options.className;
      if (options.validate) element.setAttribute('data-validate', options.validate);
      
      // Set data attributes
      if (options.data) {
        Object.entries(options.data).forEach(([key, value]) => {
          element.dataset[key] = value;
        });
      }
      
      // Add event listeners
      if (options.events) {
        Object.entries(options.events).forEach(([event, handler]) => {
          element.addEventListener(event, handler);
        });
      }
      
      // Initialize the component
      const componentOptions = { ...options };
      component.render(element, componentOptions);
      element.setAttribute('data-cf-initialized', 'true');
      
      return element;
    },
    
    /**
     * Enhance existing input element with additional functionality
     * @param {HTMLElement} element Input element
     * @param {Object} options Enhancement options
     * @private
     */
    _enhanceInputElement(element, options) {
      // Add base class
      element.classList.add('form-input');
      
      // Set up event listeners
      element.addEventListener('focus', () => {
        this._handleInputFocus(element);
      });
      
      element.addEventListener('blur', () => {
        this._handleInputBlur(element);
      });
      
      // Add clear button if required
      if (options.clearable) {
        this._makeClearable(element);
      }
      
      // Add character counter if maxlength is set
      if (element.maxLength > 0 && options.showCounter !== false) {
        this._addCharacterCounter(element);
      }
    },
    
    /**
     * Enhance existing select element with additional functionality
     * @param {HTMLElement} element Select element
     * @param {Object} options Enhancement options
     * @private
     */
    _enhanceSelectElement(element, options) {
      // Add base class
      element.classList.add('form-select');
      
      // Add search functionality for larger selects
      if (options.searchable && element.options.length > 10) {
        this._makeSelectSearchable(element);
      }
    },
    
    /**
     * Enhance existing textarea element
     * @param {HTMLElement} element Textarea element
     * @param {Object} options Enhancement options
     * @private
     */
    _enhanceTextareaElement(element, options) {
      // Add base class
      element.classList.add('form-input');
      
      // Make auto-resizable if requested
      if (options.autoResize) {
        this._makeTextareaAutoResize(element);
      }
      
      // Add character counter if maxlength is set
      if (element.maxLength > 0 && options.showCounter !== false) {
        this._addCharacterCounter(element);
      }
    },
    
    /**
     * Enhance existing checkbox element
     * @param {HTMLElement} element Checkbox element
     * @param {Object} options Enhancement options
     * @private
     */
    _enhanceCheckboxElement(element, options) {
      // Add base class
      element.classList.add('form-checkbox');
      
      // Create custom checkbox design if requested
      if (options.custom) {
        this._makeCustomCheckbox(element);
      }
    },
    
    /**
     * Enhance existing radio element
     * @param {HTMLElement} element Radio element
     * @param {Object} options Enhancement options
     * @private
     */
    _enhanceRadioElement(element, options) {
      // Add base class
      element.classList.add('form-radio');
      
      // Create custom radio design if requested
      if (options.custom) {
        this._makeCustomRadio(element);
      }
    },
    
    /**
     * Enhance existing file input element
     * @param {HTMLElement} element File input element
     * @param {Object} options Enhancement options
     * @private
     */
    _enhanceFileElement(element, options) {
      // Hide the original input
      element.classList.add('sr-only');
      
      // Create custom file input UI
      const container = document.createElement('div');
      container.className = 'cf-file-input';
      
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'cf-file-button btn btn-secondary';
      button.textContent = options.buttonText || 'Choose File';
      
      const display = document.createElement('div');
      display.className = 'cf-file-name';
      display.textContent = options.placeholder || 'No file chosen';
      
      container.appendChild(button);
      container.appendChild(display);
      
      // Insert after the original input
      element.parentNode.insertBefore(container, element.nextSibling);
      
      // Click handler
      button.addEventListener('click', () => {
        element.click();
      });
      
      // Change handler
      element.addEventListener('change', () => {
        if (element.files.length === 0) {
          display.textContent = options.placeholder || 'No file chosen';
          return;
        }
        
        if (element.files.length === 1) {
          display.textContent = element.files[0].name;
        } else {
          display.textContent = `${element.files.length} files selected`;
        }
        
        // Dispatch custom event
        element.dispatchEvent(new CustomEvent('cf:file:change', {
          bubbles: true,
          detail: { files: element.files }
        }));
      });
    },
    
    /**
     * Enhance existing date input element
     * @param {HTMLElement} element Date input element
     * @param {Object} options Enhancement options
     * @private
     */
    _enhanceDateElement(element, options) {
      // Add base class
      element.classList.add('form-input');
      
      // Check if browser supports date input natively
      const test = document.createElement('input');
      test.type = 'date';
      const isNativeSupport = test.type === 'date';
      
      // If browser doesn't support date input or forcing custom
      if (!isNativeSupport || options.useCustom) {
        // Use fallback date picker
        this._useCustomDatePicker(element, options);
      }
    },
    
    /**
     * Enhance form group container
     * @param {HTMLElement} element Form group element
     * @param {Object} options Enhancement options
     * @private
     */
    _enhanceFormGroup(element, options) {
      // Make sure element has form-group class
      element.classList.add('form-group');
      
      // Find label, input and help text elements
      const label = element.querySelector('label');
      const input = element.querySelector('input, select, textarea');
      const helpText = element.querySelector('.form-help');
      
      // Connect label with input if needed
      if (label && input && !input.id) {
        input.id = `cf-input-${this._generateId()}`;
        label.htmlFor = input.id;
      }
      
      // Add floating label if requested
      if (options.floatingLabel && label && input) {
        this._makeFloatingLabel(element, label, input);
      }
      
      // Connect help text with input for accessibility
      if (helpText && input) {
        const helpId = `cf-help-${this._generateId()}`;
        helpText.id = helpId;
        
        // Add to aria-describedby, preserving existing values
        const describedBy = input.getAttribute('aria-describedby');
        input.setAttribute('aria-describedby', 
          describedBy ? `${describedBy} ${helpId}` : helpId);
      }
    },
    
    /**
     * Create custom date picker for browsers that don't support it
     * @param {HTMLElement} element Input element
     * @param {Object} options Date picker options
     * @private
     */
    _useCustomDatePicker(element, options) {
      // This is a simplified example that would be expanded with a real date picker
      // You would typically use a library like flatpickr or create your own date picker
      
      // For now, we'll just use a text input with pattern
      element.type = 'text';
      element.placeholder = options.placeholder || 'YYYY-MM-DD';
      element.pattern = '[0-9]{4}-[0-9]{2}-[0-9]{2}';
      
      // Add date icon
      const wrapper = document.createElement('div');
      wrapper.className = 'cf-date-wrapper relative';
      
      element.parentNode.insertBefore(wrapper, element);
      wrapper.appendChild(element);
      
      const icon = document.createElement('span');
      icon.className = 'cf-date-icon absolute right-3 top-1/2 transform -translate-y-1/2';
      icon.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
      `;
      
      wrapper.appendChild(icon);
    },
    
    /**
     * Make textarea auto-resize as user types
     * @param {HTMLElement} element Textarea element
     * @private
     */
    _makeTextareaAutoResize(element) {
      // Set initial height
      element.style.height = 'auto';
      element.style.height = `${element.scrollHeight}px`;
      
      // Function to resize textarea
      const resize = () => {
        element.style.height = 'auto';
        element.style.height = `${element.scrollHeight}px`;
      };
      
      // Listen for input events
      element.addEventListener('input', resize);
      
      // Initial resize
      resize();
    },
    
    /**
     * Add character counter to input or textarea
     * @param {HTMLElement} element Input or textarea element
     * @private
     */
    _addCharacterCounter(element) {
      // Create counter element
      const counter = document.createElement('div');
      counter.className = 'cf-char-counter text-xs text-gray-500 mt-1 text-right';
      
      // Update counter text
      const updateCounter = () => {
        const current = element.value.length;
        const max = element.maxLength;
        counter.textContent = `${current}/${max}`;
        
        // Add warning class when getting close to limit
        if (current >= max * 0.9) {
          counter.classList.add('text-yellow-500');
        } else {
          counter.classList.remove('text-yellow-500');
        }
      };
      
      // Listen for input events
      element.addEventListener('input', updateCounter);
      
      // Insert counter after element
      element.parentNode.insertBefore(counter, element.nextSibling);
      
      // Initial update
      updateCounter();
    },
    
    /**
     * Make input clearable with a click
     * @param {HTMLElement} element Input element
     * @private
     */
    _makeClearable(element) {
      // Create wrapper
      const wrapper = document.createElement('div');
      wrapper.className = 'cf-clearable-input relative';
      
      // Insert wrapper
      element.parentNode.insertBefore(wrapper, element);
      wrapper.appendChild(element);
      
      // Create clear button
      const clearButton = document.createElement('button');
      clearButton.type = 'button';
      clearButton.className = 'cf-clear-button absolute right-3 top-1/2 transform -translate-y-1/2 hidden';
      clearButton.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      `;
      
      // Insert clear button
      wrapper.appendChild(clearButton);
      
      // Show/hide clear button
      const toggleClearButton = () => {
        if (element.value) {
          clearButton.classList.remove('hidden');
        } else {
          clearButton.classList.add('hidden');
        }
      };
      
      // Clear input when button is clicked
      clearButton.addEventListener('click', () => {
        element.value = '';
        toggleClearButton();
        element.focus();
        
        // Dispatch input and change events
        element.dispatchEvent(new Event('input', { bubbles: true }));
        element.dispatchEvent(new Event('change', { bubbles: true }));
      });
      
      // Toggle button visibility on input
      element.addEventListener('input', toggleClearButton);
      
      // Initial toggle
      toggleClearButton();
    },
    
    /**
     * Create floating labels
     * @param {HTMLElement} formGroup Form group element
     * @param {HTMLElement} label Label element
     * @param {HTMLElement} input Input element
     * @private
     */
    _makeFloatingLabel(formGroup, label, input) {
      // Add floating label classes
      formGroup.classList.add('cf-floating-label', 'relative');
      label.classList.add('cf-floating-label-text', 'absolute', 'transition-all', 'duration-200');
      
      // Position the label
      const updateLabelPosition = () => {
        if (input.value || document.activeElement === input) {
          label.classList.add('cf-floating-label-active');
        } else {
          label.classList.remove('cf-floating-label-active');
        }
      };
      
      // Listen for focus and blur events
      input.addEventListener('focus', updateLabelPosition);
      input.addEventListener('blur', updateLabelPosition);
      input.addEventListener('input', updateLabelPosition);
      
      // Initial position
      updateLabelPosition();
    },
    
    /**
     * Make select element searchable
     * @param {HTMLElement} element Select element
     * @private
     */
    _makeSelectSearchable(element) {
      // Create a wrapper
      const wrapper = document.createElement('div');
      wrapper.className = 'cf-select-searchable relative';
      
      // Insert wrapper
      element.parentNode.insertBefore(wrapper, element);
      wrapper.appendChild(element);
      
      // Create search input
      const searchInput = document.createElement('input');
      searchInput.type = 'text';
      searchInput.className = 'cf-select-search form-input w-full';
      searchInput.placeholder = 'Search...';
      
      // Create dropdown container
      const dropdown = document.createElement('div');
      dropdown.className = 'cf-select-dropdown absolute w-full bg-white border border-gray-300 rounded-md shadow-lg z-10 hidden overflow-y-auto max-h-60';
      
      // Insert elements
      wrapper.appendChild(searchInput);
      wrapper.appendChild(dropdown);
      
      // Hide original select
      element.classList.add('hidden');
      
      // Build dropdown options
      const buildDropdown = () => {
        dropdown.innerHTML = '';
        const options = Array.from(element.options);
        
        options.forEach(option => {
          const item = document.createElement('div');
          item.className = 'cf-select-option p-2 hover:bg-gray-100 cursor-pointer';
          item.textContent = option.textContent;
          item.dataset.value = option.value;
          
          if (option.selected) {
            item.classList.add('bg-blue-50');
            searchInput.value = option.textContent;
          }
          
          item.addEventListener('click', () => {
            // Update select value
            element.value = option.value;
            searchInput.value = option.textContent;
            
            // Dispatch change event
            element.dispatchEvent(new Event('change', { bubbles: true }));
            
            // Close dropdown
            hideDropdown();
          });
          
          dropdown.appendChild(item);
        });
      };
      
      // Filter options based on search
      const filterOptions = () => {
        const filter = searchInput.value.toLowerCase();
        const options = dropdown.querySelectorAll('.cf-select-option');
        
        options.forEach(option => {
          const text = option.textContent.toLowerCase();
          if (text.includes(filter)) {
            option.style.display = '';
          } else {
            option.style.display = 'none';
          }
        });
      };
      
      // Show/hide dropdown
      const showDropdown = () => {
        buildDropdown();
        dropdown.classList.remove('hidden');
      };
      
      const hideDropdown = () => {
        dropdown.classList.add('hidden');
      };
      
      // Event listeners
      searchInput.addEventListener('focus', showDropdown);
      searchInput.addEventListener('input', filterOptions);
      
      document.addEventListener('click', e => {
        if (!wrapper.contains(e.target)) {
          hideDropdown();
        }
      });
      
      // Initial build
      buildDropdown();
    },
    
    /**
     * Create custom checkbox design
     * @param {HTMLElement} element Checkbox element
     * @private
     */
    _makeCustomCheckbox(element) {
      // Create wrapper
      const wrapper = document.createElement('div');
      wrapper.className = 'cf-custom-checkbox inline-flex items-center';
      
      // Insert wrapper
      element.parentNode.insertBefore(wrapper, element);
      
      // Hide original checkbox but keep it accessible
      element.classList.add('sr-only');
      
      // Create custom checkbox
      const customCheckbox = document.createElement('span');
      customCheckbox.className = 'cf-checkbox-indicator w-5 h-5 border border-gray-300 rounded flex-shrink-0 mr-2 flex items-center justify-center';
      customCheckbox.innerHTML = `
        <svg class="w-3 h-3 text-white hidden" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
      `;
      
      // Create label if needed
      const labelText = element.getAttribute('data-label');
      let label = null;
      
      if (labelText) {
        label = document.createElement('span');
        label.className = 'cf-checkbox-label';
        label.textContent = labelText;
      }
      
      // Move checkbox inside wrapper
      wrapper.appendChild(element);
      wrapper.appendChild(customCheckbox);
      if (label) {
        wrapper.appendChild(label);
      }
      
      // Update custom checkbox when the real one changes
      const updateCheckbox = () => {
        if (element.checked) {
          customCheckbox.classList.add('bg-primary-600', 'border-primary-600');
          customCheckbox.querySelector('svg').classList.remove('hidden');
        } else {
          customCheckbox.classList.remove('bg-primary-600', 'border-primary-600');
          customCheckbox.querySelector('svg').classList.add('hidden');
        }
      };
      
      // Click handler for custom checkbox
      wrapper.addEventListener('click', (e) => {
        if (e.target !== element) {
          element.checked = !element.checked;
          element.dispatchEvent(new Event('change', { bubbles: true }));
          updateCheckbox();
        }
      });
      
      // Listen for changes
      element.addEventListener('change', updateCheckbox);
      
      // Initial state
      updateCheckbox();
    },
    
    /**
     * Create custom radio design
     * @param {HTMLElement} element Radio element
     * @private
     */
    _makeCustomRadio(element) {
      // Create wrapper
      const wrapper = document.createElement('div');
      wrapper.className = 'cf-custom-radio inline-flex items-center';
      
      // Insert wrapper
      element.parentNode.insertBefore(wrapper, element);
      
      // Hide original radio but keep it accessible
      element.classList.add('sr-only');
      
      // Create custom radio
      const customRadio = document.createElement('span');
      customRadio.className = 'cf-radio-indicator w-5 h-5 border border-gray-300 rounded-full flex-shrink-0 mr-2';
      customRadio.innerHTML = `<span class="hidden absolute inset-0 flex items-center justify-center">
        <span class="w-2.5 h-2.5 bg-primary-600 rounded-full"></span>
      </span>`;
      
      // Create label if needed
      const labelText = element.getAttribute('data-label');
      let label = null;
      
      if (labelText) {
        label = document.createElement('span');
        label.className = 'cf-radio-label';
        label.textContent = labelText;
      }
      
      // Move radio inside wrapper
      wrapper.appendChild(element);
      wrapper.appendChild(customRadio);
      if (label) {
        wrapper.appendChild(label);
      }
      
      // Update custom radio when the real one changes
      const updateRadio = () => {
        if (element.checked) {
          customRadio.classList.add('border-primary-600');
          customRadio.querySelector('span').classList.remove('hidden');
        } else {
          customRadio.classList.remove('border-primary-600');
          customRadio.querySelector('span').classList.add('hidden');
        }
      };
      
      // Click handler for custom radio
      wrapper.addEventListener('click', (e) => {
        if (e.target !== element) {
          element.checked = true;
          element.dispatchEvent(new Event('change', { bubbles: true }));
          updateRadio();
          
          // Update other radios in the same group
          if (element.name) {
            document.querySelectorAll(`input[type="radio"][name="${element.name}"]`).forEach(radio => {
              if (radio !== element) {
                radio.dispatchEvent(new Event('change', { bubbles: true }));
              }
            });
          }
        }
      });
      
      // Listen for changes
      element.addEventListener('change', updateRadio);
      
      // Initial state
      updateRadio();
    },
    
    /**
     * Parse component options from data attributes
     * @param {HTMLElement} element Element with data attributes
     * @param {string} componentName Component name
     * @returns {Object} Parsed options
     * @private
     */
    _parseDataOptions(element, componentName) {
      const options = {};
      const prefix = `cf${componentName.charAt(0).toUpperCase() + componentName.slice(1)}`;
      
      // Parse specific component options
      try {
        if (element.dataset[prefix]) {
          Object.assign(options, JSON.parse(element.dataset[prefix]));
        }
      } catch (e) {
        console.error(`Error parsing ${prefix} options:`, e);
      }
      
      // Parse common options
      for (const [key, value] of Object.entries(element.dataset)) {
        if (key.startsWith('cf') && key !== prefix) {
          // Convert camelCase to object property
          const propName = key.replace(/^cf/, '');
          const propNameCamel = propName.charAt(0).toLowerCase() + propName.slice(1);
          
          // Parse boolean values
          if (value === 'true') {
            options[propNameCamel] = true;
          } else if (value === 'false') {
            options[propNameCamel] = false;
          } else if (!isNaN(Number(value))) {
            options[propNameCamel] = Number(value);
          } else {
            options[propNameCamel] = value;
          }
        }
      }
      
      return options;
    },
    
    /**
     * Generate a unique ID
     * @returns {string} Unique ID
     * @private
     */
    _generateId() {
      return Math.random().toString(36).substring(2, 10);
    },
    
    /**
     * Handle input focus event
     * @param {HTMLElement} element Input element
     * @private
     */
    _handleInputFocus(element) {
      element.classList.add('focused');
    },
    
    /**
     * Handle input blur event
     * @param {HTMLElement} element Input element
     * @private
     */
    _handleInputBlur(element) {
      element.classList.remove('focused');
    }
  };
  
  // Register with CarFuse
  if (!CarFuse.forms) CarFuse.forms = {};
  CarFuse.forms.components = components;
  
  // Export to window in case CarFuse is not available
  if (!window.CarFuse) {
    window.CarFuseForms = window.CarFuseForms || {};
    window.CarFuseForms.components = components;
  }
})();
