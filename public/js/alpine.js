/**
 * CarFuse Alpine.js Integration
 * Provides structured Alpine.js component loading and registration
 * with proper error boundaries and HTMX integration
 */

(function() {
    // Check if Alpine.js is available
    if (typeof Alpine === 'undefined') {
        console.error('Alpine.js is not loaded! Make sure to include Alpine.js before this script.');
        return;
    }

    // Register Alpine with window for global access
    window.Alpine = Alpine;
    
    // Component registry
    const componentRegistry = {
        core: {
            path: '/js/alpine-components/core.js',
            component: null
        },
        form: {
            path: '/js/alpine-components/form.js',
            component: null 
        },
        auth: {
            path: '/js/alpine-components/auth.js',
            component: null
        },
        ui: {
            path: '/js/alpine-components/ui.js',
            component: null
        },
        data: {
            path: '/js/alpine-components/data.js',
            component: null
        },
        accessibility: {
            path: '/js/alpine-components/accessibility.js',
            component: null
        }
    };
    
    // Track loaded components and initialization state
    const state = {
        initialized: false,
        authHelperAvailable: typeof window.AuthHelper !== 'undefined',
        htmxAvailable: typeof window.htmx !== 'undefined',
        htmxModularAvailable: typeof window.CarFuseHTMX !== 'undefined',
        loadedComponents: new Set(),
        errors: []
    };
    
    // CarFuse Alpine system
    const CarFuseAlpine = {
        /**
         * Log debug messages
         * @param {string} message - Message to log
         * @param {any} data - Optional data to log
         */
        log: function(message, data) {
            const debug = window.CarFuse?.config?.debug || false;
            if (debug) {
                console.log(`[CarFuse Alpine] ${message}`, data || '');
            }
        },
        
        /**
         * Initialize the Alpine system
         */
        init: function() {
            if (state.initialized) {
                this.log('Already initialized');
                return Promise.resolve();
            }
            
            this.log('Initializing Alpine.js integration');
            
            // Configure global Alpine settings
            this.configureAlpineGlobals();
            
            // Set up event listeners
            this.setupEventListeners();
            
            // Register components with main CarFuse system if available
            this.registerWithCarFuse();
            
            // Start Alpine if not already started
            if (!Alpine.initializedFlag) {
                Alpine.start();
                Alpine.initializedFlag = true;
            }
            
            state.initialized = true;
            this.log('Alpine.js integration initialized');
            return Promise.resolve();
        },
        
        /**
         * Configure global Alpine extensions and settings
         */
        configureAlpineGlobals: function() {
            this.log('Configuring Alpine.js globals');
            
            // Add validation messages as a global Alpine store
            Alpine.store('validationMessages', {
                required: 'To pole jest wymagane.',
                email: 'Proszę podać prawidłowy adres email.',
                min: 'Wartość musi zawierać co najmniej {min} znaków.',
                max: 'Wartość nie może przekraczać {max} znaków.',
                minValue: 'Wartość musi być większa lub równa {min}.',
                maxValue: 'Wartość musi być mniejsza lub równa {max}.',
                numeric: 'Proszę podać wartość liczbową.',
                integer: 'Proszę podać liczbę całkowitą.',
                phone: 'Proszę podać prawidłowy numer telefonu.',
                postalCode: 'Proszę podać prawidłowy kod pocztowy.',
                date: 'Proszę podać prawidłową datę.',
                futureDate: 'Data musi być w przyszłości.',
                pastDate: 'Data musi być w przeszłości.',
                passwordMatch: 'Hasła muszą być identyczne.',
                pesel: 'Podany numer PESEL jest nieprawidłowy.',
                nip: 'Podany numer NIP jest nieprawidłowy.',
                regon: 'Podany numer REGON jest nieprawidłowy.',
            });

            // Register global Alpine magic properties
            Alpine.magic('formatCurrency', () => {
                return (amount, currency = 'PLN') => {
                    return new Intl.NumberFormat('pl-PL', {
                        style: 'currency',
                        currency: currency
                    }).format(amount);
                };
            });

            Alpine.magic('formatDate', () => {
                return (date, options = {}) => {
                    const defaultOptions = { 
                        year: 'numeric', 
                        month: '2-digit', 
                        day: '2-digit' 
                    };
                    
                    return new Intl.DateTimeFormat('pl-PL', { ...defaultOptions, ...options }).format(
                        date instanceof Date ? date : new Date(date)
                    );
                };
            });
            
            // Magic helper for async operations with error handling
            Alpine.magic('asyncHandler', () => {
                return async (promise, successCallback, errorCallback) => {
                    try {
                        const result = await promise;
                        if (successCallback) successCallback(result);
                        return result;
                    } catch (error) {
                        if (errorCallback) errorCallback(error);
                        
                        // Default error handling - show toast notification
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: {
                                title: 'Błąd',
                                message: error.message || 'Wystąpił nieoczekiwany błąd.',
                                type: 'error'
                            }
                        }));
                        
                        throw error;
                    }
                };
            });
            
            // CSRF token helper
            Alpine.magic('csrf', () => {
                return () => window.AuthHelper.getCsrfToken() || '';
            });
            
            // Error boundary directive
            Alpine.directive('error-boundary', (el, {}, { evaluate, cleanup }) => {
                const handler = (event) => {
                    if (el.contains(event.target)) {
                        // Find closest error container
                        const errorContainer = el.querySelector('[x-error-container]') || 
                            document.createElement('div');
                        
                        if (!el.querySelector('[x-error-container]')) {
                            errorContainer.setAttribute('x-error-container', '');
                            errorContainer.className = 'p-4 bg-red-100 text-red-700 rounded mt-2';
                            el.appendChild(errorContainer);
                        }
                        
                        // Display error
                        errorContainer.textContent = `Error: ${event.error?.message || 'Component error'}`;
                        errorContainer.style.display = 'block';
                        
                        // Log error
                        console.error('[Alpine Error Boundary]', event.error);
                        event.stopPropagation();
                    }
                };
                
                el.addEventListener('error', handler);
                window.addEventListener('alpine:error', handler);
                
                cleanup(() => {
                    el.removeEventListener('error', handler);
                    window.removeEventListener('alpine:error', handler);
                });
            });
        },
        
        /**
         * Set up event listeners
         */
        setupEventListeners: function() {
            this.log('Setting up event listeners');
            
            // Modal helpers
            window.openModal = function(id, data = {}) {
                window.dispatchEvent(new CustomEvent('open-modal', { 
                    detail: { id, data } 
                }));
            };
            
            window.closeModal = function(id = null) {
                window.dispatchEvent(new CustomEvent('close-modal', { 
                    detail: { id } 
                }));
            };
            
            // HTMX integration
            document.addEventListener('htmx:afterSwap', (event) => {
                try {
                    // Initialize Alpine components in swapped content
                    if (Alpine.initTree) {
                        Alpine.initTree(event.detail.target);
                    }
                } catch (error) {
                    console.error('Error initializing Alpine after HTMX swap:', error);
                }
            });
            
            // Listen for CarFuse HTMX ready event
            document.addEventListener('carfuse:htmx-ready', () => {
                this.log('CarFuseHTMX is ready');
                // Refresh Alpine components that depend on HTMX state
                this.refreshAlpineComponents();
            });
            
            // Global toast notification handler
            window.addEventListener('show-toast', event => {
                const { title, message, type, duration } = event.detail;
                
                const toastSystemEl = document.querySelector('[x-data="toastSystem"]');
                
                if (toastSystemEl && toastSystemEl.__x) {
                    const toastSystem = toastSystemEl.__x.getUnobservedData();
                    toastSystem.showToast(title, message, type, duration || 5000);
                } else {
                    console.warn('Toast system not found, using console instead');
                    const method = type === 'error' ? 'error' : 
                                 type === 'warning' ? 'warn' : 'info';
                    console[method](`${title}: ${message}`);
                }
            });
            
            // AuthHelper integration
            document.addEventListener('auth:ready', () => {
                if (!state.authHelperAvailable && window.AuthHelper) {
                    this.log('AuthHelper became available after Alpine initialization');
                    state.authHelperAvailable = true;
                    
                    // Re-initialize auth components if needed
                    if (state.loadedComponents.has('auth')) {
                        window.dispatchEvent(new CustomEvent('auth:refresh'));
                    }
                }
            });
        },
        
        /**
         * Register components with main CarFuse system
         */
        registerWithCarFuse: function() {
            if (window.CarFuse && typeof window.CarFuse.registerComponent === 'function') {
                this.log('Registering Alpine components with CarFuse');
                
                // Register component loader
                if (window.CarFuse.registry.alpine && !window.CarFuse.registry.alpine.loader) {
                    window.CarFuse.registry.alpine.loader = () => this.init();
                }
                
                // Register individual Alpine components
                Object.entries(componentRegistry).forEach(([name, component]) => {
                    window.CarFuse.registerComponent(`alpine-${name}`, {
                        path: component.path,
                        dependencies: ['alpine'],
                        type: 'alpine-component',
                        priority: 6,
                        loader: () => this.loadComponent(name)
                    });
                });
            }
        },
        
        /**
         * Load an Alpine component
         * @param {string} name - Component name
         * @returns {Promise} Promise resolving when component is loaded
         */
        loadComponent: function(name) {
            if (state.loadedComponents.has(name)) {
                return Promise.resolve(componentRegistry[name].component);
            }
            
            const component = componentRegistry[name];
            if (!component) {
                return Promise.reject(new Error(`Alpine component not found: ${name}`));
            }
            
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = component.path;
                script.async = true;
                
                script.onload = () => {
                    state.loadedComponents.add(name);
                    this.log(`Loaded Alpine component: ${name}`);
                    resolve(componentRegistry[name].component);
                };
                
                script.onerror = () => {
                    const error = new Error(`Failed to load Alpine component: ${name}`);
                    state.errors.push(error);
                    reject(error);
                };
                
                document.head.appendChild(script);
            });
        },
        
        /**
         * Refresh Alpine components after state changes
         */
        refreshAlpineComponents: function() {
            document.querySelectorAll('[x-data]').forEach(el => {
                if (el.__x) {
                    try {
                        el.__x.updateElements(el);
                    } catch (e) {
                        console.warn('Failed to update Alpine element', e);
                    }
                }
            });
        },
        
        /**
         * Register a new Alpine component
         * @param {string} name - Component name
         * @param {Function} componentFn - Component factory function
         */
        registerComponent: function(name, componentFn) {
            if (!componentRegistry[name]) {
                componentRegistry[name] = {
                    path: null, // Dynamically registered
                    component: componentFn
                };
            } else {
                componentRegistry[name].component = componentFn;
            }
            
            // Register as Alpine data
            Alpine.data(name, componentFn);
            
            this.log(`Registered Alpine component: ${name}`);
        }
    };
    
    // Expose CarFuseAlpine to global scope
    window.CarFuseAlpine = CarFuseAlpine;
    
    // Initialize if not using main CarFuse orchestration
    if (!window.CarFuse || !window.CarFuse.registry.alpine) {
        // Wait for DOM to be fully loaded
        // REMOVE THIS AUTO INITIALIZATION
        // if (document.readyState === 'loading') {
        //     document.addEventListener('DOMContentLoaded', () => {
        //         CarFuseAlpine.init();
        //     });
        // } else {
        //     CarFuseAlpine.init();
        // }
    }
})();
