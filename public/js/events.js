/**
 * CarFuse Event System
 * Provides centralized event management with consistent naming and payload structure
 */

(function() {
    // Event name constants to prevent typos and ensure consistency
    const EVENT_NAMES = {
        // Authentication events
        AUTH: {
            STATE_CHANGED: 'auth:state-changed',
            LOGIN_SUCCESS: 'auth:login-success',
            LOGIN_ERROR: 'auth:login-error',
            LOGOUT_SUCCESS: 'auth:logout-success',
            SESSION_EXPIRED: 'auth:session-expired',
            TOKEN_REFRESHED: 'auth:token-refreshed',
            UNAUTHORIZED: 'auth:unauthorized',
            FORBIDDEN: 'auth:forbidden',
            READY: 'auth:ready'
        },
        
        // UI events
        UI: {
            TOAST_SHOW: 'ui:toast-show',
            MODAL_OPEN: 'ui:modal-open',
            MODAL_CLOSE: 'ui:modal-close',
            THEME_CHANGE: 'ui:theme-change',
            NAV_CHANGE: 'ui:nav-change',
            LOADING_START: 'ui:loading-start',
            LOADING_END: 'ui:loading-end'
        },
        
        // Data events
        DATA: {
            LOADED: 'data:loaded',
            UPDATED: 'data:updated',
            ERROR: 'data:error',
            STALE: 'data:stale'
        },
        
        // Form events
        FORM: {
            SUBMIT_START: 'form:submit-start',
            SUBMIT_SUCCESS: 'form:submit-success',
            SUBMIT_ERROR: 'form:submit-error',
            VALIDATION_ERROR: 'form:validation-error'
        },
        
        // System events
        SYSTEM: {
            READY: 'system:ready',
            ERROR: 'system:error',
            NETWORK_ERROR: 'system:network-error',
            CONFIG_LOADED: 'system:config-loaded'
        }
    };
    
    // Track event listeners for debugging and management
    const eventListeners = new Map();
    
    // Debug mode flag
    let debugMode = false;
    
    // Performance tracking for events
    const eventTimings = new Map();
    
    /**
     * CarFuse Events API
     */
    const Events = {
        // Event name constants
        NAMES: EVENT_NAMES,
        
        /**
         * Enable or disable debug mode
         * @param {boolean} enabled - Whether debug mode should be enabled
         */
        setDebug: function(enabled) {
            debugMode = !!enabled;
            if (debugMode) {
                console.info('[Events] Debug mode enabled');
            }
        },
        
        /**
         * Dispatch an event with the proper naming convention
         * @param {string} eventName - Name of the event from EVENT_NAMES
         * @param {object} detail - Event details/payload
         * @param {Element} [target=document] - Target to dispatch event on
         * @returns {CustomEvent} The dispatched event
         */
        dispatch: function(eventName, detail = {}, target = document) {
            if (debugMode) {
                console.debug(`[Events] Dispatching ${eventName}`, detail);
                
                // Track event timing
                const eventId = `${eventName}-${Date.now()}`;
                eventTimings.set(eventId, {
                    name: eventName,
                    startTime: performance.now(),
                    detail: detail
                });
                
                // Add timing identifier to detail for correlation
                detail._eventTimingId = eventId;
            }
            
            const event = new CustomEvent(eventName, { 
                bubbles: true, 
                cancelable: true,
                detail: detail
            });
            
            target.dispatchEvent(event);
            return event;
        },
        
        /**
         * Listen for an event
         * @param {string} eventName - Name of the event from EVENT_NAMES
         * @param {Function} callback - Event handler
         * @param {Element} [target=document] - Target to listen on
         * @param {object} [options] - AddEventListener options
         * @returns {Function} Function to remove the listener
         */
        on: function(eventName, callback, target = document, options = {}) {
            if (debugMode) {
                console.debug(`[Events] Listening for ${eventName}`);
                
                // Wrap callback to measure performance
                const originalCallback = callback;
                callback = function(event) {
                    if (event.detail && event.detail._eventTimingId) {
                        const timing = eventTimings.get(event.detail._eventTimingId);
                        if (timing) {
                            timing.processingTime = performance.now() - timing.startTime;
                            console.debug(`[Events] Event ${eventName} processed in ${timing.processingTime.toFixed(2)}ms`, event.detail);
                            // Keep timings for a limited time
                            setTimeout(() => {
                                eventTimings.delete(event.detail._eventTimingId);
                            }, 5000);
                        }
                    }
                    return originalCallback.apply(this, arguments);
                };
            }
            
            target.addEventListener(eventName, callback, options);
            
            // Track listeners for management
            if (!eventListeners.has(eventName)) {
                eventListeners.set(eventName, new Set());
            }
            eventListeners.get(eventName).add({
                callback,
                target,
                options
            });
            
            // Return function to remove listener
            return function() {
                Events.off(eventName, callback, target, options);
            };
        },
        
        /**
         * Remove an event listener
         * @param {string} eventName - Name of the event
         * @param {Function} callback - Event handler to remove
         * @param {Element} [target=document] - Target to remove listener from
         * @param {object} [options] - RemoveEventListener options
         */
        off: function(eventName, callback, target = document, options = {}) {
            target.removeEventListener(eventName, callback, options);
            
            // Update tracking
            if (eventListeners.has(eventName)) {
                const listeners = eventListeners.get(eventName);
                for (const listener of listeners) {
                    if (listener.callback === callback && listener.target === target) {
                        listeners.delete(listener);
                        break;
                    }
                }
                if (listeners.size === 0) {
                    eventListeners.delete(eventName);
                }
            }
        },
        
        /**
         * Listen for an event once
         * @param {string} eventName - Name of the event
         * @param {Function} callback - Event handler
         * @param {Element} [target=document] - Target to listen on
         * @returns {Function} Function to remove the listener
         */
        once: function(eventName, callback, target = document) {
            return this.on(eventName, callback, target, { once: true });
        },
        
        /**
         * Get active listeners for debugging
         * @returns {object} Map of event listeners
         */
        getActiveListeners: function() {
            const result = {};
            eventListeners.forEach((listeners, eventName) => {
                result[eventName] = listeners.size;
            });
            return result;
        },
        
        /**
         * Create a helper for specific event domain
         * @param {string} domain - Event domain (auth, ui, data, etc)
         * @returns {object} Domain-specific event helpers
         */
        createDomainHelper: function(domain) {
            if (!EVENT_NAMES[domain.toUpperCase()]) {
                console.warn(`[Events] Unknown event domain: ${domain}`);
                return null;
            }
            
            const domainEvents = EVENT_NAMES[domain.toUpperCase()];
            const helper = {};
            
            // Create dispatch methods for each event
            Object.entries(domainEvents).forEach(([key, eventName]) => {
                const methodName = 'dispatch' + key.split('_').map(
                    word => word.charAt(0) + word.slice(1).toLowerCase()
                ).join('');
                
                helper[methodName] = function(detail, target) {
                    return Events.dispatch(eventName, detail, target);
                };
                
                // Also add listener method
                const listenerMethodName = 'on' + key.split('_').map(
                    word => word.charAt(0) + word.slice(1).toLowerCase()
                ).join('');
                
                helper[listenerMethodName] = function(callback, target, options) {
                    return Events.on(eventName, callback, target, options);
                };
            });
            
            return helper;
        },
        
        /**
         * Create event visualization helper for development
         * @param {Element} container - Container element for visualization
         */
        createVisualizer: function(container) {
            if (!container) {
                console.error('[Events] Container required for visualizer');
                return;
            }
            
            const visualizer = document.createElement('div');
            visualizer.className = 'events-visualizer';
            visualizer.style.cssText = 'position:fixed;bottom:0;right:0;width:300px;height:400px;background:#fff;border:1px solid #ccc;overflow:auto;z-index:9999;font-size:12px;padding:10px;';
            
            const header = document.createElement('h4');
            header.textContent = 'Event Monitor';
            visualizer.appendChild(header);
            
            const list = document.createElement('ul');
            list.style.cssText = 'list-style:none;padding:0;margin:0;';
            visualizer.appendChild(list);
            
            container.appendChild(visualizer);
            
            // Listen for all known events
            Object.values(EVENT_NAMES).forEach(domainEvents => {
                Object.values(domainEvents).forEach(eventName => {
                    document.addEventListener(eventName, event => {
                        const item = document.createElement('li');
                        item.style.cssText = 'padding:5px;margin-bottom:5px;border-bottom:1px solid #eee;';
                        
                        const timestamp = new Date().toLocaleTimeString();
                        item.innerHTML = `
                            <div><strong>${eventName}</strong> <small>${timestamp}</small></div>
                            <div><pre>${JSON.stringify(event.detail, null, 2)}</pre></div>
                        `;
                        
                        list.prepend(item);
                        
                        // Limit items
                        if (list.children.length > 50) {
                            list.lastChild.remove();
                        }
                    });
                });
            });
            
            return visualizer;
        }
    };
    
    // Create domain-specific helpers
    Events.Auth = Events.createDomainHelper('AUTH');
    Events.UI = Events.createDomainHelper('UI');
    Events.Data = Events.createDomainHelper('DATA');
    Events.Form = Events.createDomainHelper('FORM');
    Events.System = Events.createDomainHelper('SYSTEM');
    
    // Expose to global scope
    window.CarFuseEvents = Events;
})();
