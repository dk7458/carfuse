/**
 * CarFuse Dropdown Component
 * A reusable dropdown component using the component architecture
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Create dropdown component
    CarFuse.createComponent('dropdown', {
        // Component dependencies
        dependencies: ['core', 'events'],
        
        // Component properties
        props: {
            closeOnClickOutside: CarFuse.utils.PropValidator.types.boolean({ default: true }),
            closeOnSelect: CarFuse.utils.PropValidator.types.boolean({ default: true }),
            hoverMode: CarFuse.utils.PropValidator.types.boolean({ default: false }),
            hoverDelay: CarFuse.utils.PropValidator.types.number({ default: 200 }),
            position: CarFuse.utils.PropValidator.types.oneOf(['bottom', 'top', 'left', 'right'], { default: 'bottom' })
        },
        
        // Component state
        state: {
            openDropdowns: new Set(),
            dropdownRefs: new Map()
        },
        
        // Lifecycle: Prepare
        prepare() {
            this.handleDocumentClick = this.handleDocumentClick.bind(this);
            this.triggerSelector = '[data-dropdown-trigger]';
            this.contentSelector = '[data-dropdown-content]';
            this.hoverTimeouts = new Map();
        },
        
        // Lifecycle: Initialize
        initialize() {
            // Set up global click handler for closing dropdowns
            document.addEventListener('click', this.handleDocumentClick);
            
            return Promise.resolve();
        },
        
        // Lifecycle: Mount elements
        mountElements(elements) {
            elements.forEach(dropdown => {
                // Get dropdown ID
                const dropdownId = dropdown.id || `dropdown-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
                if (!dropdown.id) dropdown.id = dropdownId;
                
                // Parse options from data attribute
                let options = {};
                try {
                    if (dropdown.dataset.options) {
                        options = JSON.parse(dropdown.dataset.options);
                    }
                } catch (e) {
                    this.logError('Invalid options JSON', e);
                }
                
                // Get validated props
                const dropdownProps = this.setProps({
                    ...options,
                    dropdownId
                });
                
                // Store reference
                this.state.dropdownRefs.set(dropdownId, {
                    element: dropdown,
                    props: dropdownProps,
                    isOpen: false,
                    trigger: null,
                    content: null
                });
                
                // Set up dropdown DOM
                this.setupDropdownDOM(dropdown);
                
                // Set up event handlers
                this.setupDropdownEvents(dropdown);
            });
            
            return Promise.resolve();
        },
        
        // Set up dropdown DOM structure
        setupDropdownDOM(dropdown) {
            const dropdownId = dropdown.id;
            const dropdownRef = this.state.dropdownRefs.get(dropdownId);
            
            // Find trigger and content elements
            const trigger = dropdown.querySelector(this.triggerSelector);
            const content = dropdown.querySelector(this.contentSelector);
            
            // Store references
            if (trigger && content) {
                dropdownRef.trigger = trigger;
                dropdownRef.content = content;
                
                // Ensure proper attributes
                trigger.setAttribute('aria-haspopup', 'true');
                trigger.setAttribute('aria-expanded', 'false');
                
                content.setAttribute('aria-hidden', 'true');
                content.id = content.id || `${dropdownId}-content`;
                trigger.setAttribute('aria-controls', content.id);
                
                // Add position class
                if (dropdownRef.props.position) {
                    dropdown.classList.add(`dropdown-${dropdownRef.props.position}`);
                }
            } else {
                this.logError(`Dropdown ${dropdownId} missing trigger or content elements`);
            }
        },
        
        // Set up dropdown event handlers
        setupDropdownEvents(dropdown) {
            const dropdownId = dropdown.id;
            const dropdownRef = this.state.dropdownRefs.get(dropdownId);
            
            if (!dropdownRef.trigger || !dropdownRef.content) return;
            
            // Click handler for trigger
            dropdownRef.trigger.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggle(dropdown);
            });
            
            // Setup hover events if in hover mode
            if (dropdownRef.props.hoverMode) {
                dropdown.addEventListener('mouseenter', () => {
                    this.clearHoverTimeout(dropdownId);
                    this.hoverTimeouts.set(dropdownId, setTimeout(() => {
                        this.open(dropdown);
                    }, dropdownRef.props.hoverDelay));
                });
                
                dropdown.addEventListener('mouseleave', () => {
                    this.clearHoverTimeout(dropdownId);
                    this.hoverTimeouts.set(dropdownId, setTimeout(() => {
                        this.close(dropdown);
                    }, dropdownRef.props.hoverDelay));
                });
            }
            
            // Add click handlers to menu items
            dropdownRef.content.querySelectorAll('[data-dropdown-item]').forEach(item => {
                item.addEventListener('click', (e) => {
                    if (dropdownRef.props.closeOnSelect) {
                        this.close(dropdown);
                    }
                    
                    // Emit item selected event
                    const value = item.dataset.value || item.textContent.trim();
                    this.emit('itemSelected', { 
                        dropdownId, 
                        value, 
                        item, 
                        originalEvent: e 
                    });
                });
            });
        },
        
        // Clear hover timeouts
        clearHoverTimeout(dropdownId) {
            if (this.hoverTimeouts.has(dropdownId)) {
                clearTimeout(this.hoverTimeouts.get(dropdownId));
                this.hoverTimeouts.delete(dropdownId);
            }
        },
        
        // Handle document clicks (close dropdowns when clicking outside)
        handleDocumentClick(event) {
            for (const dropdownId of this.state.openDropdowns) {
                const dropdownRef = this.state.dropdownRefs.get(dropdownId);
                
                if (dropdownRef && dropdownRef.props.closeOnClickOutside) {
                    // Check if click is outside the dropdown
                    if (dropdownRef.element && !dropdownRef.element.contains(event.target)) {
                        this.close(dropdownRef.element);
                    }
                }
            }
        },
        
        // Toggle dropdown state
        toggle(dropdown) {
            const dropdownId = dropdown.id;
            const dropdownRef = this.state.dropdownRefs.get(dropdownId);
            
            if (!dropdownRef) return;
            
            if (dropdownRef.isOpen) {
                this.close(dropdown);
            } else {
                this.open(dropdown);
            }
        },
        
        // Open dropdown
        open(dropdown) {
            const dropdownId = dropdown.id;
            const dropdownRef = this.state.dropdownRefs.get(dropdownId);
            
            if (!dropdownRef || !dropdownRef.trigger || !dropdownRef.content || dropdownRef.isOpen) return;
            
            // Update state
            dropdownRef.isOpen = true;
            this.state.openDropdowns.add(dropdownId);
            
            // Update DOM
            dropdownRef.content.classList.add('open');
            dropdownRef.content.setAttribute('aria-hidden', 'false');
            dropdownRef.trigger.setAttribute('aria-expanded', 'true');
            dropdown.classList.add('active');
            
            // Position the dropdown
            this.positionDropdown(dropdown);
            
            // Emit open event
            this.emit('opened', { dropdownId });
        },
        
        // Close dropdown
        close(dropdown) {
            const dropdownId = dropdown.id;
            const dropdownRef = this.state.dropdownRefs.get(dropdownId);
            
            if (!dropdownRef || !dropdownRef.trigger || !dropdownRef.content || !dropdownRef.isOpen) return;
            
            // Update state
            dropdownRef.isOpen = false;
            this.state.openDropdowns.delete(dropdownId);
            
            // Update DOM
            dropdownRef.content.classList.remove('open');
            dropdownRef.content.setAttribute('aria-hidden', 'true');
            dropdownRef.trigger.setAttribute('aria-expanded', 'false');
            dropdown.classList.remove('active');
            
            // Emit close event
            this.emit('closed', { dropdownId });
        },
        
        // Position dropdown content
        positionDropdown(dropdown) {
            const dropdownRef = this.state.dropdownRefs.get(dropdown.id);
            
            if (!dropdownRef || !dropdownRef.content) return;
            
            // Reset any inline styles
            const content = dropdownRef.content;
            content.style.top = '';
            content.style.left = '';
            content.style.right = '';
            content.style.bottom = '';
            
            // Let CSS handle positioning if using classic dropdown
            if (dropdown.classList.contains('dropdown-classic')) {
                return;
            }
            
            // Calculate position based on available space
            const rect = dropdown.getBoundingClientRect();
            const contentRect = content.getBoundingClientRect();
            const position = dropdownRef.props.position;
            
            const viewportHeight = window.innerHeight;
            const viewportWidth = window.innerWidth;
            
            // Calculate space available in different directions
            const spaceBelow = viewportHeight - rect.bottom;
            const spaceAbove = rect.top;
            const spaceRight = viewportWidth - rect.right;
            const spaceLeft = rect.left;
            
            // Determine best direction based on available space if adaptive
            let direction = position;
            
            if (direction === 'bottom' && spaceBelow < contentRect.height && spaceAbove > spaceBelow) {
                direction = 'top';
            } else if (direction === 'top' && spaceAbove < contentRect.height && spaceBelow > spaceAbove) {
                direction = 'bottom';
            } else if (direction === 'right' && spaceRight < contentRect.width && spaceLeft > spaceRight) {
                direction = 'left';
            } else if (direction === 'left' && spaceLeft < contentRect.width && spaceRight > spaceLeft) {
                direction = 'right';
            }
            
            // Apply positioning
            switch (direction) {
                case 'bottom':
                    content.style.top = '100%';
                    content.style.left = '0';
                    break;
                case 'top':
                    content.style.bottom = '100%';
                    content.style.left = '0';
                    break;
                case 'left':
                    content.style.right = '100%';
                    content.style.top = '0';
                    break;
                case 'right':
                    content.style.left = '100%';
                    content.style.top = '0';
                    break;
            }
        },
        
        // Lifecycle: Clean up
        destroyComponent() {
            // Close all dropdowns
            [...this.state.openDropdowns].forEach(dropdownId => {
                const dropdownRef = this.state.dropdownRefs.get(dropdownId);
                if (dropdownRef && dropdownRef.element) {
                    this.close(dropdownRef.element);
                }
            });
            
            // Clear all timeouts
            this.hoverTimeouts.forEach(timeout => clearTimeout(timeout));
            this.hoverTimeouts.clear();
            
            // Remove global event listeners
            document.removeEventListener('click', this.handleDocumentClick);
        }
    });
})();
