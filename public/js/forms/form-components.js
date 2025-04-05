/**
 * CarFuse Form Components
 * Provides standardized form component behaviors and enhancements
 */
(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    if (!CarFuse.forms) {
        CarFuse.forms = {};
    }
    
    if (!CarFuse.forms.components) {
        CarFuse.forms.components = {};
    }
    
    // Create logger if CarFuse errorHandler exists
    const logger = CarFuse.errorHandler?.createLogger 
        ? CarFuse.errorHandler.createLogger('FormComponents') 
        : console;
    
    /**
     * Form field components registry
     */
    const components = {
        /**
         * Character counter for textarea/input fields
         * Usage: <textarea data-char-counter data-max-chars="100"></textarea>
         */
        characterCounter: {
            selector: '[data-char-counter]',
            init: function(element) {
                // Get max characters
                const maxChars = parseInt(element.dataset.maxChars, 10) || 100;
                const warningThreshold = parseInt(element.dataset.warningThreshold, 10) || 0.9 * maxChars;
                const counterClass = element.dataset.counterClass || 'char-counter';
                const warningClass = element.dataset.warningClass || 'text-warning';
                const errorClass = element.dataset.errorClass || 'text-danger';
                
                // Create counter element
                const counter = document.createElement('div');
                counter.className = counterClass;
                counter.innerHTML = `0/${maxChars} characters`;
                
                // Insert after element
                if (element.nextSibling) {
                    element.parentNode.insertBefore(counter, element.nextSibling);
                } else {
                    element.parentNode.appendChild(counter);
                }
                
                // Update counter function
                const updateCounter = function() {
                    const length = element.value.length;
                    const remaining = maxChars - length;
                    counter.innerHTML = `${length}/${maxChars} characters`;
                    
                    // Update classes based on length
                    counter.classList.remove(warningClass, errorClass);
                    if (length >= maxChars) {
                        counter.classList.add(errorClass);
                    } else if (length >= warningThreshold) {
                        counter.classList.add(warningClass);
                    }
                };
                
                // Add event listeners
                element.addEventListener('input', updateCounter);
                element.addEventListener('paste', () => setTimeout(updateCounter, 0));
                
                // Initial update
                updateCounter();
                
                // Store cleanup function
                element._cleanupCharCounter = () => {
                    element.removeEventListener('input', updateCounter);
                    element.removeEventListener('paste', updateCounter);
                    if (counter.parentNode) {
                        counter.parentNode.removeChild(counter);
                    }
                };
            },
            
            destroy: function(element) {
                if (element._cleanupCharCounter) {
                    element._cleanupCharCounter();
                }
            }
        },
        
        /**
         * Password strength meter
         * Usage: <input type="password" data-password-meter data-requirements="uppercase,lowercase,number,special,length:8">
         */
        passwordMeter: {
            selector: '[data-password-meter]',
            init: function(element) {
                // Get requirements
                const requirementsString = element.dataset.requirements || 'length:8';
                const requirements = requirementsString.split(',').reduce((acc, req) => {
                    if (req.includes(':')) {
                        const [key, value] = req.split(':');
                        acc[key] = value;
                    } else {
                        acc[req] = true;
                    }
                    return acc;
                }, {});
                
                // Default length requirement if not specified
                if (!requirements.length) {
                    requirements.length = 8;
                }
                
                // Create meter element
                const meter = document.createElement('div');
                meter.className = element.dataset.meterClass || 'password-strength-meter';
                
                // Create meter bar
                const meterBar = document.createElement('div');
                meterBar.className = 'password-meter-bar';
                meter.appendChild(meterBar);
                
                // Create meter text
                const meterText = document.createElement('div');
                meterText.className = 'password-meter-text';
                meterText.innerHTML = 'Password strength: <span>Not rated</span>';
                meter.appendChild(meterText);
                
                // Insert after element
                if (element.nextSibling) {
                    element.parentNode.insertBefore(meter, element.nextSibling);
                } else {
                    element.parentNode.appendChild(meter);
                }
                
                // Password strength check function
                const checkPasswordStrength = function() {
                    const password = element.value;
                    const checks = {
                        uppercase: /[A-Z]/.test(password),
                        lowercase: /[a-z]/.test(password),
                        number: /[0-9]/.test(password),
                        special: /[^A-Za-z0-9]/.test(password),
                        length: password.length >= parseInt(requirements.length, 10)
                    };
                    
                    // Count passed checks
                    let passedChecks = 0;
                    let totalChecks = 0;
                    
                    // Check each requirement
                    for (const [req, enabled] of Object.entries(requirements)) {
                        if (enabled && checks[req]) {
                            passedChecks++;
                        }
                        if (enabled) {
                            totalChecks++;
                        }
                    }
                    
                    // Calculate strength as percentage
                    const strength = totalChecks > 0 ? Math.round((passedChecks / totalChecks) * 100) : 0;
                    
                    // Update meter
                    meterBar.style.width = `${strength}%`;
                    
                    // Set color based on strength
                    meterBar.className = 'password-meter-bar';
                    if (strength >= 100) {
                        meterBar.classList.add('password-meter-strong');
                        meterText.querySelector('span').textContent = 'Strong';
                    } else if (strength >= 70) {
                        meterBar.classList.add('password-meter-good');
                        meterText.querySelector('span').textContent = 'Good';
                    } else if (strength >= 40) {
                        meterBar.classList.add('password-meter-medium');
                        meterText.querySelector('span').textContent = 'Medium';
                    } else if (strength > 0) {
                        meterBar.classList.add('password-meter-weak');
                        meterText.querySelector('span').textContent = 'Weak';
                    } else {
                        meterText.querySelector('span').textContent = 'Not rated';
                    }
                    
                    // Store strength as data attribute
                    element.dataset.passwordStrength = strength;
                    
                    // Dispatch event
                    element.dispatchEvent(new CustomEvent('password-strength-change', {
                        bubbles: true,
                        detail: {
                            strength,
                            checks,
                            passedChecks,
                            totalChecks
                        }
                    }));
                };
                
                // Add event listeners
                element.addEventListener('input', checkPasswordStrength);
                element.addEventListener('paste', () => setTimeout(checkPasswordStrength, 0));
                
                // Initial check
                checkPasswordStrength();
                
                // Store cleanup function
                element._cleanupPasswordMeter = () => {
                    element.removeEventListener('input', checkPasswordStrength);
                    element.removeEventListener('paste', checkPasswordStrength);
                    if (meter.parentNode) {
                        meter.parentNode.removeChild(meter);
                    }
                };
            },
            
            destroy: function(element) {
                if (element._cleanupPasswordMeter) {
                    element._cleanupPasswordMeter();
                }
            }
        },
        
        /**
         * Input mask for formatted inputs
         * Usage: <input data-input-mask="phone" data-mask-pattern="(000) 000-0000">
         */
        inputMask: {
            selector: '[data-input-mask]',
            init: function(element) {
                const type = element.dataset.inputMask;
                const pattern = element.dataset.maskPattern;
                
                if (!pattern && !type) {
                    logger.warn('Input mask requires either data-mask-pattern or a valid data-input-mask type');
                    return;
                }
                
                // Define preset patterns
                const presetPatterns = {
                    phone: '(000) 000-0000',
                    date: '00/00/0000',
                    time: '00:00',
                    postal: '00-000',
                    nip: '000-000-00-00',
                    pesel: '00000000000'
                };
                
                // Get pattern to use (custom or preset)
                const maskPattern = pattern || presetPatterns[type] || '';
                
                // Function to apply mask to input
                const applyMask = function(value, pattern) {
                    let maskedValue = '';
                    let valueIndex = 0;
                    
                    for (let i = 0; i < pattern.length && valueIndex < value.length; i++) {
                        const maskChar = pattern[i];
                        const valueChar = value[valueIndex];
                        
                        if (maskChar === '0') {
                            // Only allow digits for '0' placeholder
                            if (/\d/.test(valueChar)) {
                                maskedValue += valueChar;
                                valueIndex++;
                            }
                        } else if (maskChar === 'a') {
                            // Only allow letters for 'a' placeholder
                            if (/[a-zA-Z]/.test(valueChar)) {
                                maskedValue += valueChar;
                                valueIndex++;
                            }
                        } else if (maskChar === '*') {
                            // Allow any character for '*' placeholder
                            maskedValue += valueChar;
                            valueIndex++;
                        } else {
                            // For non-placeholder characters, add the mask character
                            maskedValue += maskChar;
                            
                            // Only consume value character if it matches mask character
                            if (valueChar === maskChar) {
                                valueIndex++;
                            }
                        }
                    }
                    
                    return maskedValue;
                };
                
                // Handle input events
                const handleInput = function(e) {
                    // Get current cursor position
                    const cursorPos = e.target.selectionStart;
                    const oldValue = e.target.value;
                    
                    // Get raw value (removing non-alphanumeric characters)
                    const rawValue = oldValue.replace(/[^\w]/g, '');
                    
                    // Apply mask
                    const maskedValue = applyMask(rawValue, maskPattern);
                    
                    // Update input value if it changed
                    if (maskedValue !== oldValue) {
                        e.target.value = maskedValue;
                        
                        // Try to maintain cursor position
                        if (cursorPos) {
                            // Calculate offset based on added mask characters
                            const addedMaskChars = maskedValue.length - rawValue.length;
                            e.target.setSelectionRange(cursorPos + addedMaskChars, cursorPos + addedMaskChars);
                        }
                    }
                };
                
                // Add input event listener
                element.addEventListener('input', handleInput);
                
                // Store cleanup function
                element._cleanupInputMask = () => {
                    element.removeEventListener('input', handleInput);
                };
            },
            
            destroy: function(element) {
                if (element._cleanupInputMask) {
                    element._cleanupInputMask();
                }
            }
        },
        
        /**
         * Autocomplete input with datalist suggestion
         * Usage: <input data-autocomplete data-source="/api/suggestions" data-min-chars="2">
         */
        autocomplete: {
            selector: '[data-autocomplete]',
            init: function(element) {
                const source = element.dataset.source;
                const minChars = parseInt(element.dataset.minChars || '2', 10);
                const resultsLimit = parseInt(element.dataset.limit || '10', 10);
                const debounceTime = parseInt(element.dataset.debounce || '300', 10);
                
                if (!source) {
                    logger.warn('Autocomplete requires data-source attribute');
                    return;
                }
                
                // Create datalist element
                const datalistId = `autocomplete-${Math.random().toString(36).substring(2, 9)}`;
                const datalist = document.createElement('datalist');
                datalist.id = datalistId;
                document.body.appendChild(datalist);
                
                // Link input to datalist
                element.setAttribute('list', datalistId);
                
                // Debounce function
                let debounceTimer = null;
                
                // Fetch suggestions function
                const fetchSuggestions = async function(query) {
                    try {
                        // Skip if query is too short
                        if (query.length < minChars) {
                            datalist.innerHTML = '';
                            return;
                        }
                        
                        // Fetch suggestions
                        let url = source;
                        
                        // Handle API URL with query parameter
                        if (url.includes('?')) {
                            url += `&query=${encodeURIComponent(query)}`;
                        } else {
                            url += `?query=${encodeURIComponent(query)}`;
                        }
                        
                        // Add limit parameter
                        url += `&limit=${resultsLimit}`;
                        
                        const response = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });
                        
                        if (!response.ok) {
                            throw new Error(`Request failed: ${response.status}`);
                        }
                        
                        const data = await response.json();
                        
                        // Clear datalist
                        datalist.innerHTML = '';
                        
                        // Add suggestions to datalist
                        if (data && Array.isArray(data)) {
                            data.forEach(item => {
                                const option = document.createElement('option');
                                option.value = typeof item === 'string' ? item : item.value || item.name || '';
                                if (typeof item !== 'string' && item.label) {
                                    option.textContent = item.label;
                                }
                                datalist.appendChild(option);
                            });
                        } else if (data && Array.isArray(data.results)) {
                            // Handle common API response format
                            data.results.forEach(item => {
                                const option = document.createElement('option');
                                option.value = typeof item === 'string' ? item : item.value || item.name || '';
                                if (typeof item !== 'string' && item.label) {
                                    option.textContent = item.label;
                                }
                                datalist.appendChild(option);
                            });
                        }
                        
                        // Dispatch event
                        element.dispatchEvent(new CustomEvent('autocomplete-updated', {
                            bubbles: true,
                            detail: { 
                                query,
                                suggestions: data
                            }
                        }));
                    } catch (error) {
                        logger.error('Autocomplete error', error);
                        
                        // Dispatch error event
                        element.dispatchEvent(new CustomEvent('autocomplete-error', {
                            bubbles: true,
                            detail: { 
                                query,
                                error
                            }
                        }));
                    }
                };
                
                // Handle input event
                const handleInput = function() {
                    const query = element.value.trim();
                    
                    // Clear previous timer
                    if (debounceTimer) {
                        clearTimeout(debounceTimer);
                    }
                    
                    // Set new timer
                    debounceTimer = setTimeout(() => {
                        fetchSuggestions(query);
                    }, debounceTime);
                };
                
                // Add event listeners
                element.addEventListener('input', handleInput);
                
                // Store cleanup function
                element._cleanupAutocomplete = () => {
                    element.removeEventListener('input', handleInput);
                    element.removeAttribute('list');
                    if (datalist.parentNode) {
                        datalist.parentNode.removeChild(datalist);
                    }
                    if (debounceTimer) {
                        clearTimeout(debounceTimer);
                    }
                };
            },
            
            destroy: function(element) {
                if (element._cleanupAutocomplete) {
                    element._cleanupAutocomplete();
                }
            }
        },
        
        /**
         * File upload with preview
         * Usage: <input type="file" data-file-upload data-preview="#preview">
         */
        fileUpload: {
            selector: '[data-file-upload]',
            init: function(element) {
                // Get options
                const previewSelector = element.dataset.preview;
                const maxSize = parseInt(element.dataset.maxSize || '5120', 10); // Default 5MB
                const acceptedTypes = element.dataset.accept?.split(',') || [];
                const multiple = element.hasAttribute('multiple');
                
                // Create UI elements
                const container = document.createElement('div');
                container.className = 'file-upload-container';
                
                // Create upload button UI
                const uploadBtn = document.createElement('div');
                uploadBtn.className = 'file-upload-button';
                uploadBtn.innerHTML = `
                    <div class="file-upload-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <div class="file-upload-text">
                        <span class="file-upload-prompt">Choose file${multiple ? 's' : ''} or drag here</span>
                        <span class="file-upload-info">Max size: ${maxSize / 1024}MB</span>
                    </div>
                `;
                
                // Create file list/preview container
                const fileList = document.createElement('div');
                fileList.className = 'file-upload-list';
                
                // Add drag-drop functionality
                uploadBtn.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    uploadBtn.classList.add('drag-over');
                });
                
                uploadBtn.addEventListener('dragleave', () => {
                    uploadBtn.classList.remove('drag-over');
                });
                
                uploadBtn.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadBtn.classList.remove('drag-over');
                    
                    // Handle dropped files
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        element.files = files;
                        handleFileChange();
                    }
                });
                
                // Handle file selection
                uploadBtn.addEventListener('click', () => {
                    element.click();
                });
                
                // Handle file change event
                const handleFileChange = function() {
                    const files = element.files;
                    
                    // Clear file list
                    fileList.innerHTML = '';
                    
                    // Process each file
                    Array.from(files).forEach(file => {
                        // Create file item
                        const fileItem = document.createElement('div');
                        fileItem.className = 'file-item';
                        
                        // Check file size
                        const fileSizeKB = file.size / 1024;
                        const fileSizeDisplay = fileSizeKB > 1024 ? 
                            `${(fileSizeKB/1024).toFixed(1)} MB` : `${Math.round(fileSizeKB)} KB`;
                        
                        const isValidSize = file.size <= maxSize * 1024;
                        const isValidType = acceptedTypes.length === 0 || 
                            acceptedTypes.some(type => file.type.startsWith(type) || file.name.endsWith(type));
                        
                        // Set validity class
                        if (!isValidSize || !isValidType) {
                            fileItem.classList.add('file-invalid');
                        }
                        
                        // File preview/icon
                        const filePreview = document.createElement('div');
                        filePreview.className = 'file-preview';
                        
                        // Create preview for images
                        if (file.type.startsWith('image/')) {
                            const img = document.createElement('img');
                            img.src = URL.createObjectURL(file);
                            img.onload = () => URL.revokeObjectURL(img.src);
                            filePreview.appendChild(img);
                        } else {
                            // Icon for non-image files
                            filePreview.innerHTML = `
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                            `;
                        }
                        
                        // File info
                        const fileInfo = document.createElement('div');
                        fileInfo.className = 'file-info';
                        fileInfo.innerHTML = `
                            <div class="file-name">${file.name}</div>
                            <div class="file-size">${fileSizeDisplay}</div>
                            ${!isValidSize ? '<div class="file-error">File is too large</div>' : ''}
                            ${!isValidType ? '<div class="file-error">Invalid file type</div>' : ''}
                        `;
                        
                        // Remove button
                        if (multiple) {
                            const removeBtn = document.createElement('button');
                            removeBtn.className = 'file-remove';
                            removeBtn.innerHTML = '&times;';
                            removeBtn.type = 'button';
                            
                            removeBtn.addEventListener('click', () => {
                                // Remove file from FileList (need to create a new FileList)
                                const dt = new DataTransfer();
                                Array.from(element.files)
                                    .filter(f => f !== file)
                                    .forEach(f => dt.items.add(f));
                                
                                element.files = dt.files;
                                fileItem.remove();
                                
                                // Update external preview if any
                                updateExternalPreview();
                            });
                            
                            fileItem.appendChild(removeBtn);
                        }
                        
                        // Add components to file item
                        fileItem.appendChild(filePreview);
                        fileItem.appendChild(fileInfo);
                        
                        // Add to file list
                        fileList.appendChild(fileItem);
                    });
                    
                    // Update external preview if any
                    updateExternalPreview();
                    
                    // Trigger change event
                    element.dispatchEvent(new CustomEvent('files-updated', {
                        bubbles: true,
                        detail: { files: element.files }
                    }));
                };
                
                // Update external preview element if specified
                const updateExternalPreview = function() {
                    if (!previewSelector) return;
                    
                    const previewElement = document.querySelector(previewSelector);
                    if (!previewElement) return;
                    
                    // Clear preview
                    previewElement.innerHTML = '';
                    
                    // Add previews for all files
                    Array.from(element.files).forEach(file => {
                        if (file.type.startsWith('image/')) {
                            const img = document.createElement('img');
                            img.src = URL.createObjectURL(file);
                            img.className = 'file-preview-image';
                            img.onload = () => URL.revokeObjectURL(img.src);
                            previewElement.appendChild(img);
                        }
                    });
                };
                
                // Hide the original file input
                element.style.display = 'none';
                
                // Add event listener to original input
                element.addEventListener('change', handleFileChange);
                
                // Add components to container
                container.appendChild(uploadBtn);
                container.appendChild(fileList);
                
                // Insert container after the original input
                element.parentNode.insertBefore(container, element.nextSibling);
                
                // Store cleanup function
                element._cleanupFileUpload = () => {
                    element.removeEventListener('change', handleFileChange);
                    if (container.parentNode) {
                        container.parentNode.removeChild(container);
                    }
                    element.style.display = '';
                };
            },
            
            destroy: function(element) {
                if (element._cleanupFileUpload) {
                    element._cleanupFileUpload();
                }
            }
        }
    };
    
    /**
     * Initialize form components on specified container or document
     * @param {HTMLElement} container - Container element or null for document
     */
    function initializeComponents(container = null) {
        const root = container || document;
        
        // Initialize each component type
        Object.entries(components).forEach(([name, component]) => {
            try {
                const elements = root.querySelectorAll(component.selector);
                elements.forEach(element => {
                    // Skip already initialized elements
                    if (element.dataset.componentInitialized === name) return;
                    
                    // Initialize component
                    component.init(element);
                    
                    // Mark as initialized
                    element.dataset.componentInitialized = name;
                });
            } catch (error) {
                logger.error(`Error initializing component ${name}:`, error);
            }
        });
    }
    
    /**
     * Destroy all form components in a container
     * @param {HTMLElement} container - Container element or null for document
     */
    function destroyComponents(container = null) {
        const root = container || document;
        
        // Destroy each component type
        Object.entries(components).forEach(([name, component]) => {
            try {
                const elements = root.querySelectorAll(`${component.selector}[data-component-initialized="${name}"]`);
                elements.forEach(element => {
                    // Call component's destroy method
                    if (component.destroy) {
                        component.destroy(element);
                    }
                    
                    // Remove initialization mark
                    delete element.dataset.componentInitialized;
                });
            } catch (error) {
                logger.error(`Error destroying component ${name}:`, error);
            }
        });
    }
    
    // Register component functions with CarFuse
    CarFuse.forms.components = components;
    CarFuse.forms.initializeComponents = initializeComponents;
    CarFuse.forms.destroyComponents = destroyComponents;
    
    // Auto-initialize on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        initializeComponents();
    });
    
    // Auto-initialize on content updates (when dynamic content is loaded)
    document.addEventListener('content-loaded', event => {
        if (event.detail && event.detail.container) {
            initializeComponents(event.detail.container);
        }
    });
})();
