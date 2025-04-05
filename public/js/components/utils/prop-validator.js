/**
 * CarFuse Prop Validator Utility
 * Provides prop validation utilities similar to React PropTypes
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    if (!CarFuse.utils) {
        CarFuse.utils = {};
    }
    
    /**
     * PropValidator provides utilities for validating component props
     */
    const PropValidator = {
        /**
         * Validates props against a schema definition
         * @param {Object} props - Properties to validate
         * @param {Object} schema - Schema definition
         * @param {string} componentName - Component name for error messages
         * @returns {Object} Validated props with defaults applied
         * @throws {Error} If validation fails and required props are missing
         */
        validate(props, schema, componentName) {
            const validatedProps = {};
            const errors = [];
            
            // Check each property in the schema
            for (const [propName, definition] of Object.entries(schema)) {
                const value = props[propName];
                const isUndefined = value === undefined;
                
                // Check if required property is missing
                if (definition.required && isUndefined) {
                    errors.push(`Required prop '${propName}' was not specified in '${componentName}'`);
                    continue;
                }
                
                // Apply default value if provided and value is undefined
                if (isUndefined && 'default' in definition) {
                    validatedProps[propName] = typeof definition.default === 'function'
                        ? definition.default()
                        : definition.default;
                    continue;
                }
                
                // Skip validation if value is undefined and not required
                if (isUndefined) {
                    continue;
                }
                
                // Validate the type
                if (definition.type && !this.validateType(value, definition.type)) {
                    errors.push(`Invalid prop '${propName}' of type '${typeof value}' supplied to '${componentName}', expected '${definition.type.name}'`);
                    continue;
                }
                
                // Run custom validator if provided
                if (definition.validator && typeof definition.validator === 'function') {
                    try {
                        const isValid = definition.validator(value);
                        if (!isValid) {
                            errors.push(`Custom validation failed for prop '${propName}' in '${componentName}'`);
                            continue;
                        }
                    } catch (error) {
                        errors.push(`Error in custom validator for prop '${propName}' in '${componentName}': ${error.message}`);
                        continue;
                    }
                }
                
                // Use the provided value
                validatedProps[propName] = value;
            }
            
            // Report all validation errors
            if (errors.length > 0) {
                console.error(`[${componentName}] Prop validation errors:\n${errors.join('\n')}`);
                
                // Throw error only if there are required props missing
                if (errors.some(error => error.includes('Required prop'))) {
                    throw new Error(`${componentName}: ${errors[0]}`);
                }
            }
            
            // Include any extra props not in the schema
            return {
                ...props,
                ...validatedProps
            };
        },
        
        /**
         * Validate a value against a type
         * @param {any} value - Value to validate
         * @param {Function} type - Constructor function for the type
         * @returns {boolean} True if the value matches the type
         */
        validateType(value, type) {
            // Check basic types
            if (type === String) return typeof value === 'string';
            if (type === Number) return typeof value === 'number' && !isNaN(value);
            if (type === Boolean) return typeof value === 'boolean';
            if (type === Function) return typeof value === 'function';
            if (type === Object) return value !== null && typeof value === 'object' && !Array.isArray(value);
            if (type === Array) return Array.isArray(value);
            
            // Handle Date objects
            if (type === Date) return value instanceof Date;
            
            // Handle custom class instances
            return value instanceof type;
        },
        
        /**
         * Helper method to create a prop type definition
         * @param {Function} type - Constructor function for the type
         * @param {Object} options - Additional options (required, default, validator)
         * @returns {Object} Prop type definition
         */
        createProp(type, options = {}) {
            return {
                type,
                required: options.required || false,
                default: options.default,
                validator: options.validator
            };
        }
    };
    
    // Add type helpers to create prop definitions
    PropValidator.types = {
        string(options = {}) {
            return PropValidator.createProp(String, options);
        },
        
        number(options = {}) {
            return PropValidator.createProp(Number, options);
        },
        
        boolean(options = {}) {
            return PropValidator.createProp(Boolean, options);
        },
        
        function(options = {}) {
            return PropValidator.createProp(Function, options);
        },
        
        object(options = {}) {
            return PropValidator.createProp(Object, options);
        },
        
        array(options = {}) {
            return PropValidator.createProp(Array, options);
        },
        
        date(options = {}) {
            return PropValidator.createProp(Date, options);
        },
        
        oneOf(allowedValues, options = {}) {
            const validator = (value) => allowedValues.includes(value);
            return PropValidator.createProp(null, {
                ...options,
                validator
            });
        }
    };
    
    // Register utility
    CarFuse.utils.PropValidator = PropValidator;
})();
