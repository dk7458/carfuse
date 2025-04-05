/**
 * CarFuse HTMX Integration
 * Main configuration and initialization for HTMX
 */

(function() {
    // Create global HTMX API namespace if it doesn't exist
    window.CarFuseHTMX = window.CarFuseHTMX || {
        config: {
            debug: false,
            defaultSwapStyle: 'transition:opacity 150ms',
            defaultSwapDelay: 0,
            historyCacheSize: 10,
            timeout: 20000,
            csrfTokenSelector: 'meta[name="csrf-token"]'
        },
        extensions: {},
        modules: {},
        isInitialized: false
    };

    // Track loaded resources
    const loadedResources = new Set();

    /**
     * Enable or disable debug mode
     * @param {boolean} enabled - Whether debug mode should be enabled
     */
    window.CarFuseHTMX.setDebug = function(enabled) {
        this.config.debug = !!enabled;
        this.log('Debug mode ' + (this.config.debug ? 'enabled' : 'disabled'));
    };
    
    /**
     * Log a message to console if debug mode is enabled
     * @param {string} message - Message to log
     * @param {*} data - Optional data to include in log
     */
    window.CarFuseHTMX.log = function(message, data) {
        if (this.config.debug) {
            console.log(`[CarFuseHTMX] ${message}`, data || '');
        }
    };

    /**
     * Get CSRF token for HTMX requests
     * @returns {string|null} CSRF token if available
     */
    window.CarFuseHTMX.getCsrfToken = function() {
        const tokenElement = document.querySelector(this.config.csrfTokenSelector);
        return tokenElement ? tokenElement.content : null;
    };
    
    /**
     * Load a JavaScript resource if it hasn't been loaded already
     * @param {string} path - Path to the JS file
     * @returns {Promise} Promise that resolves when the resource is loaded
     * @private
     */
    function loadResource(path) {
        if (loadedResources.has(path)) {
            window.CarFuseHTMX.log(`Resource already loaded: ${path}`);
            return Promise.resolve();
        }
        
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = path;
            script.async = true;
            
            script.onload = () => {
                loadedResources.add(path);
                window.CarFuseHTMX.log(`Loaded: ${path}`);
                resolve();
            };
            
            script.onerror = () => {
                const error = new Error(`Failed to load ${path}`);
                console.error(`[CarFuseHTMX] ${error.message}`);
                reject(error);
            };
            
            document.head.appendChild(script);
        });
    }
    
    /**
     * Configure HTMX with CarFuse settings
     * @private
     */
    function configureHtmx() {
        if (!window.htmx) {
            console.error('[CarFuseHTMX] HTMX not loaded, cannot configure');
            return;
        }
        
        // Configure HTMX global settings
        window.htmx.config.defaultSwapStyle = window.CarFuseHTMX.config.defaultSwapStyle;
        window.htmx.config.defaultSwapDelay = window.CarFuseHTMX.config.defaultSwapDelay;
        window.htmx.config.globalViewTransitions = true;
        window.htmx.config.historyCacheSize = window.CarFuseHTMX.config.historyCacheSize;
        window.htmx.config.allowEval = false; // For security
        window.htmx.config.allowScriptTags = false; // For security
        window.htmx.config.timeout = window.CarFuseHTMX.config.timeout;
        
        // Setup CSRF token for HTMX requests
        document.body.addEventListener('htmx:configRequest', (event) => {
            const csrfToken = window.CarFuseHTMX.getCsrfToken();
            if (csrfToken) {
                event.detail.headers['X-CSRF-Token'] = csrfToken;
            }
        });
        
        // Log HTMX events in debug mode
        if (window.CarFuseHTMX.config.debug) {
            document.body.addEventListener('htmx:beforeRequest', (event) => {
                window.CarFuseHTMX.log('HTMX Request:', {
                    url: event.detail.requestConfig.path,
                    target: event.detail.elt
                });
            });
            
            document.body.addEventListener('htmx:responseError', (event) => {
                window.CarFuseHTMX.log('HTMX Error:', {
                    status: event.detail.xhr.status,
                    url: event.detail.xhr.responseURL
                });
            });
        }
    }
    
    /**
     * Register an HTMX extension
     * @param {string} name - Extension name
     * @param {object} extension - Extension definition
     */
    window.CarFuseHTMX.registerExtension = function(name, extension) {
        if (window.htmx && window.htmx.defineExtension) {
            window.htmx.defineExtension(name, extension);
            this.extensions[name] = extension;
            this.log(`Registered HTMX extension: ${name}`);
        } else {
            console.error(`[CarFuseHTMX] Cannot register extension ${name} - HTMX not loaded`);
        }
    };
    
    /**
     * Register a CarFuse HTMX module
     * @param {string} name - Module name
     * @param {object} module - Module implementation
     */
    window.CarFuseHTMX.registerModule = function(name, module) {
        this.modules[name] = module;
        this.log(`Registered HTMX module: ${name}`);
        
        // Initialize module if HTMX is already loaded
        if (this.isInitialized && module.init) {
            module.init();
        }
    };
    
    /**
     * Initialize the HTMX system
     * @returns {Promise} Promise resolving when HTMX is fully initialized
     */
    window.CarFuseHTMX.init = function() {
        if (this.isInitialized) {
            this.log('Already initialized');
            return Promise.resolve(this);
        }
        
        this.log('Initializing HTMX');
        
        // Inherit debug setting from main CarFuse if available
        if (window.CarFuse && window.CarFuse.config) {
            this.setDebug(window.CarFuse.config.debug);
        }
        
        // Configure HTMX
        configureHtmx();
        
        this.isInitialized = true;
        this.log('HTMX initialization complete');
        
        // Dispatch ready event
        document.dispatchEvent(new CustomEvent('carfuse:htmx-ready', {
            detail: { htmx: this }
        }));
        
        return Promise.resolve(this);
    };
    
    // For backwards compatibility
    window.CarFuseHtmx = window.CarFuseHTMX;
    
    // Register with main CarFuse system if available
    if (window.CarFuse && typeof window.CarFuse.registry === 'object' && 
        typeof window.CarFuse.registry.htmx === 'object') {
        // Use custom loader for HTMX
        window.CarFuse.registry.htmx.loader = function() {
            return window.CarFuseHTMX.init();
        };
    }
})();
