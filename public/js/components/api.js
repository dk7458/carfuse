/**
 * CarFuse API Component
 * Standardizes API communication with error handling and authentication
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Define component name
    const COMPONENT_NAME = 'api';
    
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
            baseUrl: '',
            defaultHeaders: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            retryCount: 3,
            timeout: 30000
        },
        
        // State
        state: {
            initialized: false,
            requestCount: 0,
            activeRequests: new Set()
        },
        
        /**
         * Initialize API functionalities
         * @param {Object} options - Configuration options
         */
        init: function(options = {}) {
            // Apply custom options
            Object.assign(this.config, options);
            
            // Set base URL if not specified
            if (!this.config.baseUrl) {
                this.config.baseUrl = window.CarFuse?.config?.baseUrl || window.location.origin;
            }
            
            this.log('Initializing API component');
            this.state.initialized = true;
            this.log('API component initialized');
            
            return Promise.resolve();
        },
        
        /**
         * Log a message to the console if debug mode is enabled
         * @param {string} message - Message to log
         * @param {*} [data] - Optional data to include in log
         */
        log: function(message, data) {
            if (this.config.debug || CarFuse.config.debug) {
                console.log(`[CarFuse API] ${message}`, data || '');
            }
        },
        
        /**
         * Standardized fetch wrapper with error handling, auth, and CSRF
         * @param {string} url - URL to fetch
         * @param {object} options - Fetch options
         * @param {number} retries - Number of retries
         * @returns {Promise} Fetch promise
         */
        fetch: function(url, options = {}, retries = 3) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            let headers = {...(options.headers || {})};
            
            // Add CSRF token to headers
            if (csrfToken) {
                headers['X-CSRF-Token'] = csrfToken;
            }
            
            // Add authentication header if available
            if (window.AuthHelper && window.AuthHelper.isAuthenticated) {
                const authToken = window.AuthHelper.getToken();
                if (authToken) {
                    headers['Authorization'] = `Bearer ${authToken}`;
                }
            }
            
            // Set default content type if not already set
            if (!headers['Content-Type'] && !(options.body instanceof FormData)) {
                headers['Content-Type'] = 'application/json';
            }
            
            const fetchOptions = {
                ...options,
                headers: headers
            };
            
            // Track the request
            const requestId = ++this.state.requestCount;
            this.state.activeRequests.add(requestId);
            
            return fetch(url, fetchOptions)
                .then(response => {
                    if (response.status === 401) {
                        // Authentication error, try to refresh token
                        if (window.AuthHelper && window.AuthHelper.refreshToken) {
                            return window.AuthHelper.refreshToken()
                                .then(() => {
                                    // Retry the request after refreshing token
                                    if (retries > 0) {
                                        this.log('Retrying request after token refresh', { url, retries });
                                        return this.fetch(url, options, retries - 1);
                                    } else {
                                        this.showToast('Błąd', 'Sesja wygasła. Zaloguj się ponownie.', 'error');
                                        window.location.href = '/login';
                                        throw new Error('Sesja wygasła');
                                    }
                                })
                                .catch(() => {
                                    this.showToast('Błąd', 'Sesja wygasła. Zaloguj się ponownie.', 'error');
                                    window.location.href = '/login';
                                    throw new Error('Sesja wygasła');
                                });
                        } else {
                            this.showToast('Błąd', 'Sesja wygasła. Zaloguj się ponownie.', 'error');
                            window.location.href = '/login';
                            throw new Error('Sesja wygasła');
                        }
                    } else if (!response.ok) {
                        // Handle other HTTP errors
                        let message = `Wystąpił błąd: ${response.status} ${response.statusText}`;
                        
                        switch (response.status) {
                            case 403:
                                message = 'Brak uprawnień do wykonania tej operacji.';
                                break;
                            case 404:
                                message = 'Nie znaleziono zasobu.';
                                break;
                            case 500:
                                message = 'Błąd serwera. Spróbuj ponownie później.';
                                break;
                        }
                        
                        this.showToast('Błąd', message, 'error');
                        throw new Error(message);
                    }
                    
                    return response.json();
                })
                .catch(error => {
                    this.log('API request failed', { url, error });
                    if (!error.message.includes('Sesja wygasła')) {
                        this.showToast('Błąd', 'Wystąpił błąd podczas komunikacji z serwerem.', 'error');
                    }
                    throw error;
                })
                .finally(() => {
                    // Remove from active requests
                    this.state.activeRequests.delete(requestId);
                });
        },
        
        /**
         * Show toast notification
         * @param {string} title - Toast title
         * @param {string} message - Toast message
         * @param {string} type - Toast type: success, error, warning, info
         */
        showToast: function(title, message, type = 'success') {
            // Check if we have notifications component available
            if (CarFuse.notifications && CarFuse.notifications.showToast) {
                CarFuse.notifications.showToast(title, message, type);
                return;
            }
            
            // Check if we have Alpine.js toast system
            const toastSystem = document.querySelector('[x-data*="toastSystem"]');
            
            if (toastSystem && window.Alpine) {
                // Use Alpine.js toast system
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { title, message, type }
                }));
            } else {
                // Fallback to simple toast implementation
                alert(`${title}: ${message}`);
            }
        }
    };
    
    // Register the component
    CarFuse[COMPONENT_NAME] = component;
    
    // Register with CarFuse if available
    if (CarFuse.registerComponent) {
        CarFuse.registerComponent(COMPONENT_NAME, component);
    }
})();
