/**
 * CarFuse Modal Component
 * A standard modal dialog component using the component architecture
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Create modal component
    CarFuse.createComponent('modal', {
        // Component dependencies
        dependencies: ['core', 'events'],
        
        // Component properties schema
        props: {
            closeOnEscape: CarFuse.utils.PropValidator.types.boolean({ default: true }),
            closeOnOverlayClick: CarFuse.utils.PropValidator.types.boolean({ default: true }),
            focusFirstElement: CarFuse.utils.PropValidator.types.boolean({ default: true }),
            modalClass: CarFuse.utils.PropValidator.types.string({ default: 'modal-dialog' })
        },
        
        // Component state
        state: {
            activeModals: [],
            modalRefs: new Map()
        },
        
        // Lifecycle: Prepare (synchronous)
        prepare() {
            this.handleKeydown = this.handleKeydown.bind(this);
            this.triggerClass = '[data-modal-trigger]';
        },
        
        // Lifecycle: Initialize (can be async)
        initialize() {
            // Register global event listeners
            document.addEventListener('keydown', this.handleKeydown);
            
            // Register with event bus for external control
            CarFuse.eventBus.on('modal:open', (modalId) => this.openById(modalId));
            CarFuse.eventBus.on('modal:close', (modalId) => this.closeById(modalId));
            
            return Promise.resolve();
        },
        
        // Lifecycle: Mount to DOM elements
        mountElements(elements) {
            elements.forEach(modal => {
                // Get modal ID
                const modalId = modal.id || `modal-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
                if (!modal.id) modal.id = modalId;
                
                // Parse options from data attribute
                let options = {};
                try {
                    if (modal.dataset.options) {
                        options = JSON.parse(modal.dataset.options);
                    }
                } catch (e) {
                    this.logError('Invalid options JSON', e);
                }
                
                // Store validated props for this modal
                const modalProps = this.setProps({
                    ...options,
                    modalId
                });
                
                // Store reference to modal configuration
                this.state.modalRefs.set(modalId, {
                    element: modal,
                    props: modalProps,
                    triggers: []
                });
                
                // Set up modal DOM
                this.setupModalDOM(modal, modalProps);
                
                // Set up event handlers for this modal
                this.setupModalEvents(modal);
            });
            
            // Find and initialize trigger elements
            this.setupTriggers();
            
            return Promise.resolve();
        },
        
        // Set up modal DOM structure
        setupModalDOM(modal, props) {
            // Add ARIA attributes
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
            modal.setAttribute('aria-hidden', 'true');
            
            // Add base classes if not present
            if (!modal.classList.contains('modal')) {
                modal.classList.add('modal');
            }
            
            // Ensure modal has the correct structure
            if (!modal.querySelector('.modal-content')) {
                // Wrap existing content in modal-content
                const content = document.createElement('div');
                content.className = 'modal-content';
                
                // Move all children to the content wrapper
                while (modal.firstChild) {
                    content.appendChild(modal.firstChild);
                }
                
                modal.appendChild(content);
            }
            
            // Add close button if not present
            if (!modal.querySelector('[data-modal-close]')) {
                const closeBtn = document.createElement('button');
                closeBtn.setAttribute('type', 'button');
                closeBtn.setAttribute('data-modal-close', '');
                closeBtn.className = 'modal-close';
                closeBtn.innerHTML = '<span aria-hidden="true">&times;</span>';
                closeBtn.setAttribute('aria-label', 'Close');
                
                // Add to modal content
                const content = modal.querySelector('.modal-content');
                content.insertBefore(closeBtn, content.firstChild);
            }
            
            // Check if modal is in document or needs to be added
            if (!modal.parentElement) {
                document.body.appendChild(modal);
            }
        },
        
        // Set up event handlers for a modal
        setupModalEvents(modal) {
            const modalId = modal.id;
            
            // Handle close button clicks
            modal.querySelectorAll('[data-modal-close]').forEach(closeBtn => {
                closeBtn.addEventListener('click', () => this.close(modal));
            });
            
            // Handle overlay clicks if enabled
            const modalRef = this.state.modalRefs.get(modalId);
            if (modalRef && modalRef.props.closeOnOverlayClick) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        this.close(modal);
                    }
                });
            }
        },
        
        // Set up trigger elements throughout the document
        setupTriggers() {
            document.querySelectorAll(this.triggerClass).forEach(trigger => {
                const targetId = trigger.dataset.modalTarget;
                if (!targetId) return;
                
                // Store trigger in modal reference
                const modalRef = this.state.modalRefs.get(targetId);
                if (modalRef) {
                    modalRef.triggers.push(trigger);
                }
                
                // Add click handler
                trigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.openById(targetId);
                });
            });
        },
        
        // Handle keyboard events
        handleKeydown(e) {
            // Handle escape key
            if (e.key === 'Escape' || e.key === 'Esc') {
                const activeModals = this.state.activeModals;
                if (activeModals.length > 0) {
                    const topModalId = activeModals[activeModals.length - 1];
                    const modalRef = this.state.modalRefs.get(topModalId);
                    
                    if (modalRef && modalRef.props.closeOnEscape) {
                        this.close(modalRef.element);
                        e.preventDefault();
                    }
                }
            }
            
            // Handle tab key for focus trapping
            if (e.key === 'Tab' && this.state.activeModals.length > 0) {
                const topModalId = this.state.activeModals[this.state.activeModals.length - 1];
                const modalRef = this.state.modalRefs.get(topModalId);
                
                if (modalRef) {
                    this.trapFocus(e, modalRef.element);
                }
            }
        },
        
        // Trap focus within the modal
        trapFocus(e, modal) {
            const focusableElements = modal.querySelectorAll(
                'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );
            
            if (focusableElements.length === 0) return;
            
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            
            if (e.shiftKey && document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        },
        
        // Open a modal by ID
        openById(modalId) {
            const modalRef = this.state.modalRefs.get(modalId);
            if (modalRef) {
                this.open(modalRef.element);
            } else {
                this.logError(`Modal with ID "${modalId}" not found`);
            }
        },
        
        // Open a modal
        open(modal) {
            const modalId = modal.id;
            
            // Skip if already open
            if (this.state.activeModals.includes(modalId)) {
                return;
            }
            
            // Store previous active element for focus return
            modal._previousActiveElement = document.activeElement;
            
            // Add modal to active modals stack
            this.state.activeModals.push(modalId);
            
            // Update DOM
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            
            if (this.state.activeModals.length > 1) {
                document.body.classList.add('modal-stacked');
            }
            
            // Set focus to first focusable element
            const modalRef = this.state.modalRefs.get(modalId);
            if (modalRef && modalRef.props.focusFirstElement) {
                setTimeout(() => {
                    const focusable = modal.querySelector(
                        'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
                    );
                    
                    if (focusable) {
                        focusable.focus();
                    }
                }, 100);
            }
            
            // Emit open event
            this.emit('opened', { modalId });
        },
        
        // Close modal by ID
        closeById(modalId) {
            const modalRef = this.state.modalRefs.get(modalId);
            if (modalRef) {
                this.close(modalRef.element);
            }
        },
        
        // Close a specific modal
        close(modal) {
            const modalId = modal.id;
            
            // Remove from active stack
            const index = this.state.activeModals.indexOf(modalId);
            if (index !== -1) {
                this.state.activeModals.splice(index, 1);
            }
            
            // Update DOM
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            
            // Update body classes
            if (this.state.activeModals.length === 0) {
                document.body.classList.remove('modal-open');
                document.body.classList.remove('modal-stacked');
            } else if (this.state.activeModals.length === 1) {
                document.body.classList.remove('modal-stacked');
            }
            
            // Return focus to previous element
            if (modal._previousActiveElement) {
                modal._previousActiveElement.focus();
                delete modal._previousActiveElement;
            }
            
            // Emit close event
            this.emit('closed', { modalId });
        },
        
        // Close all modals
        closeAll() {
            const activeModalIds = [...this.state.activeModals];
            activeModalIds.forEach(modalId => {
                this.closeById(modalId);
            });
        },
        
        // Lifecycle: Clean up on destroy
        destroyComponent() {
            // Close all modals
            this.closeAll();
            
            // Remove event listeners
            document.removeEventListener('keydown', this.handleKeydown);
            
            // Remove event bus listeners
            CarFuse.eventBus.off('modal:open');
            CarFuse.eventBus.off('modal:close');
        }
    });
})();
