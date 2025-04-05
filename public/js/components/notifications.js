/**
 * CarFuse Notifications Component
 * Manages user feedback through toast notifications and alerts
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Check if Notifications is already initialized
    if (CarFuse.notifications) {
        console.warn('CarFuse Notifications component already initialized.');
        return;
    }
    
    // Define the component
    const notificationsComponent = {
        /**
         * Initialize Notifications functionalities
         */
        init: function() {
            this.log('Initializing Notifications component');
            this.log('Notifications component initialized');
        },
        
        /**
         * Log a message to the console if debug mode is enabled
         * @param {string} message - Message to log
         * @param {*} [data] - Optional data to include in log
         */
        log: function(message, data) {
            if (CarFuse.config.debug) {
                console.log(`[CarFuse Notifications] ${message}`, data || '');
            }
        },
        
        /**
         * Show toast notification
         * @param {string} title - Toast title
         * @param {string} message - Toast message
         * @param {string} type - Toast type: success, error, warning, info
         * @param {number} duration - Duration in milliseconds
         */
        showToast: function(title, message, type = 'success', duration = 5000) {
            // Check if we have Alpine.js toast system
            const toastSystem = document.querySelector('[x-data*="toastSystem"]');
            
            if (toastSystem && window.Alpine) {
                // Use Alpine.js toast system
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { title, message, type, duration }
                }));
            } else {
                // Fallback to simple toast implementation
                const toast = document.createElement('div');
                toast.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-md';
                
                // Apply styles based on type
                switch (type) {
                    case 'success':
                        toast.classList.add('bg-green-500', 'text-white');
                        break;
                    case 'error':
                        toast.classList.add('bg-red-500', 'text-white');
                        break;
                    case 'warning':
                        toast.classList.add('bg-yellow-500', 'text-white');
                        break;
                    case 'info':
                        toast.classList.add('bg-blue-500', 'text-white');
                        break;
                }
                
                // Create toast content
                toast.innerHTML = `
                    <div class="flex items-center">
                        <div class="flex-1">
                            <h3 class="font-medium">${title}</h3>
                            <p class="text-sm opacity-90">${message}</p>
                        </div>
                        <button class="ml-4 text-white" onclick="this.parentNode.parentNode.remove()">×</button>
                    </div>
                `;
                
                document.body.appendChild(toast);
                
                // Remove after specified duration
                setTimeout(() => {
                    toast.remove();
                }, duration);
            }
        },
        
        /**
         * Show alert message
         * @param {string} message - Alert message
         * @param {string} type - Alert type: success, error, warning, info
         */
        showAlert: function(message, type = 'info') {
            // Create alert element
            const alert = document.createElement('div');
            alert.className = 'fixed top-4 left-4 z-50 p-4 rounded-lg shadow-lg max-w-md';
            
            // Apply styles based on type
            switch (type) {
                case 'success':
                    alert.classList.add('bg-green-500', 'text-white');
                    break;
                case 'error':
                    alert.classList.add('bg-red-500', 'text-white');
                    break;
                case 'warning':
                    alert.classList.add('bg-yellow-500', 'text-white');
                    break;
                case 'info':
                    alert.classList.add('bg-blue-500', 'text-white');
                    break;
            }
            
            // Create alert content
            alert.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-sm opacity-90">${message}</p>
                    </div>
                    <button class="ml-4 text-white" onclick="this.parentNode.parentNode.remove()">×</button>
                </div>
            `;
            
            document.body.appendChild(alert);
            
            // Remove after 5 seconds
            setTimeout(() => {
                alert.remove();
            }, 5000);
        },
        
        /**
         * Show confirmation dialog
         * @param {string} message - Confirmation message
         * @param {Function} onConfirm - Callback function on confirm
         * @param {Function} onCancel - Callback function on cancel
         */
        showConfirmation: function(message, onConfirm, onCancel) {
            if (confirm(message)) {
                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            } else {
                if (typeof onCancel === 'function') {
                    onCancel();
                }
            }
        }
    };
    
    // Register the component
    CarFuse.notifications = notificationsComponent;

    // Initialize the component if CarFuse is ready
    if (CarFuse.registerComponent) {
        CarFuse.registerComponent('notifications', CarFuse.notifications);
    } else {
        console.warn('CarFuse.registerComponent is not available. Make sure core.js is loaded first.');
    }
})();
