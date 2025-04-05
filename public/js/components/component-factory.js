/**
 * CarFuse Component Factory
 * Provides simplified component creation and registration
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    /**
     * Creates and registers a component with standardized architecture
     * @param {string} name - Component name
     * @param {Object} definition - Component implementation
     * @param {Object} options - Registration options
     * @returns {Object} The registered component
     */
    function createComponent(name, definition, options = {}) {
        if (typeof name !== 'string' || !name) {
            throw new Error('Component name must be a non-empty string');
        }
        
        if (!definition || typeof definition !== 'object') {
            throw new Error(`Invalid component definition for "${name}"`);
        }
        
        // Get the Component class to extend (BaseComponent or custom)
        const Parent = definition.extends || CarFuse.BaseComponent;
        
        if (!Parent) {
            throw new Error(`BaseComponent class not found. Make sure it's loaded before creating components.`);
        }
        
        /**
         * Component class extending from the parent
         */
        class Component extends Parent {
            constructor(customOptions = {}) {
                super(name, {
                    ...options,
                    ...customOptions
                });
                
                // Initialize properties from definition
                if (definition.props) {
                    this.propDefinitions = definition.props;
                }
                
                // Initialize state from definition
                if (definition.state) {
                    this.state = typeof definition.state === 'function'
                        ? definition.state.call(this)
                        : { ...definition.state };
                }
            }
            
            // Override lifecycle methods if provided in definition
            prepare() {
                if (definition.prepare && typeof definition.prepare === 'function') {
                    definition.prepare.call(this);
                } else {
                    super.prepare();
                }
            }
            
            initialize() {
                if (definition.initialize && typeof definition.initialize === 'function') {
                    return definition.initialize.call(this);
                }
                return super.initialize();
            }
            
            mountElements(elements) {
                if (definition.mount && typeof definition.mount === 'function') {
                    return definition.mount.call(this, elements);
                }
                return super.mountElements(elements);
            }
            
            render() {
                if (definition.render && typeof definition.render === 'function') {
                    return definition.render.call(this);
                }
                return super.render();
            }
            
            destroyComponent() {
                if (definition.destroy && typeof definition.destroy === 'function') {
                    return definition.destroy.call(this);
                }
                return super.destroyComponent();
            }
            
            validateProps(props) {
                if (this.propDefinitions && CarFuse.utils && CarFuse.utils.PropValidator) {
                    return CarFuse.utils.PropValidator.validate(
                        props,
                        this.propDefinitions,
                        this.name
                    );
                }
                return props;
            }
        }
        
        // Copy static methods from definition
        if (definition.statics) {
            Object.entries(definition.statics).forEach(([key, value]) => {
                Component[key] = value;
            });
        }
        
        // Copy additional methods from definition
        Object.entries(definition).forEach(([key, value]) => {
            if (['extends', 'props', 'state', 'prepare', 'initialize', 
                'mount', 'render', 'destroy', 'statics'].includes(key)) {
                return; // Skip already handled properties
            }
            
            if (typeof value === 'function') {
                Component.prototype[key] = value;
            }
        });
        
        // Create the component instance
        const component = new Component();
        
        // Register with CarFuse
        if (CarFuse.registerComponent) {
            CarFuse.registerComponent(name, component, options);
        } else {
            // Fallback if registration method doesn't exist
            CarFuse[name] = component;
        }
        
        return component;
    }
    
    // Register the component factory
    CarFuse.createComponent = createComponent;
})();
