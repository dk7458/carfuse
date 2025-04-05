/**
 * CarFuse HTMX Auth Extension
 * 
 * Adds authentication capabilities to HTMX requests:
 * - Automatically adds auth token to requests
 * - Handles auth errors and redirects
 * - Integrates with the CarFuse Events system
 */
(function() {
    // Check if htmx is available
    if (typeof htmx === 'undefined') {
        console.error('HTMX is required for the auth extension');
        return;
    }
    
    // Reference to the auth helper
    const getAuthHelper = () => window.AuthHelper;
    
    /**
     * Auth extension for HTMX
     */
    const authExtension = {
        // Extension name for registration
        name: 'auth',
        
        /**
         * Initialize the extension
         */
        init: function() {
            // Log initialization if AuthHelper is in debug mode
            if (getAuthHelper()?.debugMode) {
                console.debug('[HTMX-Auth] Initializing auth extension');
            }
        },
        
        /**
         * Add authentication headers to outgoing requests
         */
        onEvent: function(name, evt) {
            // Handle HTMX before request events
            if (name === 'htmx:beforeRequest') {
                const auth = getAuthHelper();
                if (!auth) return;
                
                // Add auth token to headers if authenticated
                if (auth.isAuthenticated()) {
                    evt.detail.headers['Authorization'] = `Bearer ${auth.getToken()}`;
                }
                
                // Add CSRF token 
                const csrfToken = auth.getCsrfToken();
                if (csrfToken) {
                    evt.detail.headers['X-CSRF-TOKEN'] = csrfToken;
                }
            }
            
            // Handle HTMX response errors related to auth
            if (name === 'htmx:responseError') {
                const auth = getAuthHelper();
                if (!auth) return;
                
                const xhr = evt.detail.xhr;
                const status = xhr.status;
                
                // Handle authorization errors
                if (status === 401 || status === 403) {
                    // Try to refresh token on 401 (unauthorized)
                    if (status === 401 && auth.isAuthenticated()) {
                        // Prevent default HTMX error behavior
                        evt.preventDefault();
                        
                        // Try to refresh the token
                        auth.refreshToken()
                            .then(() => {
                                // Retry original request with new token
                                const origTarget = evt.detail.target;
                                const origTrigger = evt.detail.requestConfig.trigger;
                                
                                // Dispatch event to notify UI of token refresh
                                if (window.CarFuseEvents) {
                                    window.CarFuseEvents.Auth.dispatchTokenRefreshed();
                                }
                                
                                // Trigger the request again
                                setTimeout(() => {
                                    htmx.trigger(origTrigger || origTarget, 'refresh-auth');
                                }, 100);
                            })
                            .catch(() => {
                                // If token refresh fails, handle as unauthorized
                                if (window.CarFuseEvents) {
                                    window.CarFuseEvents.Auth.dispatchUnauthorized({
                                        message: 'Authentication expired'
                                    });
                                }
                                
                                // Redirect to login
                                auth.redirectToLogin(false);
                            });
                    } 
                    // Handle forbidden (403) - user is authenticated but doesn't have permission
                    else if (status === 403) {
                        if (window.CarFuseEvents) {
                            window.CarFuseEvents.Auth.dispatchForbidden({
                                message: 'Access denied',
                                path: window.location.pathname,
                                resource: evt.detail.pathInfo?.resource
                            });
                        }
                    }
                }
            }
        },
        
        /**
         * Handle HTTP errors from the server
         */
        transformResponse: function(text, xhr, elt) {
            // Only handle errors
            if (xhr.status >= 200 && xhr.status < 300) {
                return text;
            }
            
            // Try to parse error response as JSON
            try {
                const error = JSON.parse(text);
                
                // Emit error event for error handling systems
                if (window.CarFuseEvents) {
                    const eventName = xhr.status === 401 
                        ? window.CarFuseEvents.NAMES.AUTH.UNAUTHORIZED 
                        : xhr.status === 403 
                            ? window.CarFuseEvents.NAMES.AUTH.FORBIDDEN
                            : window.CarFuseEvents.NAMES.SYSTEM.ERROR;
                    
                    window.CarFuseEvents.dispatch(eventName, {
                        status: xhr.status,
                        message: error.message || error.error || 'Server error',
                        details: error,
                        xhr: xhr
                    });
                }
                
                // Return formatted error message for display
                if (error.message || error.error) {
                    return `<div class="htmx-error">${error.message || error.error}</div>`;
                }
            } catch (e) {
                // If not JSON or other error, return original text
                return text;
            }
            
            return text;
        }
    };
    
    // Register the auth extension with HTMX
    htmx.defineExtension('auth', function() {
        return authExtension;
    });
    
    // Also register with CarFuseHTMX if available
    if (window.CarFuseHTMX && typeof window.CarFuseHTMX.registerExtension === 'function') {
        window.CarFuseHTMX.registerExtension('auth', authExtension);
    }
    
    // Notify that the auth extension is loaded
    document.dispatchEvent(new CustomEvent('htmx-auth-extension:loaded'));
    
    console.info('HTMX Auth Extension loaded');
})();
