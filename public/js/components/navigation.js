/**
 * CarFuse Navigation Component
 * Manages navigation-related functionalities, including mobile menu, active link highlighting,
 * scroll-based effects, breadcrumbs, and deep linking.
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Define component name
    const COMPONENT_NAME = 'navigation';
    
    // Check if already initialized
    if (CarFuse[COMPONENT_NAME]) {
        console.warn(`CarFuse ${COMPONENT_NAME} component already initialized.`);
        return;
    }
    
    // Define the component
    const component = {
        // Configuration
        config: {
            debug: false,
            mobileMenuBreakpoint: 768, // Example breakpoint for mobile menu
            scrollThreshold: 100, // Example scroll threshold for effects
            activeClass: 'active', // Class to apply to active links
            scrollEffectClass: 'shadow-md bg-white', // Class to apply on scroll
            breadcrumbContainer: '#breadcrumbs', // Selector for breadcrumb container
            deepLinkingEnabled: true
        },
        
        // State
        state: {
            initialized: false,
            mobileMenuOpen: false,
            scrolled: false
        },
        
        /**
         * Initialize Navigation functionalities
         * @param {Object} options - Configuration options
         */
        init: function(options = {}) {
            // Apply custom options
            Object.assign(this.config, options);
            
            this.log('Initializing Navigation component');
            
            // Setup all navigation features
            this.setupMobileMenu();
            this.setupActiveLinkHighlighting();
            this.setupScrollBasedEffects();
            this.setupBreadcrumbs();
            this.setupDeepLinking();
            
            this.state.initialized = true;
            this.log('Navigation component initialized');
            
            return Promise.resolve();
        },
        
        /**
         * Log a message to the console if debug mode is enabled
         * @param {string} message - Message to log
         * @param {*} [data] - Optional data to include in log
         */
        log: function(message, data) {
            if (this.config.debug || CarFuse.config.debug) {
                console.log(`[CarFuse Navigation] ${message}`, data || '');
            }
        },
        
        /**
         * Setup mobile menu toggle
         */
        setupMobileMenu: function() {
            this.log('Setting up mobile menu');
            
            const menuButton = document.querySelector('[data-mobile-menu-toggle]');
            const menu = document.querySelector('[data-mobile-menu]');
            
            if (menuButton && menu) {
                menuButton.addEventListener('click', () => {
                    this.state.mobileMenuOpen = !this.state.mobileMenuOpen;
                    menu.classList.toggle('hidden');
                    
                    if (this.config.debug) {
                        console.log('Mobile menu toggled', this.state.mobileMenuOpen);
                    }
                });
            } else {
                console.warn('Mobile menu toggle or menu element not found.');
            }
        },
        
        /**
         * Highlight active navigation links
         */
        setupActiveLinkHighlighting: function() {
            this.log('Setting up active link highlighting');
            
            const links = document.querySelectorAll('nav a');
            
            links.forEach(link => {
                if (link.href === window.location.href) {
                    link.classList.add(this.config.activeClass);
                    
                    if (this.config.debug) {
                        console.log('Highlighted active link', link);
                    }
                }
            });
        },
        
        /**
         * Setup scroll-based header effects
         */
        setupScrollBasedEffects: function() {
            this.log('Setting up scroll-based effects');
            
            window.addEventListener('scroll', () => {
                if (window.scrollY > this.config.scrollThreshold && !this.state.scrolled) {
                    document.querySelector('header')?.classList.add(this.config.scrollEffectClass);
                    this.state.scrolled = true;
                    
                    if (this.config.debug) {
                        console.log('Added scroll effect class to header');
                    }
                } else if (window.scrollY <= this.config.scrollThreshold && this.state.scrolled) {
                    document.querySelector('header')?.classList.remove(this.config.scrollEffectClass);
                    this.state.scrolled = false;
                    
                    if (this.config.debug) {
                        console.log('Removed scroll effect class from header');
                    }
                }
            });
        },
        
        /**
         * Setup breadcrumbs
         */
        setupBreadcrumbs: function() {
            this.log('Setting up breadcrumbs');
            
            const container = document.querySelector(this.config.breadcrumbContainer);
            if (container) {
                // Example: Generate breadcrumbs based on URL segments
                const segments = window.location.pathname.split('/').filter(Boolean);
                let breadcrumbHtml = '<a href="/">Home</a>';
                let currentPath = '';
                
                segments.forEach(segment => {
                    currentPath += `/${segment}`;
                    breadcrumbHtml += ` > <a href="${currentPath}">${segment}</a>`;
                });
                
                container.innerHTML = breadcrumbHtml;
                
                if (this.config.debug) {
                    console.log('Generated breadcrumbs', breadcrumbHtml);
                }
            } else {
                console.warn('Breadcrumb container not found.');
            }
        },
        
        /**
         * Setup deep linking (scroll to anchor)
         */
        setupDeepLinking: function() {
            this.log('Setting up deep linking');
            
            if (this.config.deepLinkingEnabled && window.location.hash) {
                const targetId = window.location.hash.substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                    
                    if (this.config.debug) {
                        console.log('Scrolled to anchor', targetId);
                    }
                } else {
                    console.warn('Anchor target not found', targetId);
                }
            }
        }
    };
    
    // Register the component
    CarFuse[COMPONENT_NAME] = component;
    
    // Register with CarFuse if available
    if (CarFuse.registerComponent) {
        CarFuse.registerComponent(COMPONENT_NAME, component);
    }
})();
