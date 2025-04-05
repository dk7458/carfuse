/**
 * CarFuse Error Handler Component
 * Manages global error catching and standardized error presentation
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Define component name
    const COMPONENT_NAME = 'errorHandler';
    
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
            logErrorsToServer: true,
            errorLogEndpoint: '/api/log-error',
            showErrorNotifications: true
        },
        
        // State
        state: {
            initialized: false,
            errorCount: 0,
            lastError: null
        },
        
        /**
         * Initialize Error Handler functionalities
         * @param {Object} options - Configuration options
         */
        init: function(options = {}) {
            // Apply custom options
            Object.assign(this.config, options);
            
            this.log('Initializing Error Handler component');
            this.setupGlobalErrorHandling();
            this.state.initialized = true;
            this.log('Error Handler component initialized');
            
            return Promise.resolve();
        },
        
        /**
         * Log a message to the console if debug mode is enabled
         * @param {string} message - Message to log
         * @param {*} [data] - Optional data to include in log
         */
        log: function(message, data) {
            if (this.config.debug || CarFuse.config.debug) {
                console.log(`[CarFuse Error Handler] ${message}`, data || '');
            }
        },
        
        /**
         * Setup global error handling
         */
        setupGlobalErrorHandling: function() {
            this.log('Setting up global error handling');
            
            // Global error event listener
            window.addEventListener('error', (event) => {
                this.handleError(event.error, 'Uncaught exception');
            });
            
            // Unhandled promise rejection handler
            window.addEventListener('unhandledrejection', (event) => {
                this.handleError(event.reason, 'Unhandled promise rejection');
            });
        },
        
        /**
         * Handle an error
         * @param {Error} error - Error object
         * @param {string} context - Context of the error
         */
        handleError: function(error, context = 'Generic error') {
            this.log(`Handling error: ${context}`, error);
            
            // Format error message
            let message = `Wystąpił błąd: ${context}`;
            if (error && error.message) {
                message += ` - ${error.message}`;
            }
            
            // Log the error
            console.error('Error:', error);
            
            // Show user-friendly message
            this.showUserFriendlyError(message);
            
            // Log the error to the server (if possible)
            this.logErrorToServer(error, context);
        },
        
        /**
         * Show a user-friendly error message
         * @param {string} message - Error message to display
         */
        showUserFriendlyError: function(message) {
            if (CarFuse.notifications && CarFuse.notifications.showToast) {
                CarFuse.notifications.showToast('Błąd', message, 'error');
            } else {
                alert(`Błąd: ${message}`);
            }
        },
        
        /**
         * Log the error to the server
         * @param {Error} error - Error object
         * @param {string} context - Context of the error
         */
        logErrorToServer: function(error, context) {
            // Prepare error data
            const errorData = {
                message: error.message || 'Błąd bez wiadomości',
                stack: error.stack || 'Brak stack trace',
                context: context,
                url: window.location.href,
                userAgent: navigator.userAgent,
                timestamp: new Date().toISOString()
            };
            
            // Send error data to the server
            fetch(this.config.errorLogEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(errorData)
            }).catch(e => console.error('Failed to log error to server', e));
        }
    };
    
    // Register the component
    CarFuse[COMPONENT_NAME] = component;
    
    // Register with CarFuse if available
    if (CarFuse.registerComponent) {
        CarFuse.registerComponent(COMPONENT_NAME, component);
    }
})();
