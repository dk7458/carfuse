/**
 * CarFuse Alpine.js Base Component
 * Provides standardized patterns for Alpine.js components
 */

(function() {
    // Check if Alpine.js is available
    if (typeof Alpine === 'undefined') {
        console.error('Alpine.js is not loaded! Make sure to include Alpine.js before this script.');
        return;
    }
    
    // Check if CarFuseAlpine is available
    if (typeof window.CarFuseAlpine === 'undefined') {
        console.error('CarFuseAlpine is not available! Make sure to load it before this component.');
        return;
    }
    
    // Create a base component factory
    const createBaseComponent = (config = {}) => {
        return {
            // Component initialization state
            initialized: false,
            loading: false,
            error: null,
            
            // Default lifecycle hooks
            init() {
                this.loading = true;
                
                try {
                    // Set up error boundary
                    this.$el.setAttribute('x-error-boundary', '');
                    
                    // Initialize logger if available
                    this.logger = CarFuse.errorHandler?.createLogger(
                        this.$el.dataset.component || 'AlpineComponent'
                    );
                    
                    // Call custom init if available
                    if (typeof this.initialize === 'function') {
                        const result = this.initialize();
                        
                        // Handle async initialization
                        if (result instanceof Promise) {
                            result
                                .then(() => {
                                    this.initialized = true;
                                    this.loading = false;
                                    this.logger?.info('Component initialized successfully');
                                })
                                .catch(error => {
                                    this.handleError(error, 'initialization');
                                });
                            
                            return;
                        }
                    }
                    
                    // Synchronous init completed
                    this.initialized = true;
                    this.loading = false;
                    this.logger?.info('Component initialized successfully');
                } catch (error) {
                    this.handleError(error, 'initialization');
                }
            },
            
            // State management
            updateState(newState) {
                Object.assign(this, newState);
            },
            
            // Error handling
            handleError(error, context = 'operation') {
                this.error = error.message || `Error during ${context}`;
                this.loading = false;
                
                // Log the error
                if (this.logger) {
                    this.logger.error(`Error during ${context}`, error);
                } else {
                    console.error(`[Alpine Component] Error during ${context}:`, error);
                }
                
                // Process with global error handler if available
                if (CarFuse.errorHandler) {
                    const errorType = 
                        context === 'initialization' 
                            ? CarFuse.errorHandler.ErrorTypes.COMPONENT.INITIALIZATION
                            : context === 'update'
                                ? CarFuse.errorHandler.ErrorTypes.COMPONENT.UPDATE
                                : CarFuse.errorHandler.ErrorTypes.UI.INTERACTION;
                    
                    CarFuse.errorHandler.processError({
                        type: errorType,
                        originalError: error,
                        message: this.error,
                        source: this.$el.dataset.component || 'AlpineComponent',
                        context: {
                            componentId: this.$el.id || null,
                            elementSelector: this.getElementSelector(this.$el)
                        }
                    });
                }
                
                // Dispatch error event
                this.$dispatch('component-error', {
                    error,
                    context,
                    component: this.$el.dataset.component || 'AlpineComponent',
                    message: this.error
                });
            },
            
            // Get CSS selector for an element (for error reports)
            getElementSelector(element) {
                let selector = element.tagName.toLowerCase();
                if (element.id) selector += `#${element.id}`;
                if (element.className) {
                    const classes = element.className.split(/\s+/);
                    selector += classes.map(c => `.${c}`).join('');
                }
                return selector;
            },
            
            // Reset error state
            clearError() {
                this.error = null;
            },
            
            // Async operation wrapper with loading state
            async withLoading(operation, context = 'operation') {
                this.loading = true;
                this.clearError();
                
                try {
                    // Start performance measurement if logger available
                    this.logger?.mark(`${context}-start`);
                    
                    const result = await operation();
                    
                    // End performance measurement
                    this.logger?.measure(`${context}-start`, `${context} completed`);
                    
                    return result;
                } catch (error) {
                    this.handleError(error, context);
                    throw error;
                } finally {
                    this.loading = false;
                }
            },
            
            // Show a toast notification
            showToast(title, message, type = 'info', options = {}) {
                if (CarFuse.errorHandler) {
                    CarFuse.errorHandler.showToast(title, message, type, options);
                } else {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: {
                            title,
                            message,
                            type,
                            ...options
                        }
                    }));
                }
            }
        };
    };
    
    // Register the base component
    if (window.CarFuseAlpine.registerComponent) {
        window.CarFuseAlpine.registerComponent('baseComponent', createBaseComponent);
    }
    
    // Create a higher-order component factory
    window.CarFuseAlpine.createComponent = function(name, factory, options = {}) {
        if (typeof factory !== 'function') {
            console.error(`Invalid component factory for "${name}"`);
            return;
        }
        
        // Create the component with base functionality
        const componentFactory = (params = {}) => {
            const baseComponent = createBaseComponent(options);
            const customComponent = factory(params);
            
            // Merge the custom component with the base component
            return {
                ...baseComponent,
                ...customComponent,
                
                // Make sure init() calls both base and custom initialization
                init() {
                    baseComponent.init.call(this);
                    
                    if (customComponent.init) {
                        try {
                            customComponent.init.call(this);
                        } catch (error) {
                            baseComponent.handleError.call(this, error, 'custom initialization');
                        }
                    }
                }
            };
        };
        
        // Register the component
        window.CarFuseAlpine.registerComponent(name, componentFactory);
        
        return componentFactory;
    };
})();
