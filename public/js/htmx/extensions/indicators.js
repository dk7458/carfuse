/**
 * CarFuse HTMX Indicators Extension
 * Enhanced loading indicators for HTMX requests
 * 
 * Usage:
 * <button hx-get="/api/data" hx-ext="indicators" data-loading-class="loading">
 *   Load Data
 *   <span class="spinner" data-loading-indicator></span>
 * </button>
 */

(function() {
    // Ensure the CarFuseHTMX namespace exists
    if (typeof window.CarFuseHTMX !== 'object') {
        console.error('[CarFuseHTMX Indicators] CarFuseHTMX namespace is not defined');
        return;
    }

    // Ensure HTMX is loaded
    if (typeof window.htmx !== 'object') {
        console.error('[CarFuseHTMX Indicators] HTMX library is not loaded');
        return;
    }
    
    const indicatorsExtension = {
        onEvent: function(name, evt) {
            if (name === "htmx:beforeRequest") {
                const target = evt.detail.elt;
                
                // Add loading class if specified
                const loadingClass = target.getAttribute('data-loading-class');
                if (loadingClass) {
                    target.classList.add(loadingClass);
                }
                
                // Show loading indicators
                const indicators = target.querySelectorAll('[data-loading-indicator]');
                indicators.forEach(indicator => {
                    indicator.style.display = 'inline-block';
                });
                
                // Handle custom loading text
                const loadingTextAttr = target.getAttribute('data-loading-text');
                if (loadingTextAttr) {
                    target.dataset.originalText = target.innerText;
                    target.innerText = loadingTextAttr;
                }
                
                // Create global spinner if requested
                if (target.hasAttribute('data-global-indicator')) {
                    const globalSpinner = document.createElement('div');
                    globalSpinner.classList.add('htmx-global-indicator');
                    globalSpinner.innerHTML = '<div class="spinner"></div>';
                    document.body.appendChild(globalSpinner);
                    target.dataset.hasGlobalIndicator = "true";
                }
            }
            
            if (name === "htmx:afterRequest") {
                const target = evt.detail.elt;
                
                // Remove loading class if specified
                const loadingClass = target.getAttribute('data-loading-class');
                if (loadingClass) {
                    target.classList.remove(loadingClass);
                }
                
                // Hide loading indicators
                const indicators = target.querySelectorAll('[data-loading-indicator]');
                indicators.forEach(indicator => {
                    indicator.style.display = 'none';
                });
                
                // Restore original text
                if (target.dataset.originalText) {
                    target.innerText = target.dataset.originalText;
                    delete target.dataset.originalText;
                }
                
                // Remove global spinner if it was created
                if (target.dataset.hasGlobalIndicator === "true") {
                    const globalSpinner = document.querySelector('.htmx-global-indicator');
                    if (globalSpinner) {
                        globalSpinner.remove();
                    }
                    delete target.dataset.hasGlobalIndicator;
                }
            }
            
            if (name === "htmx:beforeSwap") {
                // Animate swapping if specified
                const target = evt.detail.target;
                if (target && target.hasAttribute('data-animated-swap')) {
                    target.classList.add('htmx-swapping');
                    
                    // Schedule removal of class after swap
                    setTimeout(() => {
                        target.classList.remove('htmx-swapping');
                    }, 300); // Duration should match CSS transition
                }
            }
        }
    };

    /**
     * Register the indicators extension with HTMX
     */
    window.CarFuseHTMX.registerExtension('indicators', indicatorsExtension);
    
    // Log initialization
    window.CarFuseHTMX.log('Indicators extension initialized');
})();
