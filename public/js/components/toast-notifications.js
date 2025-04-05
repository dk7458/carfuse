/**
 * CarFuse Toast Notifications Component
 * Provides a standardized system for displaying toast notifications
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Create toast component
    CarFuse.createComponent('notifications', {
        // Component dependencies
        dependencies: ['core', 'events'],
        
        // Component properties
        props: {
            position: CarFuse.utils.PropValidator.types.oneOf([
                'top-right', 'top-left', 'bottom-right', 'bottom-left', 'top-center', 'bottom-center'
            ], { default: 'top-right' }),
            maxToasts: CarFuse.utils.PropValidator.types.number({ default: 5 }),
            duration: CarFuse.utils.PropValidator.types.number({ default: 5000 }),
            pauseOnHover: CarFuse.utils.PropValidator.types.boolean({ default: true }),
            dismissible: CarFuse.utils.PropValidator.types.boolean({ default: true }),
            escapeHTML: CarFuse.utils.PropValidator.types.boolean({ default: true }),
            newestOnTop: CarFuse.utils.PropValidator.types.boolean({ default: true }),
            closeButton: CarFuse.utils.PropValidator.types.boolean({ default: true }),
            progressBar: CarFuse.utils.PropValidator.types.boolean({ default: true })
        },
        
        // Component state
        state: {
            toasts: [],
            containerElement: null,
            hoverState: false
        },
        
        // Lifecycle: Prepare
        prepare() {
            // Bind methods
            this.handleToastEvent = this.handleToastEvent.bind(this);
            this.handleMouseEnter = this.handleMouseEnter.bind(this);
            this.handleMouseLeave = this.handleMouseLeave.bind(this);
        },
        
        // Lifecycle: Initialize
        initialize() {
            // Listen for toast events
            document.addEventListener('carfuse:toast', this.handleToastEvent);
            document.addEventListener('show-toast', this.handleToastEvent);
            
            return Promise.resolve();
        },
        
        // Lifecycle: Mount elements
        mountElements(elements) {
            elements.forEach(container => {
                // Parse options from data attribute
                let options = {};
                try {
                    if (container.dataset.options) {
                        options = JSON.parse(container.dataset.options);
                    }
                } catch (e) {
                    this.logError('Invalid options JSON', e);
                }
                
                // Store validated props
                this.setProps({
                    ...options
                });
                
                // Set up toast container
                this.setupToastContainer(container);
                
                // Store state
                this.state.containerElement = container;
                
                // Hover state tracking
                container.addEventListener('mouseenter', this.handleMouseEnter);
                container.addEventListener('mouseleave', this.handleMouseLeave);
            });
            
            return Promise.resolve();
        },

        // Set up toast container
        setupToastContainer(container) {
            // Add appropriate classes based on position
            container.classList.add('toast-container');
            container.classList.add(`toast-${this.props.position}`);
            
            // Ensure ARIA attributes
            container.setAttribute('role', 'alert');
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'true');
        },
        
        // Handle mouse enter - pause toasts
        handleMouseEnter() {
            if (!this.props.pauseOnHover) return;
            
            this.state.hoverState = true;
            
            // Pause all toast timers
            this.state.toasts.forEach(toast => {
                if (toast.timeoutId) {
                    clearTimeout(toast.timeoutId);
                    toast.timeoutId = null;
                }
                
                // Pause progress bars
                const progressBar = document.querySelector(`#${toast.id} .toast-progress`);
                if (progressBar) {
                    progressBar.style.animationPlayState = 'paused';
                }
            });
        },
        
        // Handle mouse leave - resume toasts
        handleMouseLeave() {
            if (!this.props.pauseOnHover) return;
            
            this.state.hoverState = false;
            
            // Resume all toast timers
            this.state.toasts.forEach(toast => {
                if (!toast.timeoutId && toast.duration > 0) {
                    toast.timeoutId = setTimeout(() => {
                        this.removeToast(toast.id);
                    }, toast.remaining);
                }
                
                // Resume progress bars
                const progressBar = document.querySelector(`#${toast.id} .toast-progress`);
                if (progressBar) {
                    progressBar.style.animationPlayState = 'running';
                }
            });
        },
        
        // Handle toast event
        handleToastEvent(event) {
            const { title, message, type, ...options } = event.detail;
            this.showToast(title, message, type, options);
        },
        
        // Show a toast notification
        showToast(title, message, type = 'info', options = {}) {
            // Create toast object
            const toast = {
                id: options.id || `toast-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
                title: this.props.escapeHTML ? this.escapeHTML(title) : title,
                message: this.props.escapeHTML ? this.escapeHTML(message) : message,
                type: this.getValidToastType(type),
                timestamp: new Date(),
                duration: options.duration !== undefined ? options.duration : this.props.duration,
                remaining: options.duration !== undefined ? options.duration : this.props.duration,
                dismissible: options.dismissible !== undefined ? options.dismissible : this.props.dismissible,
                actions: options.actions || [],
                errorId: options.errorId || null,
                recoverable: !!options.recoverable,
                timeoutId: null
            };
            
            // Check if this is replacing an existing toast with the same ID
            const existingIndex = this.state.toasts.findIndex(t => t.id === toast.id);
            if (existingIndex >= 0) {
                // Remove the existing toast
                this.removeToastElement(this.state.toasts[existingIndex].id);
                this.state.toasts.splice(existingIndex, 1);
            }
            
            // Check if we need to remove oldest toasts
            if (this.state.toasts.length >= this.props.maxToasts) {
                const toastToRemove = this.props.newestOnTop ? 
                    this.state.toasts[this.state.toasts.length - 1] :
                    this.state.toasts[0];
                    
                this.removeToast(toastToRemove.id);
            }
            
            // Add toast to state
            if (this.props.newestOnTop) {
                this.state.toasts.unshift(toast);
            } else {
                this.state.toasts.push(toast);
            }
            
            // Render the toast
            this.renderToast(toast);
            
            // Set timeout for auto-dismissal if duration is positive
            if (toast.duration > 0 && !this.state.hoverState) {
                toast.timeoutId = setTimeout(() => {
                    this.removeToast(toast.id);
                }, toast.duration);
            }
            
            return toast.id;
        },
        
        // Render a toast element
        renderToast(toast) {
            if (!this.state.containerElement) return;
            
            // Create toast element
            const toastElement = document.createElement('div');
            toastElement.id = toast.id;
            toastElement.className = `toast toast-${toast.type}`;
            toastElement.setAttribute('role', 'alert');
            toastElement.setAttribute('aria-live', 'assertive');
            
            // Toast inner HTML
            let toastContent = `
                <div class="toast-content">
                    ${toast.title ? `<div class="toast-header">${toast.title}</div>` : ''}
                    <div class="toast-body">${toast.message}</div>
                    ${toast.actions.length > 0 ? this.renderToastActions(toast) : ''}
                </div>
            `;
            
            // Add close button if dismissible
            if (toast.dismissible && this.props.closeButton) {
                toastContent += `
                    <button type="button" class="toast-close" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                `;
            }
            
            // Add progress bar if enabled
            if (this.props.progressBar && toast.duration > 0) {
                toastContent += `
                    <div class="toast-progress" style="animation-duration: ${toast.duration}ms"></div>
                `;
            }
            
            toastElement.innerHTML = toastContent;
            
            // Add click handler for close button
            const closeButton = toastElement.querySelector('.toast-close');
            if (closeButton) {
                closeButton.addEventListener('click', () => this.removeToast(toast.id));
            }
            
            // Add click handlers for action buttons
            toast.actions.forEach((action, index) => {
                const actionButton = toastElement.querySelector(`.toast-action-${index}`);
                if (actionButton && action.onClick) {
                    actionButton.addEventListener('click', () => {
                        action.onClick(toast);
                        if (action.closeOnClick !== false) {
                            this.removeToast(toast.id);
                        }
                    });
                }
            });
            
            // Add to DOM
            if (this.props.newestOnTop) {
                this.state.containerElement.prepend(toastElement);
            } else {
                this.state.containerElement.appendChild(toastElement);
            }
            
            // Trigger animation
            setTimeout(() => {
                toastElement.classList.add('toast-show');
            }, 10);
            
            return toastElement;
        },
        
        // Render toast action buttons
        renderToastActions(toast) {
            if (!toast.actions || toast.actions.length === 0) {
                return '';
            }
            
            const actionButtons = toast.actions.map((action, index) => {
                const btnClass = action.class || `btn-${action.style || 'secondary'}`;
                return `
                    <button type="button" class="toast-action toast-action-${index} ${btnClass}">
                        ${this.escapeHTML(action.text)}
                    </button>
                `;
            }).join('');
            
            return `<div class="toast-actions">${actionButtons}</div>`;
        },
        
        // Remove a toast
        removeToast(toastId) {
            // Find toast in state
            const toastIndex = this.state.toasts.findIndex(toast => toast.id === toastId);
            if (toastIndex === -1) return;
            
            // Clear timeout if exists
            const toast = this.state.toasts[toastIndex];
            if (toast.timeoutId) {
                clearTimeout(toast.timeoutId);
            }
            
            // Remove toast element with animation
            this.removeToastElement(toastId);
            
            // Remove from state after animation completes
            setTimeout(() => {
                this.state.toasts.splice(toastIndex, 1);
            }, 300);
        },
        
        // Remove toast element with animation
        removeToastElement(toastId) {
            const toastElement = document.getElementById(toastId);
            if (!toastElement) return;
            
            // Start removal animation
            toastElement.classList.remove('toast-show');
            toastElement.classList.add('toast-hide');
            
            // Remove from DOM after animation
            setTimeout(() => {
                if (toastElement.parentNode) {
                    toastElement.parentNode.removeChild(toastElement);
                }
            }, 300);
        },
        
        // Clear all toasts
        clearToasts() {
            // Clone the toast array to avoid modification during iteration
            [...this.state.toasts].forEach(toast => {
                this.removeToast(toast.id);
            });
        },
        
        // Get valid toast type
        getValidToastType(type) {
            const validTypes = ['success', 'error', 'warning', 'info'];
            return validTypes.includes(type) ? type : 'info';
        },
        
        // Escape HTML to prevent XSS
        escapeHTML(str) {
            if (!str || typeof str !== 'string') return '';
            
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },
        
        // Lifecycle: Destroy
        destroyComponent() {
            // Remove event listeners
            document.removeEventListener('carfuse:toast', this.handleToastEvent);
            document.removeEventListener('show-toast', this.handleToastEvent);
            
            if (this.state.containerElement) {
                this.state.containerElement.removeEventListener('mouseenter', this.handleMouseEnter);
                this.state.containerElement.removeEventListener('mouseleave', this.handleMouseLeave);
            }
            
            // Clear all toasts
            this.clearToasts();
        }
    });
})();
