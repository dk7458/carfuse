/**
 * CarFuse HTMX Auth Extension
 * Handles authentication concerns for HTMX requests
 * 
 * Usage:
 * <div hx-get="/api/protected" hx-ext="auth">Protected Content</div>
 */

(function() {
    // Ensure the CarFuseHTMX namespace exists
    if (typeof window.CarFuseHTMX !== 'object') {
        console.error('[CarFuseHTMX Auth] CarFuseHTMX namespace is not defined');
        return;
    }

    // Ensure HTMX is loaded
    if (typeof window.htmx !== 'object') {
        console.error('[CarFuseHTMX Auth] HTMX library is not loaded');
        return;
    }
    
    const authExtension = {
        onEvent: function(name, evt) {
            // Handle auth-related events
            if (name === "htmx:configRequest") {
                // Add authentication token to requests if available
                if (window.AuthHelper && window.AuthHelper.isAuthenticated()) {
                    const token = window.AuthHelper.getToken();
                    if (token) {
                        evt.detail.headers['Authorization'] = `Bearer ${token}`;
                    }
                }
            }
            
            // Handle 401/403 responses
            if (name === "htmx:responseError") {
                const xhr = evt.detail.xhr;
                if (xhr.status === 401) {
                    // Authentication failure
                    window.CarFuseHTMX.log('Auth extension: Authentication required');
                    
                    // Try to refresh token if we have an AuthHelper
                    if (window.AuthHelper && window.AuthHelper.refreshToken) {
                        window.AuthHelper.refreshToken()
                            .then(() => {
                                // Retry the original request
                                window.CarFuseHTMX.log('Auth extension: Retrying after token refresh');
                                const originalConfig = evt.detail.requestConfig;
                                window.htmx.ajax(
                                    originalConfig.verb,
                                    originalConfig.path,
                                    { source: evt.detail.elt, target: evt.detail.target }
                                );
                            })
                            .catch(() => {
                                // Token refresh failed, redirect to login
                                window.CarFuseHTMX.log('Auth extension: Token refresh failed');
                                
                                // Dispatch auth failure event
                                document.dispatchEvent(new CustomEvent('carfuse:auth-failure', {
                                    detail: { source: evt.detail.elt, xhr: xhr }
                                }));
                                
                                // Check for login redirect attribute or use default
                                const loginUrl = evt.detail.elt.getAttribute('data-auth-redirect') || '/login';
                                window.location.href = loginUrl;
                            });
                            
                        // Prevent default error handling
                        evt.stopPropagation();
                    }
                } else if (xhr.status === 403) {
                    // Authorization failure (forbidden)
                    window.CarFuseHTMX.log('Auth extension: Authorization failed (Forbidden)');
                    
                    // Dispatch forbidden event
                    document.dispatchEvent(new CustomEvent('carfuse:auth-forbidden', {
                        detail: { source: evt.detail.elt, xhr: xhr }
                    }));
                    
                    // Show forbidden message or redirect based on attribute
                    const forbiddenUrl = evt.detail.elt.getAttribute('data-forbidden-redirect');
                    if (forbiddenUrl) {
                        window.location.href = forbiddenUrl;
                        evt.stopPropagation();
                    }
                }
            }
        }
    };

    /**
     * Register the auth extension with HTMX
     */
    window.CarFuseHTMX.registerExtension('auth', authExtension);
    
    /**
     * Get the authenticated user data if available
     * @returns {object|null} User data or null if not authenticated
     */
    window.CarFuseHTMX.getAuthUser = function() {
        if (window.AuthHelper && window.AuthHelper.isAuthenticated()) {
            return window.AuthHelper.getUserData();
        }
        return null;
    };
    
    /**
     * Check if the user is authenticated
     * @returns {boolean} True if authenticated
     */
    window.CarFuseHTMX.isAuthenticated = function() {
        return window.AuthHelper ? window.AuthHelper.isAuthenticated() : false;
    };
    
    // Log initialization
    window.CarFuseHTMX.log('Auth extension initialized');
})();
