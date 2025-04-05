/**
 * CarFuse HTMX Payment Module
 * Specialized module for handling payment operations with HTMX
 */

(function() {
    // Make sure dependencies are loaded
    if (typeof htmx === 'undefined') {
        console.error('HTMX library not found. Required for payment module.');
        return;
    }
    
    if (typeof window.CarFuseHTMX === 'undefined') {
        console.error('CarFuseHTMX core not found. Required for payment module.');
        return;
    }
    
    const CarFuseHTMX = window.CarFuseHTMX;
    CarFuseHTMX.log('Initializing payment module');
    
    // Add payment methods to CarFuseHTMX
    CarFuseHTMX.payment = {
        /**
         * Load payment details into a target element
         * @param {string|number} paymentId - ID of the payment
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when details are loaded
         */
        showDetails: function(paymentId, targetSelector = '#payment-details-container', options = {}) {
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            return CarFuseHTMX.ajax('GET', `/payment/${paymentId}/details`, {
                target: target,
                swap: options.swap || 'innerHTML',
                headers: options.headers || {}
            });
        },
        
        /**
         * Process a refund with confirmation
         * @param {string|number} paymentId - ID of the payment
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when refund is processed
         */
        refund: function(paymentId, targetSelector = '#payment-list', options = {}) {
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            const confirmMessage = options.confirmMessage || 'Czy na pewno chcesz zwrócić tę płatność?';
            
            if (!confirm(confirmMessage)) {
                return Promise.reject(new Error('Canceled by user'));
            }
            
            // Create form data for the request
            const formData = new FormData();
            
            // Add amount if provided
            if (options.amount) {
                formData.append('amount', options.amount);
            }
            
            // Add reason if provided
            if (options.reason) {
                formData.append('reason', options.reason);
            }
            
            return CarFuseHTMX.ajax('POST', `/payment/${paymentId}/refund`, {
                target: target,
                swap: options.swap || 'innerHTML',
                values: formData,
                headers: options.headers || {}
            });
        },
        
        /**
         * Verify a payment status
         * @param {string|number} paymentId - ID of the payment
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when payment is verified
         */
        verify: function(paymentId, targetSelector = '#payment-status', options = {}) {
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            return CarFuseHTMX.ajax('GET', `/payment/${paymentId}/verify`, {
                target: target,
                swap: options.swap || 'innerHTML',
                headers: options.headers || {}
            });
        },
        
        /**
         * Generate a PDF invoice
         * @param {string|number} paymentId - ID of the payment
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when PDF generation is complete
         */
        generateInvoice: function(paymentId, options = {}) {
            // This will open in a new window/tab instead of using HTMX swap
            const url = `/payment/${paymentId}/invoice`;
            window.open(url, '_blank');
            
            return Promise.resolve({
                success: true,
                message: 'Invoice generation initiated',
                url: url
            });
        },
        
        /**
         * Load payment transactions
         * @param {object} filters - Filter criteria
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when transactions are loaded
         */
        loadTransactions: function(filters = {}, targetSelector = '#transactions-list', options = {}) {
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            // Build query string from filters
            const params = new URLSearchParams();
            
            if (filters.page) params.append('page', filters.page);
            if (filters.limit) params.append('limit', filters.limit);
            if (filters.type) params.append('type', filters.type);
            if (filters.sortBy) params.append('sort_by', filters.sortBy);
            if (filters.sortDir) params.append('sort_dir', filters.sortDir);
            if (filters.dateFrom) params.append('date_from', filters.dateFrom);
            if (filters.dateTo) params.append('date_to', filters.dateTo);
            
            return CarFuseHTMX.ajax('GET', `/payment/transactions?${params.toString()}`, {
                target: target,
                swap: options.swap || 'innerHTML',
                headers: options.headers || {}
            });
        },
        
        /**
         * Set up event listeners for payment-related functionality
         * @param {HTMLElement} container - Container element to initialize (defaults to document)
         */
        init: function(container = document) {
            CarFuseHTMX.log('Initializing payment event listeners');
            
            // Handle payment details view
            container.addEventListener('click', event => {
                const detailsButton = event.target.closest('[data-payment-details]');
                if (detailsButton) {
                    event.preventDefault();
                    
                    const paymentId = detailsButton.getAttribute('data-payment-id');
                    const target = detailsButton.getAttribute('data-target') || '#payment-details-container';
                    
                    this.showDetails(paymentId, target);
                }
            });
            
            // Handle payment refund
            container.addEventListener('click', event => {
                const refundButton = event.target.closest('[data-payment-refund]');
                if (refundButton) {
                    event.preventDefault();
                    
                    const paymentId = refundButton.getAttribute('data-payment-id');
                    const target = refundButton.getAttribute('data-target') || '#payment-list';
                    
                    this.refund(paymentId, target);
                }
            });
            
            // Handle invoice generation
            container.addEventListener('click', event => {
                const invoiceButton = event.target.closest('[data-payment-invoice]');
                if (invoiceButton) {
                    event.preventDefault();
                    
                    const paymentId = invoiceButton.getAttribute('data-payment-id');
                    this.generateInvoice(paymentId);
                }
            });
        }
    };
    
    // Add payment methods to the global object
    Object.assign(window.CarFuseHTMX, CarFuseHTMX.payment);
    
    // Register custom event handlers for HTMX events related to payments
    document.addEventListener('htmx:afterSwap', event => {
        const target = event.detail.target;
        
        // Initialize payment components after they are loaded
        if (target.id === 'payment-container' || 
            target.classList.contains('payment-component') ||
            target.querySelector('.payment-component')) {
            
            // Initialize payment event handlers
            CarFuseHTMX.payment.init(target);
            
            // Dispatch custom event for other components to react to
            document.dispatchEvent(new CustomEvent('carfuse:payment-loaded', {
                detail: { targetId: target.id }
            }));
        }
    });
    
    // Initialize payment functionality when the document is ready
    if (document.readyState !== 'loading') {
        CarFuseHTMX.payment.init();
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            CarFuseHTMX.payment.init();
        });
    }
    
    CarFuseHTMX.log('Payment module initialized');
})();
