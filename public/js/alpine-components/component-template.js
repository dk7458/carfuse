/**
 * Alpine Component Template
 * This is a standard pattern for all Alpine components in the CarFuse system
 */

(() => {
    // Check if CarFuseAlpine is available
    if (!window.CarFuseAlpine) {
        console.error('CarFuseAlpine is not initialized. Make sure alpine.js is loaded before this component.');
        return;
    }
    
    /**
     * Example component definition
     * Shows standard pattern for CarFuse Alpine components
     */
    window.CarFuseAlpine.registerComponent('exampleComponent', () => {
        return {
            // Component data
            loading: false,
            message: 'Hello from example component',
            error: null,
            
            // Initialization hook
            init() {
                // Set up error boundary
                this.$el.setAttribute('x-error-boundary', '');
                
                try {
                    this.initialize();
                } catch (e) {
                    this.handleError(e);
                }
            },
            
            // Primary initialization method
            initialize() {
                console.log('Example component initialized');
            },
            
            // Standard error handler
            handleError(error) {
                this.error = error.message || 'An error occurred';
                console.error('[Alpine Component Error]', error);
                
                // Dispatch error event for tracking
                window.dispatchEvent(new CustomEvent('alpine:component-error', {
                    detail: { component: 'exampleComponent', error }
                }));
            },
            
            // Example async method with error handling
            async fetchData() {
                try {
                    this.loading = true;
                    this.error = null;
                    
                    // Simulate API call
                    const response = await new Promise(resolve => 
                        setTimeout(() => resolve({ data: 'Success' }), 1000)
                    );
                    
                    return response.data;
                } catch (error) {
                    this.handleError(error);
                    return null;
                } finally {
                    this.loading = false;
                }
            }
        };
    });
})();
