/**
 * CarFuse Alpine.js Modal Component
 * A reusable modal component using Alpine.js
 */

(function() {
    // Check if Alpine.js is available
    if (typeof Alpine === 'undefined') {
        console.error('Alpine.js is not loaded! Make sure to include Alpine.js before this script.');
        return;
    }
    
    // Check if CarFuseAlpine is available
    if (typeof window.CarFuseAlpine === 'undefined') {
        console.error('CarFuseAlpine is not available! Make sure to load it before this component.');
        return;
    }
    
    // Create modal component
    window.CarFuseAlpine.createComponent('modal', (options = {}) => {
        return {
            // Modal state
            isOpen: false,
            initialFocusElement: null,
            returnFocusElement: null,
            
            // Configuration
            config: {
                closeOnEscape: options.closeOnEscape !== false,
                closeOnOverlayClick: options.closeOnOverlayClick !== false,
                focusFirst: options.focusFirst !== false,
                preventScroll: options.preventScroll !== false,
                size: options.size || 'md',
                transition: options.transition || 'fade',
                ...options
            },
            
            // Initialize component
            initialize() {
                // Find the initial focus element if specified
                if (this.$el.querySelector('[x-init-focus]')) {
                    this.initialFocusElement = this.$el.querySelector('[x-init-focus]');
                }
                
                // Register with global modal manager if available
                if (window.modalManager) {
                    window.modalManager.register(this.$el.id || this.$id);
                }
                
                // Check if modal should be open by default
                if (this.$el.hasAttribute('x-init-open')) {
                    this.$nextTick(() => this.open());
                }
                
                // Register global listeners
                this.$watch('isOpen', (value) => {
                    if (value) {
                        document.addEventListener('keydown', this.handleKeydown);
                    } else {
                        document.removeEventListener('keydown', this.handleKeydown);
                    }
                });
            },
            
            // Open the modal
            open() {
                return this.withLoading(async () => {
                    // Store element that had focus before opening modal
                    this.returnFocusElement = document.activeElement;
                    
                    // Open the modal
                    this.isOpen = true;
                    
                    if (this.config.preventScroll) {
                        document.body.classList.add('overflow-hidden');
                    }
                    
                    // Focus the first focusable element
                    this.$nextTick(() => {
                        if (this.initialFocusElement) {
                            this.initialFocusElement.focus();
                        } else if (this.config.focusFirst) {
                            this.focusFirstElement();
                        }
                    });
                    
                    // Emit open event
                    this.$dispatch('modal-opened', { id: this.$el.id || this.$id });
                }, 'opening modal');
            },
            
            // Close the modal
            close() {
                this.isOpen = false;
                
                if (this.config.preventScroll) {
                    document.body.classList.remove('overflow-hidden');
                }
                
                // Return focus to the element that had focus before opening
                if (this.returnFocusElement) {
                    this.$nextTick(() => {
                        this.returnFocusElement.focus();
                    });
                }
                
                // Emit close event
                this.$dispatch('modal-closed', { id: this.$el.id || this.$id });
            },
            
            // Handle keydown events
            handleKeydown(event) {
                // Close on escape
                if (event.key === 'Escape' && this.config.closeOnEscape) {
                    this.close();
                    event.preventDefault();
                }
                
                // Handle tab key for focus trapping
                if (event.key === 'Tab') {
                    this.trapFocus(event);
                }
            },
            
            // Handle overlay click
            handleOverlayClick(event) {
                if (event.target === event.currentTarget && this.config.closeOnOverlayClick) {
                    this.close();
                }
            },
            
            // Focus the first focusable element in the modal
            focusFirstElement() {
                const focusable = this.$el.querySelector(
                    'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
                );
                
                if (focusable) {
                    focusable.focus();
                }
            },
            
            // Trap focus within the modal
            trapFocus(event) {
                const focusableElements = this.$el.querySelectorAll(
                    'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
                );
                
                if (focusableElements.length === 0) return;
                
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];
                
                if (event.shiftKey && document.activeElement === firstElement) {
                    lastElement.focus();
                    event.preventDefault();
                } else if (!event.shiftKey && document.activeElement === lastElement) {
                    firstElement.focus();
                    event.preventDefault();
                }
            }
        };
    }, { autoInit: true });
    
    // Register global modal manager
    if (!window.modalManager) {
        window.modalManager = {
            modals: {},
            
            register(id) {
                this.modals[id] = true;
            },
            
            open(id) {
                window.dispatchEvent(new CustomEvent('open-modal', { 
                    detail: { id } 
                }));
            },
            
            close(id) {
                window.dispatchEvent(new CustomEvent('close-modal', { 
                    detail: { id } 
                }));
            }
        };
        
        // Event handlers for opening/closing modals
        window.addEventListener('open-modal', (e) => {
            const modalId = e.detail.id;
            const modalEl = document.getElementById(modalId);
            
            if (modalEl && modalEl.__x) {
                modalEl.__x.getUnobservedData().open();
            }
        });
        
        window.addEventListener('close-modal', (e) => {
            const modalId = e.detail.id;
            if (!modalId) {
                // Close all modals if no specific ID
                document.querySelectorAll('[x-data*="modal"]').forEach(modal => {
                    if (modal.__x) {
                        modal.__x.getUnobservedData().close();
                    }
                });
            } else {
                const modalEl = document.getElementById(modalId);
                if (modalEl && modalEl.__x) {
                    modalEl.__x.getUnobservedData().close();
                }
            }
        });
    }
})();
