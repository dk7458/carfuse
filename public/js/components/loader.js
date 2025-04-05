/**
 * CarFuse Loader Component
 * Manages loading indicators for various operations
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        console.error('CarFuse global object is not defined.');
        return;
    }
    
    const CarFuse = window.CarFuse;
    
    // Check if Loader is already initialized
    if (CarFuse.loader) {
        console.warn('CarFuse Loader component already initialized.');
        return;
    }
    
    CarFuse.loader = {
        /**
         * Initialize Loader functionalities
         */
        init: function() {
            this.log('Initializing Loader component');
            this.setupGlobalPageLoader();
            this.setupHTMXIntegration();
            this.log('Loader component initialized');
        },
        
        /**
         * Log a message to the console if debug mode is enabled
         * @param {string} message - Message to log
         * @param {*} [data] - Optional data to include in log
         */
        log: function(message, data) {
            if (CarFuse.config.debug) {
                console.log(`[CarFuse Loader] ${message}`, data || '');
            }
        },
        
        /**
         * Setup global page loader
         */
        setupGlobalPageLoader: function() {
            this.log('Setting up global page loader');
            
            // Add a loading spinner to the page
            const loader = document.createElement('div');
            loader.id = 'global-loader';
            loader.className = 'fixed top-0 left-0 w-full h-full bg-gray-100 flex justify-center items-center z-50';
            loader.innerHTML = `
                <div class="spinner spinner-border-t text-blue-500 h-12 w-12"></div>
            `;
            document.body.appendChild(loader);
            
            // Hide the loader when the page is fully loaded
            window.addEventListener('load', () => {
                const loaderEl = document.getElementById('global-loader');
                if (loaderEl) {
                    loaderEl.remove();
                }
            });
        },
        
        /**
         * Setup HTMX integration for loading indicators
         */
        setupHTMXIntegration: function() {
            this.log('Setting up HTMX integration');
            
            document.body.addEventListener('htmx:beforeRequest', (event) => {
                const target = event.target;
                
                // Add loading class to the target element
                target.classList.add('htmx-request');
                
                // Show loading indicator if available
                const indicator = target.querySelector('.htmx-indicator');
                if (indicator) {
                    indicator.style.display = 'block';
                }
            });
            
            document.body.addEventListener('htmx:afterRequest', (event) => {
                const target = event.target;
                
                // Remove loading class from the target element
                target.classList.remove('htmx-request');
                
                // Hide loading indicator if available
                const indicator = target.querySelector('.htmx-indicator');
                if (indicator) {
                    indicator.style.display = 'none';
                }
            });
        },
        
        /**
         * Show loading indicator on a button
         * @param {HTMLButtonElement} button - Button to show loading indicator on
         */
        showButtonLoading: function(button) {
            button.disabled = true;
            button.classList.add('loading');
            button.setAttribute('data-original-text', button.textContent);
            
            // Create spinner element
            const spinner = document.createElement('span');
            spinner.className = 'btn-spinner mr-2';
            spinner.innerHTML = `<div class="spinner spinner-border-t h-4 w-4"></div>`;
            
            // Store original content and replace with spinner + text
            button.prepend(spinner);
            const textSpan = document.createElement('span');
            textSpan.textContent = 'Ładowanie...';
            
            // Replace button content or append to it
            if (button.querySelector('.btn-text')) {
                button.querySelector('.btn-text').textContent = 'Ładowanie...';
            } else {
                button.appendChild(textSpan);
            }
        },
        
        /**
         * Hide loading indicator on a button
         * @param {HTMLButtonElement} button - Button to hide loading indicator on
         */
        hideButtonLoading: function(button) {
            button.disabled = false;
            button.classList.remove('loading');
            
            // Remove spinner
            const spinner = button.querySelector('.btn-spinner');
            if (spinner) {
                spinner.remove();
            }
            
            // Restore original text
            if (button.dataset.originalText) {
                if (button.querySelector('.btn-text')) {
                    button.querySelector('.btn-text').textContent = button.dataset.originalText;
                } else {
                    button.textContent = button.dataset.originalText;
                }
                delete button.dataset.originalText;
            }
        },
        
        /**
         * Show loading progress bar
         * @param {HTMLElement} target - Target element to show progress bar in
         * @param {number} progress - Progress percentage
         */
        showProgressBar: function(target, progress) {
            // Create progress bar element
            let progressBar = target.querySelector('.progress-bar');
            if (!progressBar) {
                progressBar = document.createElement('div');
                progressBar.className = 'progress-bar bg-gray-200 rounded-full h-2 mb-4';
                progressBar.innerHTML = `
                    <div class="progress-bar-inner bg-blue-500 h-2 rounded-full" style="width: 0%"></div>
                `;
                target.appendChild(progressBar);
            }
            
            // Update progress
            const progressBarInner = progressBar.querySelector('.progress-bar-inner');
            progressBarInner.style.width = `${progress}%`;
        },
        
        /**
         * Hide loading progress bar
         * @param {HTMLElement} target - Target element to hide progress bar in
         */
        hideProgressBar: function(target) {
            const progressBar = target.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.remove();
            }
        }
    };
    
    // Initialize Loader component
    CarFuse.loader.init();
})();
