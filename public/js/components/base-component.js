/**
 * CarFuse Base Component
 * Provides standardized component lifecycle, dependency management, and utility methods
 * that all components should extend.
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    /**
     * Base Component Class
     * @class BaseComponent
     */
    class BaseComponent {
        /**
         * Create a new component instance
         * @param {string} name - Component name
         * @param {Object} options - Configuration options
         */
        constructor(name, options = {}) {
            this.name = name;
            this.initialized = false;
            this.mounted = false;
            this.dependencies = options.dependencies || [];
            this.elements = [];
            this.events = {};
            this.state = {};
            this.props = {};
            this.config = {
                debug: false,
                ...options
            };
            
            // Store reference to parent CarFuse instance
            this.carfuse = window.CarFuse;
        }
        
        /**
         * Lifecycle: Initialize the component
         * @param {Object} options - Initialization options
         * @returns {Promise} Promise resolved when initialization is complete
         */
        init(options = {}) {
            if (this.initialized) {
                this.log(`Component ${this.name} already initialized`);
                return Promise.resolve(this);
            }
            
            // Merge options with existing config
            Object.assign(this.config, options);
            
            // Log initialization
            this.log(`Initializing ${this.name} component`);
            
            // Check if dependencies are loaded
            const missingDependencies = this.checkDependencies();
            if (missingDependencies.length > 0) {
                const error = new Error(`Dependencies not met: ${missingDependencies.join(', ')}`);
                this.logError(`Failed to initialize ${this.name}`, error);
                return Promise.reject(error);
            }
            
            try {
                // Call the prepare lifecycle hook (sync)
                this.prepare();
                
                // Execute initialization logic (overridden by subclasses)
                const result = this.initialize();
                
                // Support both synchronous and asynchronous initialization
                const promise = result instanceof Promise ? result : Promise.resolve(result);
                
                return promise.then(() => {
                    this.initialized = true;
                    this.log(`${this.name} component initialized`);
                    
                    // Register event handlers
                    this.bindEvents();
                    
                    // Auto-mount if configured to do so
                    if (this.config.autoMount !== false) {
                        return this.mount();
                    }
                    
                    return this;
                });
            } catch (error) {
                this.logError(`Failed to initialize ${this.name}`, error);
                return Promise.reject(error);
            }
        }
        
        /**
         * Lifecycle: Prepare component (synchronous pre-initialization)
         * Override in subclass for custom preparation logic
         */
        prepare() {
            // Optional preparation logic (sync)
        }
        
        /**
         * Lifecycle: Initialize component (can be async)
         * Override in subclass for custom initialization logic
         * @returns {Promise|any} Initialization result
         */
        initialize() {
            // Default implementation does nothing
            return Promise.resolve();
        }
        
        /**
         * Lifecycle: Mount component to DOM elements
         * @param {HTMLElement|string} [container] - Optional container selector or element
         * @returns {Promise} Promise resolved when mounting is complete
         */
        mount(container) {
            if (!this.initialized) {
                return Promise.reject(new Error(`Cannot mount ${this.name}: not initialized`));
            }
            
            this.log(`Mounting ${this.name} component`);
            
            try {
                // Find target elements
                this.elements = this.findElements(container);
                
                if (this.elements.length === 0) {
                    this.log(`No elements found to mount ${this.name} component`);
                    return Promise.resolve(this);
                }
                
                // Execute mount logic (can be overridden)
                const result = this.mountElements(this.elements);
                
                // Support both synchronous and asynchronous mounting
                const promise = result instanceof Promise ? result : Promise.resolve(result);
                
                return promise.then(() => {
                    this.mounted = true;
                    this.log(`${this.name} component mounted to ${this.elements.length} elements`);
                    
                    // Fire mounted lifecycle event
                    this.emit('mounted', this);
                    
                    return this;
                });
            } catch (error) {
                this.logError(`Failed to mount ${this.name}`, error);
                return Promise.reject(error);
            }
        }
        
        /**
         * Find elements for mounting the component
         * @param {HTMLElement|string} [container] - Optional container
         * @returns {Array<HTMLElement>} Array of elements
         */
        findElements(container) {
            // Default implementation uses [data-component] attribute with the component name
            const selector = this.config.selector || `[data-component="${this.name}"]`;
            const root = container instanceof HTMLElement ? container : document;
            
            return Array.from(root.querySelectorAll(selector));
        }
        
        /**
         * Mount component to specific elements
         * Override in subclass for custom mounting logic
         * @param {Array<HTMLElement>} elements - Elements to mount to
         * @returns {Promise|any} Mounting result
         */
        mountElements(elements) {
            // Default implementation does nothing
            return Promise.resolve();
        }
        
        /**
         * Lifecycle: Update component state
         * @param {Object} newState - New state to merge
         * @param {Boolean} [shouldRender=true] - Whether to render after update
         * @returns {Object} Updated state
         */
        update(newState, shouldRender = true) {
            this.state = {
                ...this.state,
                ...newState
            };
            
            if (shouldRender && this.mounted) {
                this.render();
            }
            
            // Emit state update event
            this.emit('state:updated', this.state);
            
            return this.state;
        }
        
        /**
         * Lifecycle: Render component
         * Override in subclass for custom rendering logic
         */
        render() {
            // Default render does nothing
            this.log(`Rendering ${this.name} component`);
        }
        
        /**
         * Lifecycle: Destroy component
         */
        destroy() {
            this.log(`Destroying ${this.name} component`);
            
            // Unbind events
            this.unbindEvents();
            
            // Execute destroy logic (can be overridden)
            try {
                this.destroyComponent();
                
                this.initialized = false;
                this.mounted = false;
                this.elements = [];
                
                // Emit destroyed lifecycle event
                this.emit('destroyed', this);
                
                this.log(`${this.name} component destroyed`);
            } catch (error) {
                this.logError(`Failed to destroy ${this.name}`, error);
            }
        }
        
        /**
         * Destroy component-specific resources
         * Override in subclass for custom destroy logic
         */
        destroyComponent() {
            // Default implementation does nothing
        }
        
        /**
         * Check if required dependencies are loaded
         * @returns {Array<string>} List of missing dependencies
         */
        checkDependencies() {
            return this.dependencies.filter(dep => {
                // Check if dependency exists in CarFuse components
                return !this.carfuse.state.components.has(dep);
            });
        }
        
        /**
         * Bind events based on this.events mapping
         */
        bindEvents() {
            // Process events that were registered using the on() method
            Object.entries(this.events).forEach(([event, handlers]) => {
                handlers.forEach(handler => {
                    const { selector, callback, options } = handler;
                    
                    if (selector) {
                        // Delegate event to elements matching selector
                        document.addEventListener(event, e => {
                            const target = e.target;
                            if (target.matches(selector) || target.closest(selector)) {
                                callback.call(this, e, target.closest(selector));
                            }
                        }, options);
                    } else {
                        // Global event
                        document.addEventListener(event, e => {
                            callback.call(this, e);
                        }, options);
                    }
                });
            });
        }
        
        /**
         * Unbind all events
         */
        unbindEvents() {
            // This is a simplified version - in a real implementation,
            // you'd need to store references to bound functions to properly remove them
            this.events = {};
        }
        
        /**
         * Register an event handler
         * @param {string} event - Event name
         * @param {string} [selector] - Optional CSS selector for delegation
         * @param {Function} callback - Event handler
         * @param {Object} [options] - Event options
         * @returns {BaseComponent} This component instance
         */
        on(event, selector, callback, options = {}) {
            // Support both (event, callback) and (event, selector, callback) signatures
            if (typeof selector === 'function') {
                options = callback || {};
                callback = selector;
                selector = null;
            }
            
            if (!this.events[event]) {
                this.events[event] = [];
            }
            
            this.events[event].push({
                selector,
                callback,
                options
            });
            
            return this;
        }
        
        /**
         * Emit a custom event
         * @param {string} eventName - Event name
         * @param {any} detail - Event details
         * @returns {CustomEvent} The emitted event
         */
        emit(eventName, detail) {
            const event = new CustomEvent(`carfuse:${this.name}:${eventName}`, {
                bubbles: true,
                cancelable: true,
                detail
            });
            
            document.dispatchEvent(event);
            return event;
        }
        
        /**
         * Set component properties with validation
         * @param {Object} props - Properties to set
         * @returns {Object} The validated properties
         */
        setProps(props) {
            // Apply prop validation if the validateProps method exists
            if (typeof this.validateProps === 'function') {
                this.props = this.validateProps(props);
            } else {
                this.props = { ...props };
            }
            
            return this.props;
        }
        
        /**
         * Log a message to the console if debug mode is enabled
         * @param {string} message - Message to log
         * @param {*} [data] - Optional data to include in log
         */
        log(message, data) {
            if (this.config.debug || (this.carfuse && this.carfuse.config.debug)) {
                console.log(`[CarFuse ${this.name}] ${message}`, data || '');
            }
        }
        
        /**
         * Log an error message
         * @param {string} message - Error message
         * @param {Error} error - Error object
         */
        logError(message, error) {
            console.error(`[CarFuse ${this.name} Error] ${message}`, error);
        }
    }
    
    // Register the base component
    CarFuse.BaseComponent = BaseComponent;
    
})();
