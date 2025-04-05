/**
 * CarFuse Auth UI Component
 * Handles UI updates based on authentication state
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Define component name
    const COMPONENT_NAME = 'authUi';
    
    // Check if already initialized
    if (CarFuse[COMPONENT_NAME]) {
        console.warn(`CarFuse ${COMPONENT_NAME} component already initialized.`);
        return;
    }
    
    // Define the component
    const component = {
        // Configuration
        config: {
            debug: false,
            autoUpdateOnAuth: true,
            updateSelectors: {
                username: '[data-auth-username]',
                role: '[data-auth-role]',
                showWhenAuth: '[data-auth-show]',
                hideWhenAuth: '[data-auth-hide]'
            }
        },
        
        // State
        state: {
            initialized: false,
            isAuthenticated: false
        },
        
        /**
         * Initialize Auth UI functionalities
         * @param {Object} options - Configuration options
         */
        init: function(options = {}) {
            // Apply custom options
            Object.assign(this.config, options);
            
            this.log('Initializing Auth UI component');
            this.setupAuthStateListeners();
            
            // Check current authentication status
            if (window.AuthHelper && typeof window.AuthHelper.isAuthenticated === 'function') {
                this.updateUI(window.AuthHelper.isAuthenticated());
            }
            
            this.state.initialized = true;
            this.log('Auth UI component initialized');
            
            return Promise.resolve();
        },
        
        /**
         * Log a message to the console if debug mode is enabled
         * @param {string} message - Message to log
         * @param {*} [data] - Optional data to include in log
         */
        log: function(message, data) {
            if (this.config.debug || CarFuse.config.debug) {
                console.log(`[CarFuse Auth UI] ${message}`, data || '');
            }
        },
        
        /**
         * Setup authentication state listeners
         */
        setupAuthStateListeners: function() {
            document.addEventListener('auth:stateChanged', (event) => {
                const isAuthenticated = event.detail?.authenticated || 
                    (window.AuthHelper && typeof window.AuthHelper.isAuthenticated === 'function' ? 
                    window.AuthHelper.isAuthenticated() : false);
                
                this.updateUI(isAuthenticated);
            });
        },
        
        /**
         * Update UI based on whether the user is authenticated
         * @param {boolean} isAuthenticated - Whether the user is authenticated
         */
        updateUI: function(isAuthenticated) {
            this.log(`Updating UI, authenticated: ${isAuthenticated}`);
            this.state.isAuthenticated = isAuthenticated;
            
            // Update elements with data-auth-show/hide attributes
            document.querySelectorAll(this.config.updateSelectors.showWhenAuth).forEach(el => {
                el.style.display = isAuthenticated ? '' : 'none';
            });
            
            document.querySelectorAll(this.config.updateSelectors.hideWhenAuth).forEach(el => {
                el.style.display = isAuthenticated ? 'none' : '';
            });
            
            // Update user information displays
            if (isAuthenticated && window.AuthHelper) {
                const userData = typeof window.AuthHelper.getUserData === 'function' ? 
                    window.AuthHelper.getUserData() : {};
                
                // Update user name displays
                document.querySelectorAll(this.config.updateSelectors.username).forEach(el => {
                    el.textContent = userData?.name || userData?.email || 'Użytkownik';
                });
                
                // Update user role displays
                const userRole = typeof window.AuthHelper.getUserRole === 'function' ? 
                    window.AuthHelper.getUserRole() : 'user';
                
                document.querySelectorAll(this.config.updateSelectors.role).forEach(el => {
                    el.textContent = this.translateRoleName(userRole);
                });
                
                // Update elements that should only be visible for certain roles
                document.querySelectorAll('[data-role-access]').forEach(el => {
                    const requiredRoles = el.dataset.roleAccess.split(',').map(r => r.trim());
                    el.style.display = requiredRoles.includes(userRole) ? '' : 'none';
                });
            }
            
            // Update login/logout buttons
            const loginButtons = document.querySelectorAll('.login-button, [data-auth="login"]');
            const logoutButtons = document.querySelectorAll('.logout-button, [data-auth="logout"]');
            
            loginButtons.forEach(btn => {
                btn.style.display = isAuthenticated ? 'none' : '';
            });
            
            logoutButtons.forEach(btn => {
                btn.style.display = isAuthenticated ? '' : 'none';
                
                // Ensure logout buttons have click handler
                if (!btn.hasAttribute('data-logout-handler')) {
                    btn.setAttribute('data-logout-handler', 'true');
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (window.AuthHelper && typeof window.AuthHelper.logout === 'function') {
                            window.AuthHelper.logout()
                                .catch(err => this.log('Logout error', err));
                        }
                    });
                }
            });
        },
        
        /**
         * Helper to translate role names to more user-friendly formats
         * @param {string} role - Role code
         * @returns {string} Translated role name
         */
        translateRoleName: function(role) {
            const roleMap = {
                'admin': 'Administrator',
                'manager': 'Menedżer',
                'staff': 'Pracownik',
                'user': 'Użytkownik',
                'customer': 'Klient'
            };
            
            return roleMap[role] || role;
        }
    };
    
    // Register the component
    CarFuse[COMPONENT_NAME] = component;
    
    // Register with CarFuse if available
    if (CarFuse.registerComponent) {
        CarFuse.registerComponent(COMPONENT_NAME, component);
    }
})();
