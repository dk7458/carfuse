/**
 * CarFuse State Manager Utility
 * Provides a centralized state management system for components
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
     * Create a reactive state manager
     * @param {Object} initialState - Initial state
     * @param {Function} onChange - Callback when state changes
     * @returns {Object} State manager methods
     */
    function createStateManager(initialState = {}, onChange = null) {
        let state = { ...initialState };
        const listeners = new Set();
        
        // Add default onChange listener if provided
        if (onChange && typeof onChange === 'function') {
            listeners.add(onChange);
        }
        
        /**
         * Get the current state (read-only copy)
         * @returns {Object} Current state
         */
        function getState() {
            return { ...state };
        }
        
        /**
         * Set state by merging with existing state
         * @param {Object|Function} updater - New state object or updater function
         * @returns {Object} Updated state
         */
        function setState(updater) {
            const prevState = { ...state };
            
            if (typeof updater === 'function') {
                state = {
                    ...state,
                    ...updater(state)
                };
            } else {
                state = {
                    ...state,
                    ...updater
                };
            }
            
            // Only notify if something changed
            if (JSON.stringify(prevState) !== JSON.stringify(state)) {
                notifyListeners(state, prevState);
            }
            
            return state;
        }
        
        /**
         * Reset state to initial values
         * @returns {Object} Reset state
         */
        function resetState() {
            const prevState = { ...state };
            state = { ...initialState };
            notifyListeners(state, prevState);
            return state;
        }
        
        /**
         * Subscribe to state changes
         * @param {Function} listener - Listener function
         * @returns {Function} Unsubscribe function
         */
        function subscribe(listener) {
            if (typeof listener !== 'function') {
                throw new Error('Expected listener to be a function');
            }
            
            listeners.add(listener);
            
            return function unsubscribe() {
                listeners.delete(listener);
            };
        }
        
        /**
         * Notify all listeners of state change
         * @param {Object} currentState - Current state
         * @param {Object} previousState - Previous state
         */
        function notifyListeners(currentState, previousState) {
            listeners.forEach(listener => {
                try {
                    listener(currentState, previousState);
                } catch (error) {
                    console.error('Error in state change listener:', error);
                }
            });
        }
        
        return {
            getState,
            setState,
            resetState,
            subscribe
        };
    }
    
    // Register utility
    CarFuse.utils.createStateManager = createStateManager;
})();
