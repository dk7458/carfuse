/**
 * CarFuse Event Bus Utility
 * Provides a centralized event management system
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
     * Event Bus provides pub/sub pattern functionality
     */
    class EventBus {
        constructor() {
            this.events = {};
        }
        
        /**
         * Subscribe to an event
         * @param {string} event - Event name
         * @param {Function} callback - Event handler
         * @returns {Function} Unsubscribe function
         */
        on(event, callback) {
            if (!this.events[event]) {
                this.events[event] = [];
            }
            
            this.events[event].push(callback);
            
            // Return unsubscribe function
            return () => {
                this.off(event, callback);
            };
        }
        
        /**
         * Subscribe to an event only once
         * @param {string} event - Event name
         * @param {Function} callback - Event handler
         * @returns {Function} Unsubscribe function
         */
        once(event, callback) {
            const onceCallback = (...args) => {
                this.off(event, onceCallback);
                callback(...args);
            };
            
            return this.on(event, onceCallback);
        }
        
        /**
         * Unsubscribe from an event
         * @param {string} event - Event name
         * @param {Function} callback - Event handler to remove
         */
        off(event, callback) {
            if (!this.events[event]) return;
            
            this.events[event] = this.events[event].filter(cb => cb !== callback);
            
            // Clean up empty event arrays
            if (this.events[event].length === 0) {
                delete this.events[event];
            }
        }
        
        /**
         * Emit an event
         * @param {string} event - Event name
         * @param {...any} args - Event arguments
         */
        emit(event, ...args) {
            if (!this.events[event]) return;
            
            this.events[event].forEach(callback => {
                try {
                    callback(...args);
                } catch (error) {
                    console.error(`Error in event handler for ${event}:`, error);
                }
            });
        }
        
        /**
         * Clear all event handlers
         * @param {string} [event] - Optional specific event to clear
         */
        clear(event) {
            if (event) {
                delete this.events[event];
            } else {
                this.events = {};
            }
        }
    }
    
    // Create a global event bus instance
    const eventBus = new EventBus();
    
    // Register utility
    CarFuse.utils.EventBus = EventBus;
    CarFuse.eventBus = eventBus;
})();
