/**
 * CarFuse Core Component
 * Provides essential utility functions and core functionalities
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Define component name
    const COMPONENT_NAME = 'core';
    
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
            enablePolyfills: true,
            disableConsoleInProduction: false
        },
        
        // State
        state: {
            initialized: false,
            features: {},
            platform: {}
        },
        
        /**
         * Initialize core functionalities
         * @param {Object} options - Configuration options
         */
        init: function(options = {}) {
            // Apply custom options
            Object.assign(this.config, options);
            
            this.log('Initializing core component');
            this.detectFeatures();
            this.detectPlatform();
            
            if (this.config.enablePolyfills) {
                this.applyPolyfills();
            }
            
            this.setupDebugMode();
            this.state.initialized = true;
            this.log('Core component initialized');
            
            return Promise.resolve();
        },
        
        /**
         * Log a message to the console if debug mode is enabled
         * @param {string} message - Message to log
         * @param {*} [data] - Optional data to include in log
         */
        log: function(message, data) {
            if (this.config.debug || CarFuse.config.debug) {
                console.log(`[CarFuse Core] ${message}`, data || '');
            }
        },
        
        /**
         * Detect browser features
         */
        detectFeatures: function() {
            this.state.features = {
                'passiveEventListeners': this.supportsPassiveEventListeners(),
                'fetch': typeof window.fetch !== 'undefined',
                'promises': typeof Promise !== 'undefined',
                'es6': typeof Array.from !== 'undefined'
            };
            this.log('Detected browser features', this.state.features);
        },
        
        /**
         * Detect device and platform
         */
        detectPlatform: function() {
            this.state.platform = {
                'isMobile': /Mobi|Android/i.test(navigator.userAgent),
                'userAgent': navigator.userAgent
            };
            this.log('Detected platform', this.state.platform);
        },
        
        /**
         * Apply polyfills for older browsers
         */
        applyPolyfills: function() {
            if (!this.state.features.promises) {
                this.log('Applying Promise polyfill');
                // Load promise polyfill
            }
            
            if (!this.state.features.fetch) {
                this.log('Applying Fetch polyfill');
                // Load fetch polyfill
            }
        },
        
        /**
         * Setup debug mode functionality
         */
        setupDebugMode: function() {
            if (this.config.debug || CarFuse.config.debug) {
                this.log('Debug mode is enabled');
                // Add debug-specific functionalities
            }
        },
        
        /**
         * Check support for passive event listeners
         * @returns {boolean} True if passive event listeners are supported
         */
        supportsPassiveEventListeners: function() {
            let supportsPassive = false;
            try {
                const opts = Object.defineProperty({}, 'passive', {
                    get: function() {
                        supportsPassive = true;
                    }
                });
                window.addEventListener("testPassive", null, opts);
                window.removeEventListener("testPassive", null, opts);
            } catch (e) {}
            return supportsPassive;
        },
        
        /**
         * Utility function to format dates
         * @param {Date} date - Date object to format
         * @param {object} options - Formatting options
         * @returns {string} Formatted date string
         */
        formatDate: function(date, options = {}) {
            const defaultOptions = CarFuse.config.dateFormat || { year: 'numeric', month: '2-digit', day: '2-digit' };
            const mergedOptions = { ...defaultOptions, ...options };
            
            try {
                return new Intl.DateTimeFormat(CarFuse.config.locale, mergedOptions).format(date);
            } catch (e) {
                console.error('Error formatting date', e);
                return date.toLocaleDateString();
            }
        },
        
        /**
         * Utility function to format currency
         * @param {number} amount - Amount to format
         * @param {string} currency - Currency code
         * @returns {string} Formatted currency string
         */
        formatCurrency: function(amount, currency = CarFuse.config.currency) {
            try {
                return new Intl.NumberFormat(CarFuse.config.locale, {
                    style: 'currency',
                    currency: currency
                }).format(amount);
            } catch (e) {
                console.error('Error formatting currency', e);
                return amount.toFixed(2) + ' ' + currency;
            }
        },
        
        /**
         * Performance tracking utility
         * @param {string} name - Name of the performance mark
         */
        markPerformance: function(name) {
            if (window.performance && window.performance.mark) {
                window.performance.mark(name);
            }
        },
        
        /**
         * Measure performance between two marks
         * @param {string} startMark - Name of the starting mark
         * @param {string} endMark - Name of the ending mark
         * @param {string} measurementName - Name of the measurement
         */
        measurePerformance: function(startMark, endMark, measurementName) {
            if (window.performance && window.performance.measure) {
                window.performance.measure(measurementName, startMark, endMark);
                const measurement = window.performance.getEntriesByName(measurementName)[0];
                this.log(`${measurementName} took ${measurement.duration}ms`);
            }
        }
    };
    
    // Register the component
    CarFuse[COMPONENT_NAME] = component;
    
    // Register with CarFuse if available
    if (CarFuse.registerComponent) {
        CarFuse.registerComponent(COMPONENT_NAME, component);
    }
    
    // Initialize core component
    CarFuse.core.init();
})();
