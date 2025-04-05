/**
 * CarFuse HTMX Swap Extension
 * Enhanced content swapping for HTMX
 * 
 * Usage:
 * <div hx-get="/api/data" hx-ext="swap" data-swap-transition="fade">
 *   Loading...
 * </div>
 */

(function() {
    // Ensure the CarFuseHTMX namespace exists
    if (typeof window.CarFuseHTMX !== 'object') {
        console.error('[CarFuseHTMX Swap] CarFuseHTMX namespace is not defined');
        return;
    }

    // Ensure HTMX is loaded
    if (typeof window.htmx !== 'object') {
        console.error('[CarFuseHTMX Swap] HTMX library is not loaded');
        return;
    }
    
    // Define transitions
    const transitions = {
        fade: 'opacity 150ms ease-in-out',
        slide: 'transform 200ms ease-in-out, opacity 200ms ease-in-out',
        zoom: 'transform 200ms ease, opacity 200ms ease'
    };
    
    const swapExtension = {
        onEvent: function(name, evt) {
            if (name === "htmx:beforeSwap") {
                const source = evt.detail.elt;
                const target = evt.detail.target;
                
                if (!source || !target) return;
                
                // Apply transition based on data attribute
                const transition = source.getAttribute('data-swap-transition');
                if (transition && transitions[transition]) {
                    evt.detail.swapStyle = transitions[transition];
                }
                
                // Apply custom timing
                const timing = source.getAttribute('data-swap-timing');
                if (timing) {
                    const time = parseInt(timing, 10);
                    if (!isNaN(time)) {
                        evt.detail.swapDelay = time;
                    }
                }
            }
            
            if (name === "htmx:afterSwap") {
                const target = evt.detail.target;
                
                // Trigger custom events after swap
                if (target) {
                    // Notify CarFuse about the content change
                    document.dispatchEvent(new CustomEvent('carfuse:content-updated', {
                        detail: {
                            target: target,
                            source: evt.detail.elt
                        }
                    }));
                    
                    // Focus first focusable element if requested
                    if (target.hasAttribute('data-autofocus')) {
                        const focusable = target.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                        if (focusable) {
                            focusable.focus();
                        }
                    }
                }
            }
        }
    };

    /**
     * Register the swap extension with HTMX
     */
    window.CarFuseHTMX.registerExtension('swap', swapExtension);
    
    /**
     * Add a custom transition type to the swap extension
     * @param {string} name - Name of the transition
     * @param {string} definition - CSS transition definition
     */
    window.CarFuseHTMX.addTransition = function(name, definition) {
        if (typeof name === 'string' && typeof definition === 'string') {
            transitions[name] = definition;
            this.log(`Added custom transition: ${name}`);
        }
    };
    
    // Log initialization
    window.CarFuseHTMX.log('Swap extension initialized');
})();
