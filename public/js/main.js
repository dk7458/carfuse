/**
 * CarFuse - Main JavaScript Orchestration Layer
 * Handles component initialization and application bootstrap
 */

// Define global namespace and configuration
window.CarFuse = {
    // Core configuration
    config: {
        debug: window.location.hostname === 'localhost' || false,
        baseUrl: window.location.origin,
        locale: 'pl-PL',
        currency: 'PLN',
        dateFormat: { year: 'numeric', month: '2-digit', day: '2-digit' },
        timeFormat: { hour: '2-digit', minute: '2-digit' }
    },
    
    // Application state
    state: {
        initialized: false,
        loading: false,
        components: new Set(),
        dependencies: {},
        errors: [],
        readyCallbacks: [] // Callbacks to execute when CarFuse is fully initialized
    },
    
    // Unified component registry with dependencies and metadata
    registry: {
        // Core CarFuse components
        'core': { 
            path: '/js/components/core.js', 
            dependencies: [],
            priority: 10, // Higher priority loads first
            type: 'core'
        },
        'auth': { 
            path: '/js/components/auth.js', 
            dependencies: ['core'],
            priority: 9,
            type: 'core'
        },
        'ui': { 
            path: '/js/components/ui.js', 
            dependencies: ['core'],
            priority: 8,
            type: 'core'
        },
        'events': { 
            path: '/js/components/events.js', 
            dependencies: ['core'],
            priority: 8,
            type: 'core'
        },
        'error-handler': { 
            path: '/js/components/error-handler.js', 
            dependencies: ['core', 'events'],
            priority: 9,
            type: 'core'
        },
        'storage': { 
            path: '/js/components/storage.js', 
            dependencies: ['core'],
            priority: 8,
            type: 'core'
        },
        'i18n': { 
            path: '/js/components/i18n.js', 
            dependencies: ['core'], // Removed storage dependency
            priority: 7,
            type: 'feature'
        },
        'loader': { 
            path: '/js/components/loader.js', 
            dependencies: ['core', 'ui'],
            priority: 7,
            type: 'feature'
        },
        'forms': { 
            path: '/js/components/forms.js', 
            dependencies: ['core', 'ui'],
            priority: 6,
            type: 'feature'
        },
        'validation': { 
            path: '/js/components/validation.js', 
            dependencies: ['core', 'forms'],
            priority: 6,
            type: 'feature'
        },
        'form-handler': { 
            path: '/js/components/form-handler.js', 
            dependencies: ['core', 'forms', 'validation'],
            priority: 5,
            type: 'feature'
        },
        'bookings': { 
            path: '/js/components/bookings.js', 
            dependencies: ['core', 'forms'],
            priority: 5,
            type: 'feature'
        },
        'payments': { 
            path: '/js/components/payments.js', 
            dependencies: ['core', 'forms'],
            priority: 5,
            type: 'feature'
        },
        'user': { 
            path: '/js/components/user.js', 
            dependencies: ['core', 'auth'],
            priority: 5,
            type: 'feature'
        },
        'theme': { 
            path: '/js/components/theme.js', 
            dependencies: ['core', 'storage'],
            priority: 6,
            type: 'feature'
        },
        'api': { 
            path: '/js/components/api.js', 
            dependencies: ['core', 'error-handler'],
            priority: 7,
            type: 'feature'
        },
        'search': { 
            path: '/js/components/search.js', 
            dependencies: ['core', 'api', 'forms'],
            priority: 4,
            type: 'feature'
        },
        'navigation': { 
            path: '/js/components/navigation.js', 
            dependencies: ['core', 'ui'],
            priority: 5,
            type: 'feature'
        },
        'analytics': { 
            path: '/js/components/analytics.js', 
            dependencies: ['core', 'storage'],
            priority: 3,
            type: 'feature'
        },
        'auth-ui': { 
            path: '/js/components/auth-ui.js', 
            dependencies: ['core', 'auth', 'ui'],
            priority: 4,
            type: 'feature'
        },

        // HTMX integration (will be loaded differently)
        'htmx': { 
            path: '/js/htmx.js',
            dependencies: ['core'],
            priority: 8,
            type: 'integration'
        },
        
        // Alpine.js integration (will be loaded differently)
        'alpine': { 
            path: '/js/alpine.js',
            dependencies: ['core'],
            priority: 8,
            type: 'integration'
        }
    },

    /**
     * Initialize the CarFuse framework
     * @param {Object} options - Initialization options
     * @returns {Promise} Promise resolved when initialization is complete
     */
    init: function(options = {}) {
        // Avoid multiple initializations
        if (this.state.initialized || this.state.loading) {
            return Promise.resolve(this);
        }
        
        this.state.loading = true;
        this.log('Initializing CarFuse framework');
        
        // Override config with provided options
        Object.assign(this.config, options);
        
        // Setup error boundaries for global error handling
        this._setupErrorBoundaries();
        
        // Load core components first
        return this._loadComponentsWithPriority(['core'])
            .then(() => {
                // Then load other essential components
                const essentialComponents = ['error-handler', 'storage', 'events'];
                return this._loadComponentsWithPriority(essentialComponents);
            })
            .then(() => {
                // Initialize auth if available
                this._initializeAuth();
                
                // Load HTMX and Alpine integrations
                const integrationComponents = ['htmx', 'alpine'];
                return this._loadComponentsWithPriority(integrationComponents);
            })
            .then(() => {
                // Initialize HTMX extensions and Alpine components
                return this._initializeHtmxExtensions()
                    .then(() => this._initializeAlpineComponents());
            })
            .then(() => {
                // Mark as initialized
                this.state.initialized = true;
                this.state.loading = false;
                this.log('CarFuse framework initialized');
                
                // Execute ready callbacks
                this._executeReadyCallbacks();
                
                return this;
            })
            .catch(error => {
                this.state.loading = false;
                this.logError('Failed to initialize CarFuse', error);
                this.state.errors.push(error);
                throw error;
            });
    },
    
    /**
     * Initialize HTMX extensions
     * @private
     */
    _initializeHtmxExtensions: function() {
        return new Promise(resolve => {
            if (window.CarFuseHTMX && window.CarFuseHTMX.extensions) {
                Object.keys(window.CarFuseHTMX.extensions).forEach(extensionName => {
                    this.log(`Initializing HTMX extension: ${extensionName}`);
                    // Any specific initialization logic can be added here
                });
            }
            resolve();
        });
    },
    
    /**
     * Initialize Alpine components
     * @private
     */
    _initializeAlpineComponents: function() {
        return new Promise(resolve => {
            if (window.CarFuseAlpine) {
                this.log('Initializing Alpine components');
                // Trigger a custom event to signal that Alpine components are ready
                document.dispatchEvent(new CustomEvent('carfuse:alpine-ready'));
            }
            resolve();
        });
    },

    /**
     * Register a callback to be executed when CarFuse is fully initialized
     * @param {Function} callback - Function to call when ready
     */
    ready: function(callback) {
        if (typeof callback !== 'function') return;
        
        if (this.state.initialized) {
            callback(this);
        } else {
            this.state.readyCallbacks.push(callback);
        }
    },
    
    /**
     * Execute all registered ready callbacks
     * @private
     */
    _executeReadyCallbacks: function() {
        while (this.state.readyCallbacks.length > 0) {
            const callback = this.state.readyCallbacks.shift();
            try {
                callback(this);
            } catch (error) {
                this.logError('Error in ready callback', error);
            }
        }
        
        // Dispatch a global event for components that use event-based initialization
        document.dispatchEvent(new CustomEvent('carfuse:ready', { detail: { carfuse: this } }));
    },
    
    /**
     * Register a component with CarFuse
     * @param {string} name - Component name
     * @param {Object} componentInstance - Component instance with init method
     * @param {Object} config - Optional configuration
     * @returns {Object} The registered component
     */
    registerComponent: function(name, componentInstance, config = {}) {
        if (this.state.components.has(name)) {
            this.log(`Component ${name} already registered`);
            return componentInstance;
        }
        
        if (!componentInstance || typeof componentInstance.init !== 'function') {
            this.logError(`Invalid component ${name}: missing init method`);
            return componentInstance;
        }
        
        // Add to registry if not already there
        if (!this.registry[name]) {
            this.registry[name] = {
                path: config.path || null,
                dependencies: config.dependencies || [],
                priority: config.priority || 5,
                type: config.type || 'feature',
                instance: componentInstance
            };
        } else {
            // Update registry entry with the component instance
            this.registry[name].instance = componentInstance;
            
            // Update registry with any new config settings
            if (config.dependencies) this.registry[name].dependencies = config.dependencies;
            if (config.priority) this.registry[name].priority = config.priority;
            if (config.type) this.registry[name].type = config.type;
        }
        
        // Track that we have this component
        this.state.components.add(name);
        
        // Log registration
        this.log(`Component ${name} registered`, { 
            dependencies: this.registry[name].dependencies,
            priority: this.registry[name].priority 
        });
        
        return componentInstance;
    },
    
    /**
     * Load and initialize specific components
     * @param {Array<string>} componentNames - Names of components to load
     * @returns {Promise} Promise resolved when all components are loaded
     */
    loadComponents: function(componentNames) {
        if (!Array.isArray(componentNames) || componentNames.length === 0) {
            return Promise.resolve();
        }
        
        this.log(`Loading components: ${componentNames.join(', ')}`);
        
        // Check dependencies first
        const allComponents = this._checkDependencies(componentNames);
        
        // Sort by priority and load
        return this._loadComponentsWithPriority(allComponents);
    },
    
    /**
     * Log a message if debug mode is enabled
     * @param {string} message - Message to log
     * @param {*} data - Optional data to log
     */
    log: function(message, data) {
        if (this.config.debug) {
            console.log(`[CarFuse] ${message}`, data || '');
        }
    },
    
    /**
     * Log an error message
     * @param {string} message - Error message
     * @param {Error} error - Error object
     */
    logError: function(message, error) {
        console.error(`[CarFuse Error] ${message}`, error);
    },
    
    /**
     * Set up global error boundaries
     * @private
     */
    _setupErrorBoundaries: function() {
        window.addEventListener('error', (event) => {
            this.logError('Uncaught exception', event.error);
            this.state.errors.push(event.error);
        });
        
        window.addEventListener('unhandledrejection', (event) => {
            this.logError('Unhandled promise rejection', event.reason);
            this.state.errors.push(event.reason);
        });
    },
    
    /**
     * Check and resolve dependencies for a list of components
     * @param {Array<string>} componentNames - Component names to check
     * @returns {Array<string>} All required components including dependencies
     * @private
     */
    _checkDependencies: function(componentNames) {
        const allComponents = new Set();
        const checkComponent = (name) => {
            if (allComponents.has(name)) return;
            
            allComponents.add(name);
            
            // Get dependencies from registry
            const component = this.registry[name];
            if (!component) {
                this.logError(`Component ${name} not found in registry`);
                return;
            }
            
            if (component.dependencies && component.dependencies.length > 0) {
                component.dependencies.forEach(dep => {
                    if (!this.state.components.has(dep)) {
                        checkComponent(dep);
                    }
                });
            }
        };
        
        // Start dependency check from each requested component
        componentNames.forEach(name => checkComponent(name));
        
        return Array.from(allComponents);
    },
    
    /**
     * Initialize authentication if available
     * @private
     */
    _initializeAuth: function() {
        if (window.AuthHelper && typeof window.AuthHelper.init === 'function') {
            this.log('Initializing AuthHelper');
            window.AuthHelper.init();
        }
    },
    
    /**
     * Load components sorted by priority
     * @param {Array<string>} componentNames - Names of components to load
     * @returns {Promise} Promise resolved when components are loaded
     * @private
     */
    _loadComponentsWithPriority: function(componentNames) {
        // Sort components by priority (higher priority loads first)
        const sortedComponents = componentNames
            .map(name => ({ name, priority: this.registry[name]?.priority || 5 }))
            .sort((a, b) => b.priority - a.priority)
            .map(item => item.name);
        
        // Load components sequentially to respect dependencies and priorities
        return sortedComponents.reduce(
            (promise, name) => promise.then(() => this._loadComponent(name)),
            Promise.resolve()
        );
    },
    
    /**
     * Load a specific component
     * @param {string} name - Component name
     * @returns {Promise} Promise resolved when component is loaded
     * @private
     */
    _loadComponent: function(name) {
        // Skip if already loaded
        if (this.state.components.has(name)) {
            this.log(`Component ${name} already loaded`);
            return Promise.resolve();
        }
        
        const component = this.registry[name];
        if (!component) {
            return Promise.reject(new Error(`Component ${name} not found in registry`));
        }
        
        // If component instance already exists, initialize it
        if (component.instance) {
            this.log(`Initializing component ${name}`);
            try {
                const result = component.instance.init();
                this.state.components.add(name);
                
                // Support async initialization
                if (result && typeof result.then === 'function') {
                    return result.then(() => {
                        this.log(`Component ${name} initialized (async)`);
                    });
                } else {
                    this.log(`Component ${name} initialized`);
                    return Promise.resolve();
                }
            } catch (error) {
                this.logError(`Failed to initialize component ${name}`, error);
                this.state.errors.push(error);
                return Promise.reject(error);
            }
        }
        
        // Otherwise, load the script if path is provided
        if (!component.path) {
            return Promise.reject(new Error(`Component ${name} has no path or instance`));
        }
        
        this.log(`Loading component ${name} from ${component.path}`);
        
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = component.path;
            script.async = true;
            
            script.onload = () => {
                this.log(`Component ${name} script loaded`);
                
                // The component should register itself, but we'll check
                if (!this.state.components.has(name)) {
                    if (CarFuse[name] && typeof CarFuse[name].init === 'function') {
                        this.registerComponent(name, CarFuse[name]);
                        try {
                            const result = CarFuse[name].init();
                            this.state.components.add(name);
                            
                            // Support async initialization
                            if (result && typeof result.then === 'function') {
                                result.then(() => {
                                    this.log(`Component ${name} initialized (async)`);
                                    resolve();
                                }).catch(error => {
                                    this.logError(`Failed to initialize component ${name}`, error);
                                    this.state.errors.push(error);
                                    reject(error);
                                });
                            } else {
                                this.log(`Component ${name} initialized`);
                                resolve();
                            }
                        } catch (error) {
                            this.logError(`Failed to initialize component ${name}`, error);
                            this.state.errors.push(error);
                            reject(error);
                        }
                    } else {
                        this.logError(`Component ${name} script loaded but did not register properly`);
                        reject(new Error(`Component ${name} did not register properly`));
                    }
                } else {
                    this.log(`Component ${name} registered itself`);
                    resolve();
                }
            };
            
            script.onerror = () => {
                const error = new Error(`Failed to load component ${name} from ${component.path}`);
                this.logError(`Failed to load component ${name}`, error);
                this.state.errors.push(error);
                reject(error);
            };
            
            document.head.appendChild(script);
        });
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Determine initial components based on page requirements
    // For example, check for data attributes on body to determine needed components
    const body = document.body;
    const requiredComponents = ['core', 'auth', 'ui', 'events', 'error-handler'];
    
    // Check for HTMX usage
    if (body.querySelector('[hx-get], [hx-post], [hx-put], [hx-delete], [hx-trigger]')) {
        requiredComponents.push('htmx');
    }
    
    // Check for Alpine.js usage
    if (body.querySelector('[x-data], [x-bind], [x-text], [x-html], [x-model]')) {
        requiredComponents.push('alpine');
    }
    
    // Check for form validation
    if (body.querySelector('form[data-validate]')) {
        requiredComponents.push('validation', 'form-handler');
    }
    
    // Check for theme features
    if (body.querySelector('#theme-toggle, [data-theme]')) {
        requiredComponents.push('theme');
    }
    
    // Initialize with detected components
    CarFuse.init({ components: requiredComponents })
        .catch(error => console.error('CarFuse initialization failed:', error));
    
    // Add CSRF token to all forms using AuthHelper
    document.querySelectorAll('form').forEach(form => {
        window.AuthHelper.addCsrfToForm(form);
    });
});
