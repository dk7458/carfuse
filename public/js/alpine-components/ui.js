/**
 * CarFuse Alpine.js UI Components
 * Common UI components (modals, dropdowns, tabs)
 */

document.addEventListener('alpine:init', () => {
    // Modal Dialog Component
    Alpine.data('modal', (options = {}) => ({
        isOpen: false,
        title: options.title || '',
        showCloseButton: options.showCloseButton !== false,
        escToClose: options.escToClose !== false,
        clickOutsideToClose: options.clickOutsideToClose !== false,
        size: options.size || 'md', // sm, md, lg, xl, or full
        
        // Map sizes to Tailwind classes
        sizeClasses: {
            sm: 'max-w-md',
            md: 'max-w-lg',
            lg: 'max-w-2xl',
            xl: 'max-w-4xl',
            full: 'max-w-full mx-4'
        },
        
        init() {
            // Setup event listeners
            if (this.escToClose) {
                window.addEventListener('keydown', e => {
                    if (this.isOpen && e.key === 'Escape') {
                        this.close();
                    }
                });
            }
            
            // Register a global modal event listener
            window.addEventListener('open-modal', e => {
                if (e.detail.id === this.$el.id) {
                    this.open(e.detail.data);
                }
            });
            
            window.addEventListener('close-modal', e => {
                if (!e.detail.id || e.detail.id === this.$el.id) {
                    this.close();
                }
            });
        },
        
        getSizeClass() {
            return this.sizeClasses[this.size] || this.sizeClasses.md;
        },
        
        open(data) {
            // Trigger an event that can be captured by parent components
            this.$dispatch('modal-opening', { id: this.$el.id, data });
            this.isOpen = true;
            document.body.classList.add('overflow-hidden');
            
            // Focus the first focusable element in the modal
            this.$nextTick(() => {
                const focusable = this.$el.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                if (focusable.length > 0) {
                    focusable[0].focus();
                }
                
                this.$dispatch('modal-opened', { id: this.$el.id, data });
            });
        },
        
        close() {
            // Trigger an event that can be captured by parent components
            this.$dispatch('modal-closing', { id: this.$el.id });
            this.isOpen = false;
            document.body.classList.remove('overflow-hidden');
            
            this.$nextTick(() => {
                this.$dispatch('modal-closed', { id: this.$el.id });
            });
        },
        
        handleBackdropClick(e) {
            if (this.clickOutsideToClose && e.target === e.currentTarget) {
                this.close();
            }
        }
    }));
    
    // Dropdown Component
    Alpine.data('dropdown', (options = {}) => ({
        open: false,
        position: options.position || 'bottom-right', // top-left, top-right, bottom-left, bottom-right
        
        init() {
            // Close dropdown on outside click
            document.addEventListener('click', (e) => {
                if (this.open && !this.$el.contains(e.target)) {
                    this.open = false;
                }
            });
            
            // Close dropdown on escape key
            window.addEventListener('keydown', (e) => {
                if (this.open && e.key === 'Escape') {
                    this.open = false;
                    e.preventDefault();
                }
            });
        },
        
        toggle() {
            this.open = !this.open;
            
            if (this.open) {
                this.$nextTick(() => {
                    // Focus the first focusable element in the dropdown
                    const focusable = this.$refs.panel.querySelectorAll('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
                    if (focusable.length > 0) {
                        focusable[0].focus();
                    }
                });
            }
        },
        
        positionClass() {
            const positions = {
                'top-left': 'bottom-full right-0 mb-2',
                'top-right': 'bottom-full left-0 mb-2',
                'bottom-left': 'top-full right-0 mt-2',
                'bottom-right': 'top-full left-0 mt-2'
            };
            return positions[this.position] || positions['bottom-right'];
        }
    }));
    
    // Tabs Component
    Alpine.data('tabs', (options = {}) => ({
        activeTab: options.defaultTab || null,
        tabs: [],
        
        init() {
            // Find tabs from DOM if not provided
            if (!this.tabs.length) {
                const tabElements = this.$el.querySelectorAll('[x-tab]');
                this.tabs = Array.from(tabElements).map(el => el.getAttribute('x-tab'));
            }
            
            // Set initial active tab
            if (!this.activeTab && this.tabs.length) {
                this.activeTab = this.tabs[0];
            }
            
            // Check URL hash for tab
            const hash = window.location.hash.substring(1);
            if (hash && this.tabs.includes(hash)) {
                this.activeTab = hash;
            }
            
            // Handle keyboard navigation
            this.$watch('activeTab', value => {
                // Update URL hash if needed
                if (options.updateHash) {
                    window.location.hash = value;
                }
                
                // Focus the active tab
                this.$nextTick(() => {
                    const activeTabElement = this.$el.querySelector(`[x-tab="${value}"]`);
                    if (activeTabElement) {
                        activeTabElement.focus();
                    }
                });
            });
        },
        
        setActiveTab(tab) {
            this.activeTab = tab;
        },
        
        handleKeyDown(event, currentIndex) {
            const lastIndex = this.tabs.length - 1;
            
            switch(event.key) {
                case 'ArrowRight':
                case 'ArrowDown':
                    this.setActiveTab(this.tabs[(currentIndex + 1) % this.tabs.length]);
                    event.preventDefault();
                    break;
                case 'ArrowLeft':
                case 'ArrowUp':
                    this.setActiveTab(this.tabs[currentIndex === 0 ? lastIndex : currentIndex - 1]);
                    event.preventDefault();
                    break;
                case 'Home':
                    this.setActiveTab(this.tabs[0]);
                    event.preventDefault();
                    break;
                case 'End':
                    this.setActiveTab(this.tabs[lastIndex]);
                    event.preventDefault();
                    break;
            }
        }
    }));
});
