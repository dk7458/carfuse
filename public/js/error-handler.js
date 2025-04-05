/**
 * CarFuse Error Handler
 * Comprehensive error handling, logging, and user feedback system
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    /**
     * Standard error types
     * Provides a hierarchy of error types for consistent error handling
     */
    const ErrorTypes = {
        // System errors
        SYSTEM: {
            INITIALIZATION: 'system.initialization',
            CONFIGURATION: 'system.configuration',
            DEPENDENCY: 'system.dependency',
        },
        // Network errors
        NETWORK: {
            CONNECTION: 'network.connection',
            TIMEOUT: 'network.timeout',
            REQUEST_FAILED: 'network.request_failed',
            RESPONSE_PARSING: 'network.response_parsing',
            OFFLINE: 'network.offline',
        },
        // Authentication errors
        AUTH: {
            UNAUTHORIZED: 'auth.unauthorized',
            FORBIDDEN: 'auth.forbidden',
            SESSION_EXPIRED: 'auth.session_expired',
            TOKEN_INVALID: 'auth.token_invalid',
        },
        // Data errors
        DATA: {
            VALIDATION: 'data.validation',
            NOT_FOUND: 'data.not_found',
            CONFLICT: 'data.conflict',
            INTEGRITY: 'data.integrity',
        },
        // UI errors
        UI: {
            RENDERING: 'ui.rendering',
            INTERACTION: 'ui.interaction',
            ANIMATION: 'ui.animation',
        },
        // Component errors
        COMPONENT: {
            INITIALIZATION: 'component.initialization',
            MOUNT: 'component.mount',
            UPDATE: 'component.update',
            DESTROY: 'component.destroy',
        }
    };
    
    /**
     * Standard HTTP status to error type mapping
     */
    const HttpStatusToErrorType = {
        400: ErrorTypes.DATA.VALIDATION,
        401: ErrorTypes.AUTH.UNAUTHORIZED,
        403: ErrorTypes.AUTH.FORBIDDEN,
        404: ErrorTypes.DATA.NOT_FOUND,
        409: ErrorTypes.DATA.CONFLICT,
        422: ErrorTypes.DATA.VALIDATION,
        500: ErrorTypes.SYSTEM.INITIALIZATION,
        502: ErrorTypes.NETWORK.REQUEST_FAILED,
        503: ErrorTypes.NETWORK.REQUEST_FAILED,
        504: ErrorTypes.NETWORK.TIMEOUT
    };
    
    /**
     * User-friendly error messages by error type
     */
    const DefaultErrorMessages = {
        // System errors
        [ErrorTypes.SYSTEM.INITIALIZATION]: 'System initialization failed. Please refresh the page.',
        [ErrorTypes.SYSTEM.CONFIGURATION]: 'System configuration error. Please contact support.',
        [ErrorTypes.SYSTEM.DEPENDENCY]: 'A required system component failed to load.',
        
        // Network errors
        [ErrorTypes.NETWORK.CONNECTION]: 'Connection error. Please check your internet connection.',
        [ErrorTypes.NETWORK.TIMEOUT]: 'The request timed out. Please try again.',
        [ErrorTypes.NETWORK.REQUEST_FAILED]: 'The request failed. Please try again later.',
        [ErrorTypes.NETWORK.RESPONSE_PARSING]: 'Failed to process server response.',
        [ErrorTypes.NETWORK.OFFLINE]: 'You appear to be offline. Please check your internet connection.',
        
        // Authentication errors
        [ErrorTypes.AUTH.UNAUTHORIZED]: 'You need to log in to access this feature.',
        [ErrorTypes.AUTH.FORBIDDEN]: 'You don\'t have permission to access this feature.',
        [ErrorTypes.AUTH.SESSION_EXPIRED]: 'Your session has expired. Please log in again.',
        [ErrorTypes.AUTH.TOKEN_INVALID]: 'Authentication failed. Please log in again.',
        
        // Data errors
        [ErrorTypes.DATA.VALIDATION]: 'Please check the form for errors and try again.',
        [ErrorTypes.DATA.NOT_FOUND]: 'The requested data could not be found.',
        [ErrorTypes.DATA.CONFLICT]: 'There was a conflict with the data. Please refresh and try again.',
        [ErrorTypes.DATA.INTEGRITY]: 'Data integrity error. Please try again.',
        
        // UI errors
        [ErrorTypes.UI.RENDERING]: 'Failed to display the interface correctly. Please refresh.',
        [ErrorTypes.UI.INTERACTION]: 'There was a problem processing your action.',
        [ErrorTypes.UI.ANIMATION]: 'Animation error. This won\'t affect functionality.',
        
        // Component errors
        [ErrorTypes.COMPONENT.INITIALIZATION]: 'Failed to initialize component.',
        [ErrorTypes.COMPONENT.MOUNT]: 'Failed to load the interface component.',
        [ErrorTypes.COMPONENT.UPDATE]: 'Failed to update the interface component.',
        [ErrorTypes.COMPONENT.DESTROY]: 'Failed to clean up component resources.'
    };

    /**
     * Logger provides standardized logging across the application
     */
    class Logger {
        constructor(moduleName = 'General', options = {}) {
            this.moduleName = moduleName;
            this.options = {
                level: 'info',
                remoteLogging: false,
                remoteLevel: 'error',
                remoteEndpoint: '/api/log',
                performance: false,
                ...options
            };
            
            // Log levels and their priorities
            this.levels = {
                debug: 0,
                info: 1,
                warn: 2,
                error: 3,
                silent: 4
            };
            
            // Performance marks
            this.marks = {};
        }
        
        /**
         * Should this message be logged based on current level?
         * @param {string} level - The level to check
         * @returns {boolean} Whether to log this message
         */
        shouldLog(level) {
            return this.levels[level] >= this.levels[this.options.level];
        }
        
        /**
         * Format a log message with context
         * @param {string} level - Log level
         * @param {string} message - Message to log
         * @param {any} data - Additional data
         * @returns {Object} Formatted log entry
         */
        formatLogEntry(level, message, data) {
            return {
                timestamp: new Date().toISOString(),
                level,
                module: this.moduleName,
                message,
                data,
                context: {
                    userAgent: navigator.userAgent,
                    url: window.location.href,
                    viewportSize: {
                        width: window.innerWidth,
                        height: window.innerHeight
                    }
                }
            };
        }
        
        /**
         * Send log entry to remote endpoint
         * @param {Object} entry - Log entry
         */
        async sendRemote(entry) {
            if (!this.options.remoteLogging || this.levels[entry.level] < this.levels[this.options.remoteLevel]) {
                return;
            }
            
            try {
                const response = await fetch(this.options.remoteEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(entry)
                });
                
                if (!response.ok) {
                    console.error('Failed to send log to remote endpoint', response.status);
                }
            } catch (error) {
                // Don't log this to avoid infinite loops
                console.error('Error sending remote log', error);
            }
        }
        
        /**
         * Log a debug message
         * @param {string} message - Message to log
         * @param {any} data - Additional data
         */
        debug(message, data) {
            if (this.shouldLog('debug')) {
                const entry = this.formatLogEntry('debug', message, data);
                console.debug(`[${entry.module}] ${message}`, data || '');
                this.sendRemote(entry);
            }
        }
        
        /**
         * Log an info message
         * @param {string} message - Message to log
         * @param {any} data - Additional data
         */
        info(message, data) {
            if (this.shouldLog('info')) {
                const entry = this.formatLogEntry('info', message, data);
                console.info(`[${entry.module}] ${message}`, data || '');
                this.sendRemote(entry);
            }
        }
        
        /**
         * Log a warning message
         * @param {string} message - Message to log
         * @param {any} data - Additional data
         */
        warn(message, data) {
            if (this.shouldLog('warn')) {
                const entry = this.formatLogEntry('warn', message, data);
                console.warn(`[${entry.module}] ${message}`, data || '');
                this.sendRemote(entry);
            }
        }
        
        /**
         * Log an error message
         * @param {string} message - Message to log
         * @param {Error|any} error - Error object or additional data
         */
        error(message, error) {
            if (this.shouldLog('error')) {
                const entry = this.formatLogEntry('error', message, {
                    message: error?.message,
                    stack: error?.stack,
                    originalError: error
                });
                console.error(`[${entry.module}] ${message}`, error || '');
                this.sendRemote(entry);
            }
        }
        
        /**
         * Start performance measurement
         * @param {string} name - Name of the mark
         */
        mark(name) {
            if (!this.options.performance) return;
            
            this.marks[name] = performance.now();
            if (window.performance && window.performance.mark) {
                window.performance.mark(`${this.moduleName}:${name}`);
            }
        }
        
        /**
         * End performance measurement and log result
         * @param {string} name - Name of the mark to end
         * @param {string} [label] - Optional label for the measurement
         */
        measure(name, label) {
            if (!this.options.performance || !this.marks[name]) return;
            
            const duration = performance.now() - this.marks[name];
            const measureName = label || `${name} duration`;
            
            if (window.performance && window.performance.measure) {
                try {
                    window.performance.measure(
                        `${this.moduleName}:${measureName}`,
                        `${this.moduleName}:${name}`
                    );
                } catch (e) {
                    // Ignore missing mark errors
                }
            }
            
            this.debug(`${measureName}: ${duration.toFixed(2)}ms`, { name, duration });
            delete this.marks[name];
            return duration;
        }
    }
    
    /**
     * Main error handler class
     */
    class ErrorHandler {
        constructor(options = {}) {
            this.options = {
                showToasts: true,
                logToConsole: true,
                remoteReporting: true,
                remoteEndpoint: '/api/error',
                captureGlobalErrors: true,
                ...options
            };
            
            this.logger = new Logger('ErrorHandler', {
                level: options.logLevel || 'info',
                remoteLogging: this.options.remoteReporting,
                remoteEndpoint: this.options.remoteEndpoint
            });
            
            this.errorHistory = [];
            this.recoveryStrategies = new Map();
            
            // Register default recovery strategies
            this.registerDefaultRecoveryStrategies();
        }
        
        /**
         * Initialize the error handler
         */
        init() {
            if (this.options.captureGlobalErrors) {
                this.setupGlobalErrorHandlers();
            }
            
            // Check for offline status
            window.addEventListener('online', () => {
                this.handleOnlineStatusChange(true);
            });
            
            window.addEventListener('offline', () => {
                this.handleOnlineStatusChange(false);
            });
            
            this.logger.info('Error handler initialized');
            return this;
        }
        
        /**
         * Set up global error handlers
         */
        setupGlobalErrorHandlers() {
            // Uncaught exceptions
            window.addEventListener('error', (event) => {
                this.handleUncaughtError(event);
            });
            
            // Unhandled promise rejections
            window.addEventListener('unhandledrejection', (event) => {
                this.handleUnhandledRejection(event);
            });
            
            // HTMX errors if HTMX is available
            if (typeof htmx !== 'undefined') {
                document.addEventListener('htmx:error', (event) => {
                    this.handleHtmxError(event);
                });
            }
            
            // Alpine errors if Alpine is available
            if (typeof Alpine !== 'undefined') {
                document.addEventListener('alpine:error', (event) => {
                    this.handleAlpineError(event);
                });
            }
        }
        
        /**
         * Handle uncaught errors
         * @param {ErrorEvent} event - Error event
         */
        handleUncaughtError(event) {
            const error = event.error || new Error(event.message || 'Unknown error');
            
            this.logger.error('Uncaught exception', error);
            
            this.processError({
                type: ErrorTypes.SYSTEM.INITIALIZATION,
                originalError: error,
                message: error.message,
                source: 'uncaught',
                context: {
                    filename: event.filename,
                    lineno: event.lineno,
                    colno: event.colno
                }
            });
        }
        
        /**
         * Handle unhandled promise rejections
         * @param {PromiseRejectionEvent} event - Rejection event
         */
        handleUnhandledRejection(event) {
            const reason = event.reason;
            const error = reason instanceof Error ? reason : new Error(String(reason));
            
            this.logger.error('Unhandled promise rejection', error);
            
            this.processError({
                type: ErrorTypes.SYSTEM.INITIALIZATION,
                originalError: error,
                message: error.message,
                source: 'promise',
                context: {
                    reason: reason
                }
            });
        }
        
        /**
         * Handle HTMX errors
         * @param {CustomEvent} event - HTMX error event
         */
        handleHtmxError(event) {
            const error = event.detail.error || new Error('HTMX error');
            const xhr = event.detail.xhr;
            
            this.logger.error('HTMX error', error);
            
            let errorType = ErrorTypes.NETWORK.REQUEST_FAILED;
            if (xhr) {
                errorType = HttpStatusToErrorType[xhr.status] || errorType;
            }
            
            this.processError({
                type: errorType,
                originalError: error,
                message: error.message,
                source: 'htmx',
                context: {
                    ...event.detail
                }
            });
        }
        
        /**
         * Handle Alpine.js errors
         * @param {CustomEvent} event - Alpine error event
         */
        handleAlpineError(event) {
            const error = event.detail.error || new Error('Alpine error');
            
            this.logger.error('Alpine error', error);
            
            this.processError({
                type: ErrorTypes.UI.RENDERING,
                originalError: error,
                message: error.message,
                source: 'alpine',
                context: {
                    ...event.detail,
                    expression: event.detail.expression
                }
            });
        }
        
        /**
         * Handle online status change
         * @param {boolean} isOnline - Whether now online
         */
        handleOnlineStatusChange(isOnline) {
            if (isOnline) {
                this.logger.info('Application is online');
                this.showToast('Connection Restored', 'You\'re back online.', 'success');
                
                // Attempt to recover pending operations
                this.applyRecoveryStrategy(ErrorTypes.NETWORK.OFFLINE);
            } else {
                this.logger.warn('Application is offline');
                this.processError({
                    type: ErrorTypes.NETWORK.OFFLINE,
                    message: 'You are offline',
                    source: 'network',
                    silent: true // Don't show error toast, we'll show an offline toast
                });
                
                this.showToast('You\'re Offline', 'Check your internet connection.', 'warning', {
                    duration: 0, // Persistent until back online
                    id: 'offline-notification'
                });
            }
        }
        
        /**
         * Process an error through the error handling pipeline
         * @param {Object} errorInfo - Error information
         */
        processError(errorInfo) {
            const now = new Date();
            const errorEntry = {
                id: `error-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
                timestamp: now.toISOString(),
                type: errorInfo.type || ErrorTypes.SYSTEM.INITIALIZATION,
                message: errorInfo.message || 'An error occurred',
                originalError: errorInfo.originalError,
                source: errorInfo.source || 'application',
                context: {
                    url: window.location.href,
                    userAgent: navigator.userAgent,
                    ...errorInfo.context
                },
                handled: false,
                recovered: false
            };
            
            // Add to error history
            this.errorHistory.push(errorEntry);
            
            // Trim error history if it gets too large
            if (this.errorHistory.length > 100) {
                this.errorHistory.shift();
            }
            
            // Attempt to apply recovery strategy
            const recovered = this.applyRecoveryStrategy(errorEntry.type, errorEntry);
            errorEntry.recovered = recovered;
            
            // Show user feedback if not silent
            if (!errorInfo.silent && this.options.showToasts) {
                const friendlyMessage = this.getUserFriendlyMessage(errorEntry.type, errorEntry.message);
                this.showToast('Error', friendlyMessage, 'error', {
                    errorId: errorEntry.id,
                    recoverable: Boolean(this.recoveryStrategies.has(errorEntry.type))
                });
            }
            
            // Report error remotely
            if (this.options.remoteReporting) {
                this.reportErrorRemotely(errorEntry);
            }
            
            return errorEntry;
        }
        
        /**
         * Get a user-friendly error message
         * @param {string} errorType - Type of error
         * @param {string} originalMessage - Original error message
         * @returns {string} User-friendly message
         */
        getUserFriendlyMessage(errorType, originalMessage) {
            // Return default message for this error type, falling back to original message
            return DefaultErrorMessages[errorType] || originalMessage || 'An unexpected error occurred';
        }
        
        /**
         * Show a toast notification
         * @param {string} title - Toast title
         * @param {string} message - Toast message
         * @param {string} type - Toast type (success, error, warning, info)
         * @param {Object} options - Additional toast options
         */
        showToast(title, message, type = 'info', options = {}) {
            // If we have a toast component in CarFuse, use it
            if (CarFuse.notifications && CarFuse.notifications.showToast) {
                CarFuse.notifications.showToast(title, message, type, options);
                return;
            }
            
            // Use Alpine.js toast if available
            const toastSystem = document.querySelector('[x-data*="toastSystem"]');
            if (toastSystem && window.Alpine) {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { 
                        title, 
                        message, 
                        type, 
                        ...options 
                    }
                }));
                return;
            }
            
            // Dispatch a general toast event
            window.dispatchEvent(new CustomEvent('carfuse:toast', {
                detail: { 
                    title, 
                    message, 
                    type, 
                    ...options 
                }
            }));
            
            // Fallback to console if no toast system is available
            if (this.options.logToConsole) {
                const method = type === 'error' ? 'error' : type === 'warning' ? 'warn' : 'info';
                console[method](`${title}: ${message}`);
            }
        }
        
        /**
         * Report an error to a remote endpoint
         * @param {Object} errorEntry - Error entry to report
         */
        reportErrorRemotely(errorEntry) {
            // Don't send certain types of errors remotely
            if (errorEntry.type === ErrorTypes.NETWORK.OFFLINE) {
                return; // No need to try reporting when user is offline
            }
            
            // Prepare the error report
            const report = {
                ...errorEntry,
                // Don't include the full error object in the report
                originalError: undefined,
                errorMessage: errorEntry.originalError?.message,
                errorName: errorEntry.originalError?.name,
                errorStack: errorEntry.originalError?.stack,
                userAgent: navigator.userAgent,
                language: navigator.language,
                viewport: {
                    width: window.innerWidth,
                    height: window.innerHeight
                },
                timestamp: new Date().toISOString()
            };
            
            // Send error report
            fetch(this.options.remoteEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(report),
                // Use keepalive to ensure the request completes even if navigating away
                keepalive: true
            }).catch(e => {
                // Silently fail if we can't report the error
                this.logger.debug('Failed to report error remotely', e);
            });
        }
        
        /**
         * Register a recovery strategy for an error type
         * @param {string} errorType - Type of error to handle
         * @param {Function} strategy - Recovery function
         */
        registerRecoveryStrategy(errorType, strategy) {
            if (typeof strategy !== 'function') {
                throw new Error('Recovery strategy must be a function');
            }
            
            this.recoveryStrategies.set(errorType, strategy);
        }
        
        /**
         * Apply a recovery strategy for an error
         * @param {string} errorType - Type of error
         * @param {Object} errorEntry - Error entry
         * @returns {boolean} Whether recovery was attempted
         */
        applyRecoveryStrategy(errorType, errorEntry) {
            const strategy = this.recoveryStrategies.get(errorType);
            if (!strategy) return false;
            
            try {
                strategy(errorEntry);
                return true;
            } catch (e) {
                this.logger.error('Error in recovery strategy', e);
                return false;
            }
        }
        
        /**
         * Register default recovery strategies
         */
        registerDefaultRecoveryStrategies() {
            // Session expired recovery - redirect to login
            this.registerRecoveryStrategy(ErrorTypes.AUTH.SESSION_EXPIRED, () => {
                if (window.AuthHelper && typeof window.AuthHelper.redirectToLogin === 'function') {
                    window.AuthHelper.redirectToLogin();
                } else {
                    window.location.href = '/login';
                }
            });
            
            // Network offline recovery - retry when back online
            this.registerRecoveryStrategy(ErrorTypes.NETWORK.OFFLINE, () => {
                // When coming back online, pending requests will be handled by the online event
            });
            
            // Token invalid recovery - refresh token
            this.registerRecoveryStrategy(ErrorTypes.AUTH.TOKEN_INVALID, () => {
                if (window.AuthHelper && typeof window.AuthHelper.refreshToken === 'function') {
                    window.AuthHelper.refreshToken()
                        .catch(() => {
                            // If token refresh fails, redirect to login
                            if (window.AuthHelper && typeof window.AuthHelper.redirectToLogin === 'function') {
                                window.AuthHelper.redirectToLogin();
                            }
                        });
                }
            });
        }
        
        /**
         * Create specialized error with specific type
         * @param {string} message - Error message
         * @param {string} type - Error type
         * @param {Object} data - Additional error data
         * @returns {Error} Typed error object
         */
        createError(message, type, data = {}) {
            const error = new Error(message);
            error.errorType = type;
            error.errorData = data;
            return error;
        }
        
        /**
         * Create a new logger instance
         * @param {string} moduleName - Name of the module
         * @param {Object} options - Logger options
         * @returns {Logger} New logger instance
         */
        createLogger(moduleName, options = {}) {
            return new Logger(moduleName, {
                ...this.options,
                ...options
            });
        }
        
        /**
         * Get the error history
         * @returns {Array} Array of error entries
         */
        getErrorHistory() {
            return [...this.errorHistory];
        }
        
        /**
         * Clear error history
         */
        clearErrorHistory() {
            this.errorHistory = [];
        }
    }
    
    // Create main error handler instance
    const errorHandler = new ErrorHandler({
        showToasts: true,
        logToConsole: true,
        remoteReporting: window.location.hostname !== 'localhost',
        logLevel: window.location.hostname === 'localhost' ? 'debug' : 'info',
        captureGlobalErrors: true
    });
    
    // Export error types
    errorHandler.ErrorTypes = ErrorTypes;
    
    // Register the error handler
    CarFuse.errorHandler = errorHandler;
    
    // Create a global logger
    CarFuse.logger = errorHandler.createLogger('CarFuse');
    
    // Initialize when document is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => errorHandler.init());
    } else {
        errorHandler.init();
    }
    
    // Export Logger class for creating custom loggers
    CarFuse.Logger = Logger;
    
    // Expose error creation API globally
    CarFuse.createError = (message, type, data) => errorHandler.createError(message, type, data);
    
    console.log('CarFuse Error Handler loaded');
})();
