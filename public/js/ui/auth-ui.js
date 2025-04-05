/**
 * CarFuse Auth UI Components
 * 
 * Provides reusable UI components for authentication forms,
 * user profile displays, and permission-based UI elements.
 */
(function() {
    // Verify dependencies
    if (typeof window.AuthHelper === 'undefined') {
        console.error('AuthHelper is required for Auth UI components');
        return;
    }
    
    // CSS Classes
    const CLASSES = {
        CONTAINER: 'cf-auth-container',
        FORM: 'cf-auth-form',
        FIELD: 'cf-auth-field',
        BUTTON: 'cf-auth-button',
        ERROR: 'cf-auth-error',
        SUCCESS: 'cf-auth-success',
        HIDDEN: 'cf-auth-hidden',
        LOADING: 'cf-auth-loading',
        PROFILE: 'cf-auth-profile',
        AVATAR: 'cf-auth-avatar'
    };
    
    /**
     * Auth UI Components
     */
    const AuthUI = {
        // CSS Classes
        CLASSES: CLASSES,
        
        /**
         * Initialize auth UI with default settings
         * @param {object} options - Configuration options
         */
        init: function(options = {}) {
            // Default options
            this.options = {
                autoInitialize: true,
                loginRedirect: null,
                logoutRedirect: '/',
                enableProfileLinks: true,
                enableRegistration: true,
                enablePasswordReset: true,
                enableRememberMe: true,
                ...options
            };
            
            // Auto-initialize components if enabled
            if (this.options.autoInitialize) {
                this.initializeComponents();
            }
            
            // Listen for auth events
            this.bindAuthEvents();
        },
        
        /**
         * Bind to authentication events
         */
        bindAuthEvents: function() {
            // Use standardized event names if available
            const eventNames = window.CarFuseEvents?.NAMES?.AUTH || {
                STATE_CHANGED: 'auth:state-changed',
                LOGIN_SUCCESS: 'auth:login-success',
                LOGIN_ERROR: 'auth:login-error',
                LOGOUT_SUCCESS: 'auth:logout-success',
                SESSION_EXPIRED: 'auth:session-expired'
            };
            
            // Update UI on auth state changes
            document.addEventListener(eventNames.STATE_CHANGED, () => {
                this.updateAuthDependentUI();
            });
            
            // Handle login success
            document.addEventListener(eventNames.LOGIN_SUCCESS, (e) => {
                // Show success message
                this.showMessage('login-success', 'Successfully logged in!');
                
                // Redirect if configured
                if (this.options.loginRedirect) {
                    window.location.href = this.options.loginRedirect;
                }
                
                // Add success indication to forms
                document.querySelectorAll(`.${CLASSES.FORM}[data-form-type="login"]`).forEach(form => {
                    form.classList.add('success');
                });
            });
            
            // Handle login errors
            document.addEventListener(eventNames.LOGIN_ERROR, (e) => {
                const errorMessage = e.detail?.message || 'Login failed';
                this.showMessage('login-error', errorMessage);
                
                // Add error indication to forms
                document.querySelectorAll(`.${CLASSES.FORM}[data-form-type="login"]`).forEach(form => {
                    const errorEl = form.querySelector(`.${CLASSES.ERROR}`) || document.createElement('div');
                    errorEl.className = CLASSES.ERROR;
                    errorEl.textContent = errorMessage;
                    
                    if (!form.querySelector(`.${CLASSES.ERROR}`)) {
                        form.prepend(errorEl);
                    }
                    
                    form.classList.add('error');
                });
            });
            
            // Handle logout
            document.addEventListener(eventNames.LOGOUT_SUCCESS, () => {
                // Show message
                this.showMessage('logout-success', 'You have been logged out');
                
                // Redirect if configured
                if (this.options.logoutRedirect) {
                    window.location.href = this.options.logoutRedirect;
                }
            });
            
            // Handle session expiration
            document.addEventListener(eventNames.SESSION_EXPIRED, () => {
                this.showMessage('session-expired', 'Your session has expired. Please log in again.');
            });
        },
        
        /**
         * Initialize all auth UI components on the page
         */
        initializeComponents: function() {
            // Initialize login forms
            this.initializeForms();
            
            // Initialize profile displays
            this.initializeProfileDisplays();
            
            // Apply role-based visibility
            this.updateAuthDependentUI();
            
            // Initialize logout buttons
            document.querySelectorAll('[data-cf-auth-logout]').forEach(button => {
                if (!button.hasAttribute('data-cf-auth-initialized')) {
                    button.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.handleLogout();
                    });
                    button.setAttribute('data-cf-auth-initialized', 'true');
                }
            });
        },
        
        /**
         * Initialize authentication forms
         */
        initializeForms: function() {
            // Find all auth forms
            document.querySelectorAll(`.${CLASSES.FORM}`).forEach(form => {
                // Skip already initialized forms
                if (form.hasAttribute('data-cf-auth-initialized')) return;
                
                const formType = form.getAttribute('data-form-type') || 'login';
                
                // Set up form submission
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    
                    switch (formType) {
                        case 'login':
                            this.handleLogin(form);
                            break;
                        case 'register':
                            this.handleRegistration(form);
                            break;
                        case 'password-reset':
                            this.handlePasswordReset(form);
                            break;
                        case 'profile-update':
                            this.handleProfileUpdate(form);
                            break;
                    }
                });
                
                // Mark as initialized
                form.setAttribute('data-cf-auth-initialized', 'true');
            });
        },
        
        /**
         * Initialize user profile displays
         */
        initializeProfileDisplays: function() {
            document.querySelectorAll(`.${CLASSES.PROFILE}`).forEach(container => {
                // Skip already initialized containers
                if (container.hasAttribute('data-cf-auth-initialized')) return;
                
                // Update profile content
                this.updateProfileDisplay(container);
                
                // Mark as initialized
                container.setAttribute('data-cf-auth-initialized', 'true');
            });
        },
        
        /**
         * Update a profile display with user data
         * @param {Element} container - Profile display container
         */
        updateProfileDisplay: function(container) {
            if (!container) return;
            
            const auth = window.AuthHelper;
            const isLoggedIn = auth.isAuthenticated();
            
            // Get the profile template type
            const templateType = container.getAttribute('data-profile-template') || 'default';
            const showLogout = container.getAttribute('data-show-logout') !== 'false';
            const showAvatar = container.getAttribute('data-show-avatar') !== 'false';
            
            // Clear current content
            while (container.firstChild) {
                container.removeChild(container.firstChild);
            }
            
            if (isLoggedIn) {
                const userData = auth.getUserData();
                const userRole = auth.getUserRole();
                
                switch (templateType) {
                    case 'compact':
                        this._renderCompactProfile(container, userData, {
                            showLogout, showAvatar
                        });
                        break;
                    case 'detailed':
                        this._renderDetailedProfile(container, userData, {
                            showLogout, showAvatar, userRole
                        });
                        break;
                    case 'avatar-only':
                        this._renderAvatarOnlyProfile(container, userData);
                        break;
                    case 'name-only':
                        this._renderNameOnlyProfile(container, userData);
                        break;
                    default:
                        this._renderDefaultProfile(container, userData, {
                            showLogout, showAvatar
                        });
                }
            } else {
                // Render login link
                const loginLink = document.createElement('a');
                loginLink.href = '/login';
                loginLink.textContent = 'Log In';
                loginLink.className = 'cf-auth-login-link';
                container.appendChild(loginLink);
                
                // Add register link if enabled
                if (this.options.enableRegistration) {
                    container.appendChild(document.createTextNode(' or '));
                    const registerLink = document.createElement('a');
                    registerLink.href = '/register';
                    registerLink.textContent = 'Register';
                    registerLink.className = 'cf-auth-register-link';
                    container.appendChild(registerLink);
                }
            }
        },
        
        /**
         * Render default profile display
         * @private
         */
        _renderDefaultProfile: function(container, userData, options) {
            const { showLogout, showAvatar } = options;
            
            const wrapper = document.createElement('div');
            wrapper.className = 'cf-auth-profile-wrapper';
            
            if (showAvatar && userData.avatar) {
                const avatar = document.createElement('img');
                avatar.src = userData.avatar;
                avatar.alt = `${userData.name}'s avatar`;
                avatar.className = CLASSES.AVATAR;
                wrapper.appendChild(avatar);
            }
            
            const info = document.createElement('div');
            info.className = 'cf-auth-profile-info';
            
            const name = document.createElement('div');
            name.className = 'cf-auth-profile-name';
            name.textContent = userData.name || userData.email || 'User';
            info.appendChild(name);
            
            if (this.options.enableProfileLinks) {
                const links = document.createElement('div');
                links.className = 'cf-auth-profile-links';
                
                const profileLink = document.createElement('a');
                profileLink.href = '/profile';
                profileLink.textContent = 'Profile';
                profileLink.className = 'cf-auth-profile-link';
                links.appendChild(profileLink);
                
                if (showLogout) {
                    links.appendChild(document.createTextNode(' | '));
                    const logoutLink = document.createElement('a');
                    logoutLink.href = '#';
                    logoutLink.textContent = 'Logout';
                    logoutLink.className = 'cf-auth-logout-link';
                    logoutLink.setAttribute('data-cf-auth-logout', 'true');
                    logoutLink.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.handleLogout();
                    });
                    links.appendChild(logoutLink);
                }
                
                info.appendChild(links);
            }
            
            wrapper.appendChild(info);
            container.appendChild(wrapper);
        },
        
        /**
         * Render compact profile display
         * @private
         */
        _renderCompactProfile: function(container, userData, options) {
            const { showLogout, showAvatar } = options;
            
            const wrapper = document.createElement('div');
            wrapper.className = 'cf-auth-profile-compact';
            
            if (showAvatar && userData.avatar) {
                const avatar = document.createElement('img');
                avatar.src = userData.avatar;
                avatar.alt = `${userData.name}'s avatar`;
                avatar.className = `${CLASSES.AVATAR} cf-auth-avatar-small`;
                wrapper.appendChild(avatar);
            }
            
            const nameSpan = document.createElement('span');
            nameSpan.className = 'cf-auth-profile-name';
            nameSpan.textContent = userData.name || userData.email || 'User';
            wrapper.appendChild(nameSpan);
            
            if (showLogout) {
                const logoutLink = document.createElement('a');
                logoutLink.href = '#';
                logoutLink.textContent = 'Logout';
                logoutLink.className = 'cf-auth-logout-link cf-auth-logout-compact';
                logoutLink.setAttribute('data-cf-auth-logout', 'true');
                logoutLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleLogout();
                });
                wrapper.appendChild(logoutLink);
            }
            
            container.appendChild(wrapper);
        },
        
        /**
         * Render detailed profile display
         * @private
         */
        _renderDetailedProfile: function(container, userData, options) {
            const { showLogout, showAvatar, userRole } = options;
            
            // Implementation for detailed profile view
            const wrapper = document.createElement('div');
            wrapper.className = 'cf-auth-profile-detailed';
            
            if (showAvatar && userData.avatar) {
                const avatar = document.createElement('img');
                avatar.src = userData.avatar;
                avatar.alt = `${userData.name}'s avatar`;
                avatar.className = CLASSES.AVATAR;
                wrapper.appendChild(avatar);
            }
            
            const info = document.createElement('div');
            info.className = 'cf-auth-profile-info';
            
            // Name
            const name = document.createElement('div');
            name.className = 'cf-auth-profile-name';
            name.textContent = userData.name || userData.email || 'User';
            info.appendChild(name);
            
            // Email
            if (userData.email) {
                const email = document.createElement('div');
                email.className = 'cf-auth-profile-email';
                email.textContent = userData.email;
                info.appendChild(email);
            }
            
            // Role badge if available
            if (userRole) {
                const roleBadge = document.createElement('div');
                roleBadge.className = `cf-auth-role-badge cf-auth-role-${userRole}`;
                roleBadge.textContent = userRole.charAt(0).toUpperCase() + userRole.slice(1);
                info.appendChild(roleBadge);
            }
            
            // Actions
            const actions = document.createElement('div');
            actions.className = 'cf-auth-profile-actions';
            
            // Profile link
            const profileLink = document.createElement('a');
            profileLink.href = '/profile';
            profileLink.textContent = 'My Profile';
            profileLink.className = 'cf-auth-profile-link';
            actions.appendChild(profileLink);
            
            // Settings link
            if (window.CarFuseRBAC?.checkResourceAccess('settings')) {
                actions.appendChild(document.createTextNode(' | '));
                const settingsLink = document.createElement('a');
                settingsLink.href = '/settings';
                settingsLink.textContent = 'Settings';
                settingsLink.className = 'cf-auth-settings-link';
                actions.appendChild(settingsLink);
            }
            
            // Admin link
            if (window.CarFuseRBAC?.checkResourceAccess('admin-dashboard')) {
                actions.appendChild(document.createTextNode(' | '));
                const adminLink = document.createElement('a');
                adminLink.href = '/admin';
                adminLink.textContent = 'Admin';
                adminLink.className = 'cf-auth-admin-link';
                actions.appendChild(adminLink);
            }
            
            // Logout link
            if (showLogout) {
                actions.appendChild(document.createTextNode(' | '));
                const logoutLink = document.createElement('a');
                logoutLink.href = '#';
                logoutLink.textContent = 'Logout';
                logoutLink.className = 'cf-auth-logout-link';
                logoutLink.setAttribute('data-cf-auth-logout', 'true');
                logoutLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleLogout();
                });
                actions.appendChild(logoutLink);
            }
            
            info.appendChild(actions);
            wrapper.appendChild(info);
            container.appendChild(wrapper);
        },
        
        /**
         * Render avatar-only profile display
         * @private
         */
        _renderAvatarOnlyProfile: function(container, userData) {
            // Implementation for avatar-only view
            const avatarLink = document.createElement('a');
            avatarLink.href = '/profile';
            avatarLink.className = 'cf-auth-avatar-link';
            
            const avatar = document.createElement('img');
            avatar.src = userData.avatar || '/img/default-avatar.png';
            avatar.alt = `${userData.name}'s avatar`;
            avatar.className = CLASSES.AVATAR;
            avatarLink.appendChild(avatar);
            
            container.appendChild(avatarLink);
        },
        
        /**
         * Render name-only profile display
         * @private
         */
        _renderNameOnlyProfile: function(container, userData) {
            // Implementation for name-only view
            const nameLink = document.createElement('a');
            nameLink.href = '/profile';
            nameLink.className = 'cf-auth-name-link';
            nameLink.textContent = userData.name || userData.email || 'User';
            container.appendChild(nameLink);
        },
        
        /**
         * Update all UI elements based on authentication state
         */
        updateAuthDependentUI: function() {
            const isLoggedIn = window.AuthHelper.isAuthenticated();
            
            // Update login/logout visibility
            document.querySelectorAll('[data-cf-auth-logged-in]').forEach(el => {
                el.style.display = isLoggedIn ? '' : 'none';
            });
            
            document.querySelectorAll('[data-cf-auth-logged-out]').forEach(el => {
                el.style.display = !isLoggedIn ? '' : 'none';
            });
            
            // Update role-based visibility if RBAC is available
            if (window.CarFuseRBAC) {
                window.CarFuseRBAC.applyAccessControl();
            } else {
                // Simple role-based visibility
                const userRole = window.AuthHelper.getUserRole();
                
                document.querySelectorAll('[data-cf-auth-role]').forEach(el => {
                    const requiredRole = el.getAttribute('data-cf-auth-role');
                    if (!requiredRole) return;
                    
                    const hasRole = window.AuthHelper.hasRole(requiredRole);
                    el.style.display = hasRole ? '' : 'none';
                });
            }
            
            // Update profile displays
            document.querySelectorAll(`.${CLASSES.PROFILE}`).forEach(container => {
                this.updateProfileDisplay(container);
            });
        },
        
        /**
         * Handle login form submission
         * @param {HTMLFormElement} form - Login form element
         */
        handleLogin: function(form) {
            // Show loading state
            form.classList.add(CLASSES.LOADING);
            
            // Clear previous errors
            const existingError = form.querySelector(`.${CLASSES.ERROR}`);
            if (existingError) {
                existingError.remove();
            }
            
            // Get form data
            const email = form.querySelector('input[name="email"]')?.value;
            const password = form.querySelector('input[name="password"]')?.value;
            const remember = form.querySelector('input[name="remember"]')?.checked;
            
            if (!email || !password) {
                this.showFormError(form, 'Email and password are required');
                form.classList.remove(CLASSES.LOADING);
                return;
            }
            
            // Call AuthHelper login method
            window.AuthHelper.login(email, password, remember)
                .then(response => {
                    form.classList.remove(CLASSES.LOADING);
                    form.classList.add(CLASSES.SUCCESS);
                    
                    // Show success message
                    this.showFormSuccess(form, 'Login successful!');
                    
                    // Handle redirect
                    const redirect = form.getAttribute('data-redirect');
                    if (redirect) {
                        window.location.href = redirect;
                    }
                })
                .catch(error => {
                    form.classList.remove(CLASSES.LOADING);
                    this.showFormError(form, error.message || 'Login failed');
                });
        },
        
        /**
         * Handle registration form submission
         * @param {HTMLFormElement} form - Registration form element
         */
        handleRegistration: function(form) {
            // Show loading state
            form.classList.add(CLASSES.LOADING);
            
            // Clear previous errors
            const existingError = form.querySelector(`.${CLASSES.ERROR}`);
            if (existingError) {
                existingError.remove();
            }
            
            // Get form data
            const formData = new FormData(form);
            const registerEndpoint = form.getAttribute('data-endpoint') || '/api/auth/register';
            
            // Call registration API
            window.AuthHelper.fetch(registerEndpoint, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || data.error || 'Registration failed');
                    });
                }
                return response.json();
            })
            .then(data => {
                form.classList.remove(CLASSES.LOADING);
                form.classList.add(CLASSES.SUCCESS);
                
                // Show success message
                this.showFormSuccess(form, data.message || 'Registration successful!');
                
                // Auto login if server returns tokens
                if (data.token) {
                    // Let the server handle setting cookies
                    window.AuthHelper.fetchUserData().then(() => {
                        // Handle redirect
                        const redirect = form.getAttribute('data-redirect');
                        if (redirect) {
                            window.location.href = redirect;
                        }
                    });
                } else {
                    // Just redirect if specified
                    const redirect = form.getAttribute('data-redirect');
                    if (redirect) {
                        setTimeout(() => {
                            window.location.href = redirect;
                        }, 1500);
                    }
                }
            })
            .catch(error => {
                form.classList.remove(CLASSES.LOADING);
                this.showFormError(form, error.message || 'Registration failed');
            });
        },
        
        /**
         * Handle password reset form submission
         * @param {HTMLFormElement} form - Password reset form element
         */
        handlePasswordReset: function(form) {
            // Show loading state
            form.classList.add(CLASSES.LOADING);
            
            // Clear previous errors
            const existingError = form.querySelector(`.${CLASSES.ERROR}`);
            if (existingError) {
                existingError.remove();
            }
            
            // Get form data
            const formData = new FormData(form);
            const resetEndpoint = form.getAttribute('data-endpoint') || '/api/auth/password/reset';
            
            // Call password reset API
            window.AuthHelper.fetch(resetEndpoint, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || data.error || 'Password reset failed');
                    });
                }
                return response.json();
            })
            .then(data => {
                form.classList.remove(CLASSES.LOADING);
                form.classList.add(CLASSES.SUCCESS);
                
                // Show success message
                this.showFormSuccess(form, data.message || 'Password reset email sent!');
                
                // Redirect if specified
                const redirect = form.getAttribute('data-redirect');
                if (redirect) {
                    setTimeout(() => {
                        window.location.href = redirect;
                    }, 2000);
                }
            })
            .catch(error => {
                form.classList.remove(CLASSES.LOADING);
                this.showFormError(form, error.message || 'Password reset failed');
            });
        },
        
        /**
         * Handle profile update form submission
         * @param {HTMLFormElement} form - Profile update form element
         */
        handleProfileUpdate: function(form) {
            // Show loading state
            form.classList.add(CLASSES.LOADING);
            
            // Clear previous errors
            const existingError = form.querySelector(`.${CLASSES.ERROR}`);
            if (existingError) {
                existingError.remove();
            }
            
            // Get form data
            const formData = new FormData(form);
            const updateEndpoint = form.getAttribute('data-endpoint') || '/api/user/profile';
            
            // Call profile update API
            window.AuthHelper.fetch(updateEndpoint, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || data.error || 'Profile update failed');
                    });
                }
                return response.json();
            })
            .then(data => {
                form.classList.remove(CLASSES.LOADING);
                form.classList.add(CLASSES.SUCCESS);
                
                // Show success message
                this.showFormSuccess(form, data.message || 'Profile updated successfully!');
                
                // Refresh user data to ensure we have the latest
                window.AuthHelper.fetchUserData().then(() => {
                    // Update profile displays
                    this.updateAuthDependentUI();
                });
            })
            .catch(error => {
                form.classList.remove(CLASSES.LOADING);
                this.showFormError(form, error.message || 'Profile update failed');
            });
        },
        
        /**
         * Handle user logout
         */
        handleLogout: function() {
            window.AuthHelper.logout()
                .then(() => {
                    // Redirect if configured
                    if (this.options.logoutRedirect) {
                        window.location.href = this.options.logoutRedirect;
                    }
                })
                .catch(error => {
                    console.error('Logout error:', error);
                    
                    // Show error if available
                    this.showMessage('logout-error', error.message || 'Logout failed');
                });
        },
        
        /**
         * Show a form error message
         * @param {HTMLFormElement} form - Form to show error on
         * @param {string} message - Error message
         */
        showFormError: function(form, message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = CLASSES.ERROR;
            errorDiv.textContent = message;
            
            // Find the submit button and insert before it
            const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitButton) {
                submitButton.parentNode.insertBefore(errorDiv, submitButton);
            } else {
                form.appendChild(errorDiv);
            }
            
            form.classList.add('error');
        },
        
        /**
         * Show a form success message
         * @param {HTMLFormElement} form - Form to show success on
         * @param {string} message - Success message
         */
        showFormSuccess: function(form, message) {
            const successDiv = document.createElement('div');
            successDiv.className = CLASSES.SUCCESS;
            successDiv.textContent = message;
            
            // Find submit button and insert before it
            const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitButton) {
                submitButton.parentNode.insertBefore(successDiv, submitButton);
            } else {
                form.appendChild(successDiv);
            }
            
            form.classList.add('success');
        },
        
        /**
         * Show a global message
         * @param {string} type - Message type (login-success, login-error, etc.)
         * @param {string} message - Message content
         */
        showMessage: function(type, message) {
            // Use toast notification system if available
            if (window.CarFuseEvents) {
                const messageType = type.includes('error') ? 'error' : 
                    type.includes('success') ? 'success' : 'info';
                    
                window.CarFuseEvents.UI.dispatchToastShow({
                    title: messageType.charAt(0).toUpperCase() + messageType.slice(1),
                    message: message,
                    type: messageType
                });
                
                return;
            }
            
            // Otherwise show a simple alert
            alert(message);
        },
        
        /**
         * Create a login form element
         * @param {string} targetSelector - CSS selector for target container
         * @param {object} options - Form configuration options
         * @returns {HTMLFormElement} The created login form
         */
        createLoginForm: function(targetSelector, options = {}) {
            const container = document.querySelector(targetSelector);
            if (!container) {
                console.error(`Target container not found: ${targetSelector}`);
                return null;
            }
            
            // Default options
            const formOptions = {
                showRememberMe: true,
                showForgotPassword: true,
                showRegisterLink: true,
                loginEndpoint: '/api/auth/login',
                loginRedirect: null,
                formClasses: '',
                ...options
            };
            
            // Create form
            const form = document.createElement('form');
            form.className = `${CLASSES.FORM} ${formOptions.formClasses}`.trim();
            form.setAttribute('data-form-type', 'login');
            form.setAttribute('data-endpoint', formOptions.loginEndpoint);
            
            if (formOptions.loginRedirect) {
                form.setAttribute('data-redirect', formOptions.loginRedirect);
            }
            
            // Create form title
            const title = document.createElement('h3');
            title.className = 'cf-auth-form-title';
            title.textContent = 'Login';
            form.appendChild(title);
            
            // Email field
            const emailField = document.createElement('div');
            emailField.className = CLASSES.FIELD;
            
            const emailLabel = document.createElement('label');
            emailLabel.setAttribute('for', 'cf-auth-email');
            emailLabel.textContent = 'Email';
            emailField.appendChild(emailLabel);
            
            const emailInput = document.createElement('input');
            emailInput.type = 'email';
            emailInput.id = 'cf-auth-email';
            emailInput.name = 'email';
            emailInput.required = true;
            emailInput.placeholder = 'Enter your email';
            emailField.appendChild(emailInput);
            
            form.appendChild(emailField);
            
            // Password field
            const passwordField = document.createElement('div');
            passwordField.className = CLASSES.FIELD;
            
            const passwordLabel = document.createElement('label');
            passwordLabel.setAttribute('for', 'cf-auth-password');
            passwordLabel.textContent = 'Password';
            passwordField.appendChild(passwordLabel);
            
            const passwordInput = document.createElement('input');
            passwordInput.type = 'password';
            passwordInput.id = 'cf-auth-password';
            passwordInput.name = 'password';
            passwordInput.required = true;
            passwordInput.placeholder = 'Enter your password';
            passwordField.appendChild(passwordInput);
            
            form.appendChild(passwordField);
            
            // Remember me checkbox
            if (formOptions.showRememberMe) {
                const rememberField = document.createElement('div');
                rememberField.className = `${CLASSES.FIELD} cf-auth-checkbox-field`;
                
                const rememberCheckbox = document.createElement('input');
                rememberCheckbox.type = 'checkbox';
                rememberCheckbox.id = 'cf-auth-remember';
                rememberCheckbox.name = 'remember';
                rememberField.appendChild(rememberCheckbox);
                
                const rememberLabel = document.createElement('label');
                rememberLabel.setAttribute('for', 'cf-auth-remember');
                rememberLabel.textContent = 'Remember me';
                rememberField.appendChild(rememberLabel);
                
                form.appendChild(rememberField);
            }
            
            // Submit button
            const submitBtn = document.createElement('button');
            submitBtn.type = 'submit';
            submitBtn.className = CLASSES.BUTTON;
            submitBtn.textContent = 'Login';
            form.appendChild(submitBtn);
            
            // Links
            const linksDiv = document.createElement('div');
            linksDiv.className = 'cf-auth-links';
            
            if (formOptions.showForgotPassword) {
                const forgotLink = document.createElement('a');
                forgotLink.href = '/password/reset';
                forgotLink.className = 'cf-auth-forgot-link';
                forgotLink.textContent = 'Forgot Password?';
                linksDiv.appendChild(forgotLink);
            }
            
            if (formOptions.showRegisterLink) {
                if (formOptions.showForgotPassword) {
                    linksDiv.appendChild(document.createTextNode(' | '));
                }
                
                const registerLink = document.createElement('a');
                registerLink.href = '/register';
                registerLink.className = 'cf-auth-register-link';
                registerLink.textContent = 'Create Account';
                linksDiv.appendChild(registerLink);
            }
            
            form.appendChild(linksDiv);
            
            // Add form to container
            container.appendChild(form);
            
            // Initialize the form
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLogin(form);
            });
            
            // Mark as initialized
            form.setAttribute('data-cf-auth-initialized', 'true');
            
            return form;
        }
    };
    
    // Initialize on DOM content loaded if not explicitly initialized
    document.addEventListener('DOMContentLoaded', () => {
        // If no previous initialization
        if (!window.CarFuseAuthUI) {
            AuthUI.init();
        }
    });
    
    // Export globally
    window.CarFuseAuthUI = AuthUI;
    
    // Notify that Auth UI is loaded
    document.dispatchEvent(new CustomEvent('auth-ui:loaded'));
    
    console.info('CarFuse Auth UI loaded');
})();
