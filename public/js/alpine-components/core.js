/**
 * Alpine Core Component
 * Provides core functionality for Alpine components
 */

(() => {
    // Check if CarFuseAlpine is available
    if (!window.CarFuseAlpine) {
        console.error('CarFuseAlpine is not initialized. Make sure alpine.js is loaded before this component.');
        return;
    }
    
    /**
     * Alpine Core component
     * Provides shared functionality for other components
     */
    window.CarFuseAlpine.registerComponent('alpineCore', () => {
        return {
            initialized: false,
            isDebug: window.CarFuse?.config?.debug || false,
            
            init() {
                // Set up error boundary
                this.$el.setAttribute('x-error-boundary', '');
                
                try {
                    this.initialized = true;
                    
                    if (this.isDebug) {
                        console.log('[Alpine Core] Initialized');
                    }
                    
                    // Dispatch event for others to know core is ready
                    window.dispatchEvent(new CustomEvent('alpine:core-ready'));
                } catch (e) {
                    console.error('[Alpine Core] Initialization error:', e);
                }
            }
        };
    });
    
    /**
     * Alpine Toast System Component
     * Provides standardized toast notifications
     */
    window.CarFuseAlpine.registerComponent('toastSystem', () => {
        return {
            toasts: [],
            
            init() {
                // Set up error boundary
                this.$el.setAttribute('x-error-boundary', '');
            },
            
            /**
             * Show a toast notification
             * @param {string} title - Toast title
             * @param {string} message - Toast message
             * @param {string} type - Toast type: success, error, warning, info
             * @param {number} duration - Duration to show toast in milliseconds
             */
            showToast(title, message, type = 'info', duration = 5000) {
                const id = Date.now();
                const toast = { id, title, message, type, visible: true };
                
                this.toasts.push(toast);
                
                // Auto-hide toast after duration
                setTimeout(() => {
                    this.hideToast(id);
                }, duration);
            },
            
            /**
             * Hide a specific toast
             * @param {number} id - Toast ID to hide
             */
            hideToast(id) {
                const index = this.toasts.findIndex(toast => toast.id === id);
                if (index !== -1) {
                    this.toasts[index].visible = false;
                    
                    // Remove from array after animation completes
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(toast => toast.id !== id);
                    }, 500); // Animation duration
                }
            }
        };
    });
})();
