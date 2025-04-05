/**
 * CarFuse Component Registry
 * Provides a centralized registry for component discovery and lazy loading
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    /**
     * Component Registry manages component registration, discovery, and lazy loading
     */
    class ComponentRegistry {
        constructor() {
            this.components = new Map();
            this.componentTypes = new Set();
            this.loadedComponents = new Set();
            this.pendingLoads = new Map();
        }
        
        /**
         * Register a component with the registry
         * @param {string} name - Component name
         * @param {Object} options - Component options
         */
        register(name, options = {}) {
            if (this.components.has(name)) {
                console.warn(`Component "${name}" already registered, overwriting.`);
            }
            
            const componentOptions = {
                name,
                path: options.path || null,
                type: options.type || 'component',
                dependencies: options.dependencies || [],
                instance: options.instance || null,
                factory: options.factory || null,
                priority: options.priority || 5,
                lazy: options.lazy !== false,
                autoInit: options.autoInit !== false,
                selector: options.selector || `[data-component="${name}"]`
            };
            
            this.components.set(name, componentOptions);
            this.componentTypes.add(componentOptions.type);
            
            if (options.instance) {
                this.loadedComponents.add(name);
            }
            
            return componentOptions;
        }
        
        /**
         * Get a component by name
         * @param {string} name - Component name
         * @returns {Object|null} Component options or null if not found
         */
        get(name) {
            return this.components.get(name) || null;
        }
        
        /**
         * Check if a component is registered
         * @param {string} name - Component name
         * @returns {boolean} True if component is registered
         */
        has(name) {
            return this.components.has(name);
        }
        
        /**
         * Check if a component is loaded
         * @param {string} name - Component name
         * @returns {boolean} True if component is loaded
         */
        isLoaded(name) {
            return this.loadedComponents.has(name);
        }
        
        /**
         * Get all registered components
         * @returns {Array} Array of component options
         */
        getAll() {
            return Array.from(this.components.values());
        }
        
        /**
         * Get components by type
         * @param {string} type - Component type
         * @returns {Array} Array of component options
         */
        getByType(type) {
            return this.getAll().filter(component => component.type === type);
        }
        
        /**
         * Load a component by name
         * @param {string} name - Component name
         * @returns {Promise} Promise resolved with component instance
         */
        load(name) {
            if (this.loadedComponents.has(name)) {
                const component = this.get(name);
                return Promise.resolve(component.instance);
            }
            
            // Check if load already pending
            if (this.pendingLoads.has(name)) {
                return this.pendingLoads.get(name);
            }
            
            const component = this.get(name);
            
            if (!component) {
                return Promise.reject(new Error(`Component "${name}" not registered`));
            }
            
            // If instance exists but not marked as loaded
            if (component.instance) {
                this.loadedComponents.add(name);
                return Promise.resolve(component.instance);
            }
            
            // Load dependencies first
            const dependencyPromises = (component.dependencies || [])
                .filter(dep => !this.loadedComponents.has(dep))
                .map(dep => this.load(dep));
            
            let loadPromise;
            
            if (!component.path) {
                // No path, use factory if available
                if (component.factory) {
                    loadPromise = Promise.resolve()
                        .then(() => {
                            const instance = component.factory();
                            component.instance = instance;
                            this.loadedComponents.add(name);
                            return instance;
                        });
                } else {
                    loadPromise = Promise.reject(new Error(`Component "${name}" has no path or factory`));
                }
            } else {
                // Load script from path
                loadPromise = Promise.all(dependencyPromises)
                    .then(() => new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = component.path;
                        script.async = true;
                        
                        script.onload = () => {
                            // The script should register the component
                            if (this.loadedComponents.has(name)) {
                                resolve(this.get(name).instance);
                            } else if (CarFuse[name]) {
                                // Auto-register if found in CarFuse namespace
                                component.instance = CarFuse[name];
                                this.loadedComponents.add(name);
                                resolve(component.instance);
                            } else {
                                reject(new Error(`Component "${name}" did not register properly`));
                            }
                        };
                        
                        script.onerror = () => {
                            reject(new Error(`Failed to load script for component "${name}"`));
                        };
                        
                        document.head.appendChild(script);
                    }));
            }
            
            // Store pending promise
            this.pendingLoads.set(name, loadPromise);
            
            // Clean up pending promise when finished
            return loadPromise.finally(() => {
                this.pendingLoads.delete(name);
            });
        }
        
        /**
         * Auto-discover components in the DOM and initialize them
         * @param {HTMLElement} root - Root element to search in
         */
        discoverComponents(root = document) {
            // Get unique component names from data-component attributes
            const componentElements = root.querySelectorAll('[data-component]');
            const componentNames = new Set();
            
            componentElements.forEach(element => {
                const name = element.dataset.component;
                if (name) componentNames.add(name);
            });
            
            // Load and initialize components
            return Promise.all(
                Array.from(componentNames)
                    .map(name => this.load(name)
                        .then(instance => {
                            if (instance && typeof instance.mount === 'function') {
                                return instance.mount(root);
                            }
                        })
                        .catch(error => {
                            console.error(`Error loading component "${name}":`, error);
                        })
                    )
            );
        }
    }
    
    // Create registry instance
    CarFuse.componentRegistry = new ComponentRegistry();
    
    // Add helper method to CarFuse
    CarFuse.registerComponentWithRegistry = function(name, options = {}) {
        return CarFuse.componentRegistry.register(name, options);
    };
})();
