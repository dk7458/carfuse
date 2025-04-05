/**
 * CarFuse HTMX Booking Module
 * Specialized module for handling booking operations with HTMX
 */

(function() {
    // Make sure dependencies are loaded
    if (typeof htmx === 'undefined') {
        console.error('HTMX library not found. Required for booking module.');
        return;
    }
    
    if (typeof window.CarFuseHTMX === 'undefined') {
        console.error('CarFuseHTMX core not found. Required for booking module.');
        return;
    }
    
    const CarFuseHTMX = window.CarFuseHTMX;
    CarFuseHTMX.log('Initializing booking module');
    
    // Add booking methods to CarFuseHTMX
    CarFuseHTMX.booking = {
        /**
         * Load booking details into a target element
         * @param {string|number} bookingId - ID of the booking
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when details are loaded
         */
        showDetails: function(bookingId, targetSelector = '#booking-details-container', options = {}) {
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            return CarFuseHTMX.ajax('GET', `/booking/${bookingId}/details`, {
                target: target,
                swap: options.swap || 'innerHTML',
                headers: options.headers || {}
            });
        },
        
        /**
         * Cancel a booking with confirmation
         * @param {string|number} bookingId - ID of the booking
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when booking is canceled
         */
        cancel: function(bookingId, targetSelector = '#booking-list', options = {}) {
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            const confirmMessage = options.confirmMessage || 'Czy na pewno chcesz anulować tę rezerwację?';
            
            if (!confirm(confirmMessage)) {
                return Promise.reject(new Error('Canceled by user'));
            }
            
            return CarFuseHTMX.ajax('POST', `/booking/${bookingId}/cancel`, {
                target: target,
                swap: options.swap || 'innerHTML',
                headers: options.headers || {}
            });
        },
        
        /**
         * Reschedule a booking
         * @param {string|number} bookingId - ID of the booking
         * @param {object} newDates - New dates for the booking
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when booking is rescheduled
         */
        reschedule: function(bookingId, newDates, targetSelector = '#booking-list', options = {}) {
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            // Validate dates
            if (!newDates || !newDates.pickup || !newDates.dropoff) {
                return Promise.reject(new Error('Invalid dates provided for rescheduling'));
            }
            
            // Create form data for the request
            const formData = new FormData();
            formData.append('pickup_date', newDates.pickup);
            formData.append('dropoff_date', newDates.dropoff);
            
            return CarFuseHTMX.ajax('POST', `/booking/${bookingId}/reschedule`, {
                target: target,
                swap: options.swap || 'innerHTML',
                values: formData,
                headers: options.headers || {}
            });
        },
        
        /**
         * Create a new booking
         * @param {object} bookingData - Booking data
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when booking is created
         */
        create: function(bookingData, targetSelector = '#booking-form-container', options = {}) {
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            return CarFuseHTMX.ajax('POST', '/booking/create', {
                target: target,
                swap: options.swap || 'innerHTML',
                values: bookingData,
                headers: options.headers || {}
            });
        },
        
        /**
         * Load booking history/logs
         * @param {string|number} bookingId - ID of the booking
         * @param {string} targetSelector - CSS selector for the target element
         * @param {object} options - Additional options
         * @returns {Promise} Promise that resolves when logs are loaded
         */
        loadLogs: function(bookingId, targetSelector, options = {}) {
            const target = document.querySelector(targetSelector);
            if (!target) {
                return Promise.reject(new Error(`Target not found: ${targetSelector}`));
            }
            
            return CarFuseHTMX.ajax('GET', `/booking/${bookingId}/logs`, {
                target: target,
                swap: options.swap || 'innerHTML',
                headers: options.headers || {}
            });
        },
        
        /**
         * Set up event listeners for booking-related functionality
         * @param {HTMLElement} container - Container element to initialize (defaults to document)
         */
        init: function(container = document) {
            CarFuseHTMX.log('Initializing booking event listeners');
            
            // Handle booking details toggle
            container.addEventListener('click', event => {
                const toggleButton = event.target.closest('[data-booking-details-toggle]');
                if (toggleButton) {
                    const bookingId = toggleButton.getAttribute('data-booking-id');
                    const targetId = toggleButton.getAttribute('data-target') || `#booking-details-${bookingId}`;
                    const target = document.querySelector(targetId);
                    
                    if (target) {
                        if (target.classList.contains('hidden')) {
                            target.classList.remove('hidden');
                            this.loadLogs(bookingId, `${targetId} .booking-logs-container`);
                        } else {
                            target.classList.add('hidden');
                        }
                    }
                }
            });
            
            // Handle booking cancellation
            container.addEventListener('click', event => {
                const cancelButton = event.target.closest('[data-booking-cancel]');
                if (cancelButton) {
                    event.preventDefault();
                    
                    const bookingId = cancelButton.getAttribute('data-booking-id');
                    const target = cancelButton.getAttribute('data-target') || '#booking-list';
                    
                    this.cancel(bookingId, target);
                }
            });
        }
    };
    
    // Add booking methods to the global object
    Object.assign(window.CarFuseHTMX, CarFuseHTMX.booking);
    
    // Register custom event handlers for HTMX events related to bookings
    document.addEventListener('htmx:afterSwap', event => {
        const target = event.detail.target;
        
        // Initialize booking components after they are loaded
        if (target.id === 'booking-form-container' || 
            target.classList.contains('booking-component') ||
            target.querySelector('.booking-component')) {
            
            // Initialize date pickers after booking form is loaded
            if (typeof window.initDateRangePickers === 'function') {
                window.initDateRangePickers();
            }
            
            // Initialize booking event handlers
            CarFuseHTMX.booking.init(target);
            
            // Dispatch custom event for other components to react to
            document.dispatchEvent(new CustomEvent('carfuse:booking-form-loaded', {
                detail: { targetId: target.id }
            }));
        }
    });
    
    // Initialize booking functionality when the document is ready
    if (document.readyState !== 'loading') {
        CarFuseHTMX.booking.init();
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            CarFuseHTMX.booking.init();
        });
    }
    
    CarFuseHTMX.log('Booking module initialized');
})();
