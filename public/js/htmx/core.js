/**
 * CarFuse HTMX Core
 * Base functionality and utilities for HTMX integration
 */

(function() {
    // Ensure the CarFuseHTMX namespace exists
    if (typeof window.CarFuseHTMX !== 'object') {
        console.error('[CarFuseHTMX Core] CarFuseHTMX namespace is not defined');
        return;
    }

    // Ensure HTMX is loaded
    if (typeof window.htmx !== 'object') {
        console.error('[CarFuseHTMX Core] HTMX library is not loaded');
        return;
    }

    /**
     * Core HTMX utilities
     */
    const core = {
        /**
         * Trigger an HTMX AJAX request
         * @param {string} method - HTTP method (GET, POST, etc)
         * @param {string} url - URL to request
         * @param {object} options - HTMX options
         * @returns {Promise} Promise resolving when the request is complete
         * 
         * @example
         * // Simple GET request
         * CarFuseHTMX.core.ajax('GET', '/api/users').then(response => {
         *   console.log('Response data:', response);
         * });
         * 
         * // POST with target element and swap
         * CarFuseHTMX.core.ajax('POST', '/api/save', {
         *   target: document.getElementById('result'),
         *   swap: 'innerHTML',
         *   values: { name: 'John' }
         * });
         */
        ajax: function(method, url, options = {}) {
            return new Promise((resolve, reject) => {
                const defaultOptions = {
                    timeout: window.CarFuseHTMX.config.timeout,
                    headers: {}
                };
                
                const mergedOptions = { ...defaultOptions, ...options };
                
                // Create one-time event listeners for this request
                const target = mergedOptions.target || document.body;
                
                const successHandler = (event) => {
                    if (event.detail.elt === target && event.detail.xhr.responseURL.includes(url)) {
                        document.removeEventListener('htmx:afterRequest', successHandler);
                        document.removeEventListener('htmx:responseError', errorHandler);
                        resolve(event.detail);
                    }
                };
                
                const errorHandler = (event) => {
                    if (event.detail.elt === target && event.detail.xhr.responseURL.includes(url)) {
                        document.removeEventListener('htmx:afterRequest', successHandler);
                        document.removeEventListener('htmx:responseError', errorHandler);
                        reject(event.detail);
                    }
                };
                
                document.addEventListener('htmx:afterRequest', successHandler);
                document.addEventListener('htmx:responseError', errorHandler);
                
                // Make the actual request
                window.htmx.ajax(method, url, mergedOptions);
            });
        },
        
        /**
         * Process HTMX elements in a container after dynamic insertion
         * @param {HTMLElement} container - Container element
         * 
         * @example
         * // Process a dynamically created element
         * const newElement = document.createElement('div');
         * newElement.innerHTML = '<button hx-get="/api/data">Load Data</button>';
         * document.body.appendChild(newElement);
         * CarFuseHTMX.core.process(newElement);
         */
        process: function(container) {
            if (container) {
                window.htmx.process(container);
            }
        },
        
        /**
         * Convert form data to JSON object
         * @param {FormData} formData - FormData object
         * @returns {object} JSON object
         * 
         * @example
         * const form = document.querySelector('form');
         * const formData = new FormData(form);
         * const json = CarFuseHTMX.core.formDataToJson(formData);
         * console.log(json);
         */
        formDataToJson: function(formData) {
            const json = {};
            formData.forEach((value, key) => {
                // Handle existing keys (arrays)
                if (json[key]) {
                    if (!Array.isArray(json[key])) {
                        json[key] = [json[key]];
                    }
                    json[key].push(value);
                } else {
                    json[key] = value;
                }
            });
            return json;
        },
        
        /**
         * Trigger client-side HTMX events
         * @param {HTMLElement} element - Element to trigger event on
         * @param {string} eventName - HTMX event name
         * @param {object} detail - Event detail
         * 
         * @example
         * // Trigger a custom event
         * CarFuseHTMX.core.triggerEvent(
         *   document.getElementById('myElement'),
         *   'refreshContent',
         *   { id: 123 }
         * );
         */
        triggerEvent: function(element, eventName, detail = {}) {
            if (!element) return;
            window.htmx.trigger(element, eventName, detail);
        }
    };
    
    // Add core methods to the global object
    window.CarFuseHTMX.core = core;
    
    // Log initialization
    window.CarFuseHTMX.log('Core module initialized');
})();
