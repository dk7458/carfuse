/**
 * CarFuse Theme Component
 * Manages theming and appearance settings
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Check if Theme is already initialized
    if (CarFuse.theme) {
        console.warn('CarFuse Theme component already initialized.');
        return;
    }
    
    // IMPORTANT: Prevent flash of incorrect theme by applying theme early
    try {
        const storedTheme = localStorage.getItem('carfuse_theme');
        if (storedTheme) {
            if (storedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else if (storedTheme === 'light') {
                document.documentElement.classList.add('light');
            } else if (storedTheme === 'high-contrast') {
                document.documentElement.classList.add('high-contrast');
            } else if (storedTheme === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
                if (prefersDark.matches) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.add('light');
                }
            }
        } else {
            // If no stored theme, check system preference
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
            if (prefersDark.matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.add('light');
            }
        }
    } catch (e) {
        // If error, default to light mode
        document.documentElement.classList.add('light');
    }
    
    // Define the component
    const themeComponent = {
        // Configuration
        config: {
            defaultTheme: 'system', // system, light, dark
            availableThemes: ['system', 'light', 'dark', 'high-contrast'],
            persistTheme: true,
            detectSystemPreference: true,
            transitionDuration: 300,
            transitionClass: 'transition-theme',
            debug: false,
            toggleSelector: '.theme-toggle',
            storageKey: 'carfuse_theme'
        },
        
        // State
        state: {
            initialized: false,
            currentTheme: null,
            systemPreference: null,
            toggleElements: []
        },
        
        /**
         * Initialize Theme functionalities
         * @param {Object} options - Configuration options
         */
        init: function(options = {}) {
            // Apply custom options
            Object.assign(this.config, options);
            
            this.log('Initializing Theme component');

            // Add transition class to document for smooth theme switching
            document.documentElement.classList.add(this.config.transitionClass);
            document.documentElement.style.setProperty('--theme-transition-duration', `${this.config.transitionDuration}ms`);
            
            // Detect system preference
            if (this.config.detectSystemPreference) {
                this.detectSystemPreference();
            }
            
            // Load theme from storage
            this.loadTheme();
            
            // Apply initial theme
            this.applyTheme(this.state.currentTheme);
            
            // Setup event listeners
            this.setupEventListeners();
            
            // Initialize toggle elements
            this.initializeToggles();
            
            // Set up storage event for cross-tab synchronization
            window.addEventListener('storage', (e) => {
                if (e.key === this.config.storageKey) {
                    const newTheme = e.newValue;
                    if (newTheme && newTheme !== this.state.currentTheme) {
                        this.state.currentTheme = newTheme;
                        this.applyTheme(newTheme);
                        this.updateToggleStates();
                    }
                }
            });
            
            this.state.initialized = true;
            this.log('Theme component initialized');
            
            // Dispatch initialization event
            document.dispatchEvent(new CustomEvent('carfuse:themeInitialized', {
                detail: { theme: this.getActiveTheme() }
            }));
        },
        
        /**
         * Log a message to the console if debug mode is enabled
         * @param {string} message - Message to log
         * @param {*} [data] - Optional data to include in log
         */
        log: function(message, data) {
            if (CarFuse.config?.debug || this.config.debug) {
                console.log(`[CarFuse Theme] ${message}`, data || '');
            }
        },
        
        /**
         * Detect system preference for dark mode
         */
        detectSystemPreference: function() {
            if (window.matchMedia) {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
                this.state.systemPreference = prefersDark.matches ? 'dark' : 'light';
                
                prefersDark.addEventListener('change', (e) => {
                    this.state.systemPreference = e.matches ? 'dark' : 'light';
                    
                    // Re-apply theme if set to system
                    if (this.state.currentTheme === 'system') {
                        this.applyTheme('system');
                    }
                });
                
                this.log(`System preference detected: ${this.state.systemPreference}`);
            } else {
                this.log('System preference detection not supported');
            }
        },
        
        /**
         * Load theme from storage
         */
        loadTheme: function() {
            if (this.config.persistTheme) {
                try {
                    const storedTheme = localStorage.getItem('carfuse_theme');
                    
                    if (storedTheme && this.config.availableThemes.includes(storedTheme)) {
                        this.state.currentTheme = storedTheme;
                        this.log(`Theme loaded from storage: ${this.state.currentTheme}`);
                        return;
                    }
                } catch (e) {
                    this.log('Error accessing localStorage', e);
                }
            }
            
            // Fallback to default theme
            this.state.currentTheme = this.config.defaultTheme;
            this.log(`Using default theme: ${this.state.currentTheme}`);
        },
        
        /**
         * Save theme to storage
         */
        saveTheme: function() {
            if (this.config.persistTheme) {
                try {
                    localStorage.setItem('carfuse_theme', this.state.currentTheme);
                    this.log(`Theme saved to storage: ${this.state.currentTheme}`);
                } catch (e) {
                    this.log('Error saving to localStorage', e);
                }
            }
        },
        
        /**
         * Apply the selected theme
         * @param {string} theme - Theme name (light, dark, system, high-contrast)
         */
        applyTheme: function(theme) {
            let themeToApply = theme;
            
            if (theme === 'system') {
                themeToApply = this.state.systemPreference || 'light';
                this.log(`Using system preference for theme: ${themeToApply}`);
            }
            
            // Remove existing theme classes
            document.documentElement.classList.remove('light', 'dark', 'high-contrast');
            
            // Add new theme class
            if (themeToApply === 'light') {
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
            } else if (themeToApply === 'dark') {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            } else if (themeToApply === 'high-contrast') {
                document.documentElement.classList.add('high-contrast');
            }
            
            // Update meta theme-color
            this.updateMetaThemeColor(themeToApply);
            
            // Update toggle states
            this.updateToggleStates();
            
            // Dispatch theme change event
            const event = new CustomEvent('carfuse:themeChanged', {
                detail: { 
                    theme: themeToApply,
                    selectedTheme: theme
                }
            });
            document.dispatchEvent(event);
            
            // Optimize rendering by using requestAnimationFrame
            requestAnimationFrame(() => {
                document.body.style.display = 'none';
                document.body.offsetHeight; // Force a reflow
                document.body.style.display = '';
            });
            
            this.log(`Theme applied: ${themeToApply}`);
        },
        
        /**
         * Update meta theme-color for browser UI
         * @param {string} theme - Current theme
         */
        updateMetaThemeColor: function(theme) {
            let metaThemeColor = document.querySelector('meta[name="theme-color"]');
            
            if (!metaThemeColor) {
                metaThemeColor = document.createElement('meta');
                metaThemeColor.setAttribute('name', 'theme-color');
                document.head.appendChild(metaThemeColor);
            }
            
            // Set appropriate color based on theme
            if (theme === 'dark') {
                metaThemeColor.setAttribute('content', '#121212'); // Dark background
            } else if (theme === 'high-contrast') {
                metaThemeColor.setAttribute('content', '#000000'); // Black background
            } else {
                metaThemeColor.setAttribute('content', '#ffffff'); // Light background
            }
        },
        
        /**
         * Initialize toggle elements with proper accessibility attributes
         */
        initializeToggles: function() {
            // Find all toggle elements
            this.state.toggleElements = document.querySelectorAll(this.config.toggleSelector);
            
            this.state.toggleElements.forEach(toggle => {
                // Set up accessibility attributes
                if (!toggle.getAttribute('role')) toggle.setAttribute('role', 'button');
                if (!toggle.getAttribute('tabindex')) toggle.setAttribute('tabindex', '0');
                if (!toggle.getAttribute('aria-label')) toggle.setAttribute('aria-label', 'Toggle theme');
                
                // Add theme indicator for screen readers
                let themeText = toggle.querySelector('.theme-toggle-text') || document.createElement('span');
                if (!toggle.querySelector('.theme-toggle-text')) {
                    themeText.classList.add('theme-toggle-text', 'sr-only');
                    toggle.appendChild(themeText);
                }
                
                // Set initial aria states
                this.updateToggleState(toggle);
            });
        },
        
        /**
         * Update all toggle states
         */
        updateToggleStates: function() {
            this.state.toggleElements.forEach(toggle => {
                this.updateToggleState(toggle);
            });
        },
        
        /**
         * Update single toggle state
         * @param {HTMLElement} toggle - Toggle element
         */
        updateToggleState: function(toggle) {
            const activeTheme = this.getActiveTheme();
            const themeText = toggle.querySelector('.theme-toggle-text');
            
            // Update aria attributes
            toggle.setAttribute('aria-pressed', activeTheme === 'dark');
            toggle.setAttribute('aria-live', 'polite');
            
            // Update theme text for screen readers
            if (themeText) {
                themeText.textContent = `Currently in ${activeTheme} mode. Click to switch to ${activeTheme === 'dark' ? 'light' : 'dark'} mode.`;
            }
            
            // Update toggle visuals
            const lightIcon = toggle.querySelector('.theme-toggle-light-icon');
            const darkIcon = toggle.querySelector('.theme-toggle-dark-icon');
            
            if (lightIcon && darkIcon) {
                if (activeTheme === 'dark') {
                    lightIcon.classList.add('hidden');
                    darkIcon.classList.remove('hidden');
                } else {
                    lightIcon.classList.remove('hidden');
                    darkIcon.classList.add('hidden');
                }
            }
        },
        
        /**
         * Set up event listeners for theme toggles
         */
        setupEventListeners: function() {
            this.log('Setting up theme event listeners');
            
            // Theme toggle button
            document.addEventListener('click', (e) => {
                const toggle = e.target.closest(this.config.toggleSelector);
                if (toggle) {
                    this.handleToggleClick(toggle);
                }
            });
            
            // Keyboard accessibility for toggles
            document.addEventListener('keydown', (e) => {
                const toggle = e.target.closest(this.config.toggleSelector);
                if (toggle && (e.key === 'Enter' || e.key === ' ')) {
                    e.preventDefault();
                    this.handleToggleClick(toggle);
                }
            });
            
            // Specific theme buttons
            document.querySelectorAll('[data-theme-value]').forEach(button => {
                button.addEventListener('click', () => {
                    const themeValue = button.dataset.themeValue;
                    if (this.config.availableThemes.includes(themeValue)) {
                        this.setTheme(themeValue);
                    }
                });
            });
            
            // Check for reduced motion preference
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            if (prefersReducedMotion.matches) {
                document.documentElement.classList.add('reduce-motion');
                this.log('Reduced motion preference detected');
            }
            
            prefersReducedMotion.addEventListener('change', (e) => {
                if (e.matches) {
                    document.documentElement.classList.add('reduce-motion');
                } else {
                    document.documentElement.classList.remove('reduce-motion');
                }
                this.log(`Reduced motion preference changed: ${e.matches}`);
            });
            
            // Listen for DOM changes to initialize new toggle elements
            const observer = new MutationObserver((mutations) => {
                let needsUpdate = false;
                
                mutations.forEach(mutation => {
                    if (mutation.addedNodes.length) {
                        for (let i = 0; i < mutation.addedNodes.length; i++) {
                            const node = mutation.addedNodes[i];
                            if (node.nodeType === 1 && (
                                node.matches?.(this.config.toggleSelector) || 
                                node.querySelectorAll?.(this.config.toggleSelector).length
                            )) {
                                needsUpdate = true;
                                break;
                            }
                        }
                    }
                });
                
                if (needsUpdate) {
                    this.initializeToggles();
                }
            });
            
            observer.observe(document.body, { childList: true, subtree: true });
        },
        
        /**
         * Handle toggle click event
         * @param {HTMLElement} toggle - Toggle element that was clicked
         */
        handleToggleClick: function(toggle) {
            const targetTheme = toggle.dataset.themeValue;
            
            if (targetTheme && this.config.availableThemes.includes(targetTheme)) {
                // If toggle has specific theme value
                this.setTheme(targetTheme);
            } else {
                // Toggle between light and dark
                const currentTheme = this.getActiveTheme();
                this.setTheme(currentTheme === 'dark' ? 'light' : 'dark');
            }
            
            // Add animation effect to toggle
            toggle.classList.add('theme-toggle-active');
            setTimeout(() => {
                toggle.classList.remove('theme-toggle-active');
            }, 300);
        },
        
        /**
         * Set the theme
         * @param {string} theme - Theme name (light, dark, system, high-contrast)
         */
        setTheme: function(theme) {
            if (!this.config.availableThemes.includes(theme)) {
                console.warn(`Theme ${theme} not available`);
                return;
            }
            
            this.state.currentTheme = theme;
            this.saveTheme();
            this.applyTheme(theme);
            this.log(`Theme set to: ${theme}`);
        },
        
        /**
         * Get current theme
         * @returns {string} Current theme name
         */
        getCurrentTheme: function() {
            return this.state.currentTheme;
        },
        
        /**
         * Get current active theme (resolves system preference)
         * @returns {string} Active theme name
         */
        getActiveTheme: function() {
            if (this.state.currentTheme === 'system') {
                return this.state.systemPreference || 'light';
            }
            return this.state.currentTheme;
        }
    };
    
    // Register the component
    CarFuse.theme = themeComponent;

    // Initialize the component if CarFuse is ready
    if (CarFuse.registerComponent) {
        CarFuse.registerComponent('theme', CarFuse.theme);
    } else {
        console.warn('CarFuse.registerComponent is not available. Make sure core.js is loaded first.');
    }
})();
