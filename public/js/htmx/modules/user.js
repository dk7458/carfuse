/**
 * CarFuse HTMX User Module
 * Specialized module for handling user account operations with HTMX
 */

(function() {
    // Make sure dependencies are loaded
    if (typeof htmx === 'undefined') {
        console.error('HTMX library not found. Required for user module.');
        return;
    }
    
    if (typeof window.CarFuseHTMX === 'undefined') {
        console.error('CarFuseHTMX core not found. Required for user module.');
        return;
    }
    
    const CarFuseHTMX = window.CarFuseHTMX;
    CarFuseHTMX.log('Initializing user module');
    
    // Add user account methods to CarFuseHTMX
    CarFuseHTMX.user = {
        /**
         * Update user profile
         * @param {FormData|object} formData - Form data or profile data object
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when profile is updated
         */
        updateProfile: function(formData, targetSelector = '#profile-container', options = {}) {
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            // If formData is not a FormData object, convert it
            let data = formData;
            if (!(formData instanceof FormData)) {
                data = new FormData();
                Object.entries(formData).forEach(([key, value]) => {
                    data.append(key, value);
                });
            }
            
            return CarFuseHTMX.ajax('POST', '/user/update-profile', {
                target: target,
                swap: options.swap || 'innerHTML',
                values: data,
                headers: options.headers || {}
            });
        },
        
        /**
         * Change user password
         * @param {object} passwordData - Password data object
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when password is changed
         */
        changePassword: function(passwordData, targetSelector = '#password-form-container', options = {}) {
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            // Create form data for the request
            const formData = new FormData();
            formData.append('current_password', passwordData.current || '');
            formData.append('password', passwordData.new || '');
            formData.append('password_confirmation', passwordData.confirm || '');
            
            return CarFuseHTMX.ajax('POST', '/user/change-password', {
                target: target,
                swap: options.swap || 'innerHTML',
                values: formData,
                headers: options.headers || {}
            });
        },
        
        /**
         * Load user preferences
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when preferences are loaded
         */
        loadPreferences: function(targetSelector = '#user-preferences', options = {}) {
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            return CarFuseHTMX.ajax('GET', '/user/preferences', {
                target: target,
                swap: options.swap || 'innerHTML',
                headers: options.headers || {}
            });
        },
        
        /**
         * Update user notification settings
         * @param {object} settings - Notification settings
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when settings are updated
         */
        updateNotificationSettings: function(settings, targetSelector = '#notification-settings', options = {}) {
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            // Create form data for the request
            const formData = new FormData();
            
            // Add each setting to form data
            Object.entries(settings).forEach(([key, value]) => {
                formData.append(key, value);
            });
            
            return CarFuseHTMX.ajax('POST', '/user/notification-settings', {
                target: target,
                swap: options.swap || 'innerHTML',
                values: formData,
                headers: options.headers || {}
            });
        },
        
        /**
         * Load user notifications
         * @param {object} filters - Filter criteria
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when notifications are loaded
         */
        loadNotifications: function(filters = {}, targetSelector = '#notifications-container', options = {}) {
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            // Build query string from filters
            const params = new URLSearchParams();
            
            if (filters.page) params.append('page', filters.page);
            if (filters.limit) params.append('limit', filters.limit);
            if (filters.read === true || filters.read === false) params.append('read', filters.read ? '1' : '0');
            if (filters.type) params.append('type', filters.type);
            
            return CarFuseHTMX.ajax('GET', `/user/notifications?${params.toString()}`, {
                target: target,
                swap: options.swap || 'innerHTML',
                headers: options.headers || {}
            });
        },
        
        /**
         * Mark notification as read
         * @param {string|number} notificationId - ID of the notification
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when notification is marked as read
         */
        markNotificationRead: function(notificationId, targetSelector = null, options = {}) {
            // Create form data for the request
            const formData = new FormData();
            
            // If no target selector, just make the request without updating UI
            if (!targetSelector) {
                return fetch(`/user/notifications/${notificationId}/mark-read`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to mark notification as read');
                    }
                    return response.json();
                });
            }
            
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            return CarFuseHTMX.ajax('POST', `/user/notifications/${notificationId}/mark-read`, {
                target: target,
                swap: options.swap || 'innerHTML',
                values: formData,
                headers: options.headers || {}
            });
        },
        
        /**
         * Mark all notifications as read
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when all notifications are marked as read
         */
        markAllNotificationsRead: function(targetSelector = '#notifications-container', options = {}) {
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            // Create form data for the request
            const formData = new FormData();
            
            return CarFuseHTMX.ajax('POST', '/user/notifications/mark-all-read', {
                target: target,
                swap: options.swap || 'innerHTML',
                values: formData,
                headers: options.headers || {}
            });
        },
        
        /**
         * Upload user avatar
         * @param {File} file - Avatar file to upload
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when avatar is uploaded
         */
        uploadAvatar: function(file, targetSelector = '#avatar-container', options = {}) {
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            // Create form data for the request
            const formData = new FormData();
            formData.append('avatar', file);
            
            return CarFuseHTMX.ajax('POST', '/user/upload-avatar', {
                target: target,
                swap: options.swap || 'innerHTML',
                values: formData,
                headers: options.headers || {}
            });
        },
        
        /**
         * Set up event listeners for user-related functionality
         * @param {HTMLElement} container - Container element to initialize (defaults to document)
         */
        init: function(container = document) {
            CarFuseHTMX.log('Initializing user event listeners');
            
            // Handle notification marking as read
            container.addEventListener('click', event => {
                const notificationButton = event.target.closest('[data-notification-id]');
                if (notificationButton && notificationButton.hasAttribute('data-mark-read')) {
                    event.preventDefault();
                    
                    const notificationId = notificationButton.getAttribute('data-notification-id');
                    const target = notificationButton.getAttribute('data-target');
                    
                    this.markNotificationRead(notificationId, target);
                }
            });
            
            // Handle mark all notifications as read
            container.addEventListener('click', event => {
                const markAllButton = event.target.closest('[data-mark-all-read]');
                if (markAllButton) {
                    event.preventDefault();
                    
                    const target = markAllButton.getAttribute('data-target') || '#notifications-container';
                    this.markAllNotificationsRead(target);
                }
            });
            
            // Handle avatar upload
            container.addEventListener('change', event => {
                const avatarInput = event.target.closest('[data-avatar-upload]');
                if (avatarInput && avatarInput.files && avatarInput.files.length > 0) {
                    const target = avatarInput.getAttribute('data-target') || '#avatar-container';
                    this.uploadAvatar(avatarInput.files[0], target);
                }
            });
            
            // Handle form submissions with validation
            container.addEventListener('submit', event => {
                const form = event.target.closest('form[data-validate]');
                if (form) {
                    // If form has validation attribute, perform validation
                    event.preventDefault();
                    
                    // Example validation logic (to be customized)
                    const isValid = this._validateForm(form);
                    
                    if (isValid) {
                        // If using HTMX, let HTMX handle the submission
                        if (form.hasAttribute('hx-post') || form.hasAttribute('hx-put')) {
                            htmx.trigger(form, 'submit');
                        } else {
                            // Otherwise submit the form normally
                            form.submit();
                        }
                    }
                }
            });
        },
        
        /**
         * Validate a form's fields
         * @param {HTMLFormElement} form - Form to validate
         * @returns {boolean} Whether the form is valid
         */
        _validateForm: function(form) {
            let isValid = true;
            
            // Clear previous error messages
            form.querySelectorAll('.error-message').forEach(el => {
                el.remove();
            });
            
            // Validate required fields
            form.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    this._showFieldError(field, 'To pole jest wymagane');
                }
            });
            
            // Validate email fields
            form.querySelectorAll('input[type="email"]').forEach(field => {
                if (field.value && !this._isValidEmail(field.value)) {
                    isValid = false;
                    this._showFieldError(field, 'Wprowadź prawidłowy adres email');
                }
            });
            
            return isValid;
        },
        
        /**
         * Show an error message for a form field
         * @param {HTMLElement} field - Field with error
         * @param {string} message - Error message
         */
        _showFieldError: function(field, message) {
            const errorMessage = document.createElement('div');
            errorMessage.className = 'error-message text-red-500 text-sm mt-1';
            errorMessage.textContent = message;
            
            if (field.parentNode) {
                field.parentNode.appendChild(errorMessage);
            }
            
            field.classList.add('border-red-500');
        },
        
        /**
         * Validate email format
         * @param {string} email - Email to validate
         * @returns {boolean} Whether the email is valid
         */
        _isValidEmail: function(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
    };
    
    // Add user methods to the global object
    Object.assign(window.CarFuseHTMX, CarFuseHTMX.user);
    
    // Register custom event handlers for HTMX events related to user functionality
    document.addEventListener('htmx:afterSwap', event => {
        const target = event.detail.target;
        
        // Initialize user components after they are loaded
        if (target.id === 'profile-container' || 
            target.id === 'notifications-container' ||
            target.classList.contains('user-component') ||
            target.querySelector('.user-component')) {
            
            // Initialize user event handlers
            CarFuseHTMX.user.init(target);
            
            // Dispatch custom event for other components to react to
            document.dispatchEvent(new CustomEvent('carfuse:user-component-loaded', {
                detail: { targetId: target.id }
            }));
        }
    });
    
    // Initialize user functionality when the document is ready
    if (document.readyState !== 'loading') {
        CarFuseHTMX.user.init();
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            CarFuseHTMX.user.init();
        });
    }
    
    CarFuseHTMX.log('User module initialized');
})();
