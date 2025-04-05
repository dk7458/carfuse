/**
 * CarFuse Authentication System
 * 
 * This module integrates and initializes all authentication-related components
 * to create a complete authentication and authorization system.
 */
(function() {
    // Check if all required components are available
    const components = {
        events: typeof window.CarFuseEvents !== 'undefined',
        auth: typeof window.AuthHelper !== 'undefined',
        rbac: typeof window.CarFuseRBAC !== 'undefined',
        toast: typeof window.CarFuseToast !== 'undefined',
        authUI: typeof window.CarFuseAuthUI !== 'undefined'
    };
    
    // Track what's loaded and what's missing
    const missingComponents = Object.entries(components)
        .filter(([key, exists]) => !exists)
        .map(([key]) => key);
    
    if (missingComponents.length > 0) {
        console.warn(
            'CarFuse Auth System: Some components are missing:',
            missingComponents.join(', ')
        );
    }
    
    /**
     * Auth System API
     */
    const AuthSystem = {
        // Component references for easier access
        events: window.CarFuseEvents,
        helper: window.AuthHelper,
        rbac: window.CarFuseRBAC,
        toast: window.CarFuseToast,
        ui: window.CarFuseAuthUI,
        
        // Track initialization state
        initialized: false,
        
        /**
         * Initialize all authentication components
         * @param {object} options - Configuration options
         */
        init: function(options = {}) {
            // Skip if already initialized
            if (this.initialized) {
                console.info('Auth System already initialized');
                return this;
            }
            
            // Default options
            const defaultOptions = {
                debug: false,
                enableToasts: true,
                autoApplyRBAC: true,
                customRoleHierarchy: null,
                resourceMappings: null,
                redirects: {
                    afterLogin: null,
                    afterLogout: '/'
                }
            };
            
            const config = { ...defaultOptions, ...options };
            
            // Enable debug mode if specified
            if (config.debug) {
                if (this.helper) this.helper.setDebug(true);
                if (this.events) this.events.setDebug(true);
                console.info('Auth System debug mode enabled');
            }
            
            // Configure role hierarchy if specified
            if (config.customRoleHierarchy && this.helper) {
                this.helper.roleHierarchy = { 
                    ...this.helper.roleHierarchy, 
                    ...config.customRoleHierarchy 
                };
            }
            
            // Configure redirect paths if specified
            if (config.redirects && this.helper) {
                this.helper.setRedirectPaths(config.redirects);
            }
            
            // Configure resource mappings if specified
            if (config.resourceMappings && this.rbac) {
                this.rbac.configureResourceAccess(config.resourceMappings);
            }
            
            // Initialize auth UI if available
            if (this.ui) {
                this.ui.init({
                    autoInitialize: true,
                    loginRedirect: config.redirects?.afterLogin,
                    logoutRedirect: config.redirects?.afterLogout
                });
            }
            
            // Apply RBAC automatically if enabled
            if (config.autoApplyRBAC && this.rbac && this.helper?.isAuthenticated()) {
                this.rbac.applyAccessControl();
            }
            
            // Mark as initialized
            this.initialized = true;
            
            // Dispatch system ready event
            if (this.events) {
                this.events.System.dispatchReady({
                    authSystemReady: true,
                    components: Object.keys(components).filter(key => components[key])
                });
            } else {
                document.dispatchEvent(new CustomEvent('system:ready', {
                    detail: {
                        authSystemReady: true,
                        components: Object.keys(components).filter(key => components[key])
                    }
                }));
            }
            
            console.info('CarFuse Auth System initialized');
            return this;
        },
        
        /**
         * Get current auth state
         * @returns {object|null} Current authentication state
         */
        getState: function() {
            return this.helper ? this.helper.getAuthState() : null;
        },
        
        /**
         * Initialize Alpine.js integration
         */
        initAlpine: function() {
            if (this.helper) {
                this.helper.registerAlpine();
            }
            return this;
        },
        
        /**
         * Check if user is authenticated
         * @returns {boolean} True if user is authenticated
         */
        isAuthenticated: function() {
            return this.helper ? this.helper.isAuthenticated() : false;
        },
        
        /**
         * Check if user has specified role
         * @param {string|string[]} roles - Role(s) to check
         * @returns {boolean} True if user has the role
         */
        hasRole: function(roles) {
            return this.helper ? this.helper.hasRole(roles) : false;
        },
        
        /**
         * Show a toast notification
         * @param {string} message - Message to display
         * @param {string} type - Toast type (success, error, info, warning)
         * @param {string} title - Toast title
         */
        notify: function(message, type = 'info', title = '') {
            if (!this.toast) return;
            
            if (type === 'success') {
                this.toast.success(message, title || 'Success');
            } else if (type === 'error') {
                this.toast.error(message, title || 'Error');
            } else if (type === 'warning') {
                this.toast.warning(message, title || 'Warning');
            } else {
                this.toast.info(message, title || 'Information');
            }
        },
        
        /**
         * Create a login UI component
         * @param {string} targetSelector - CSS selector for the container
         * @param {object} options - Login form options
         */
        createLoginUI: function(targetSelector, options = {}) {
            if (!this.ui) {
                console.error('Auth UI component is not available');
                return;
            }
            
            return this.ui.createLoginForm(targetSelector, options);
        }
    };
    
    // Auto-initialize on DOM content loaded
    document.addEventListener('DOMContentLoaded', () => {
        // Wait for other components to initialize
        setTimeout(() => {
            if (!AuthSystem.initialized) {
                AuthSystem.init();
            }
        }, 100);
    });
    
    // Expose to global scope
    window.CarFuseAuthSystem = AuthSystem;
    
    console.info('CarFuse Auth System module loaded');
})();
