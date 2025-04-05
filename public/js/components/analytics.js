/**
 * CarFuse Analytics Component
 * Privacy-focused user behavior tracking with GDPR compliance
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Define component name
    const COMPONENT_NAME = 'analytics';
    
    // Check if already initialized
    if (CarFuse[COMPONENT_NAME]) {
        console.warn(`CarFuse ${COMPONENT_NAME} component already initialized.`);
        return;
    }
    
    // Define the component
    const component = {
        // Configuration
        config: {
            endpoint: '/api/analytics',
            batchSize: 10,
            batchInterval: 30000, // 30 seconds
            samplingRate: 1.0, // 100% of events
            includePerformance: true,
            includeUserAgent: false, // Minimize personal data
            debug: false
        },
        
        // State
        state: {
            consentGiven: false,
            initialized: false,
            anonymousId: null,
            batchedEvents: [],
            batchTimer: null,
            pageViewTimestamp: null,
            performanceMetrics: {}
        },
        
        /**
         * Initialize Analytics functionalities
         * @param {Object} options - Configuration options
         */
        init: function(options = {}) {
            // Apply custom options
            Object.assign(this.config, options);
            
            this.log('Initializing Analytics component');
            this.initializeAnonymousId();
            this.checkStoredConsent();
            this.setupEventListeners();
            
            // Initialize performance tracking
            if (this.config.includePerformance) {
                this.initializePerformanceTracking();
            }
            
            // Track initial page view
            this.trackPageView();
            
            this.state.initialized = true;
            this.log('Analytics component initialized');
            
            return Promise.resolve();
        },
        
        /**
         * Log a message to the console if debug mode is enabled
         * @param {string} message - Message to log
         * @param {*} [data] - Optional data to include in log
         */
        log: function(message, data) {
            if (this.config.debug || CarFuse.config.debug) {
                console.log(`[CarFuse Analytics] ${message}`, data || '');
            }
        },
        
        /**
         * Initialize or retrieve anonymous ID
         */
        initializeAnonymousId: function() {
            let anonymousId = localStorage.getItem('cf_anonymous_id');

            if (!anonymousId) {
                // Generate a random UUID
                anonymousId = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
                    const r = Math.random() * 16 | 0;
                    const v = c === 'x' ? r : (r & 0x3 | 0x8);
                    return v.toString(16);
                });

                // Store it for future use
                localStorage.setItem('cf_anonymous_id', anonymousId);
            }

            this.state.anonymousId = anonymousId;
            this.log('Initialized anonymous ID', anonymousId);
        },

        /**
         * Check for stored consent
         */
        checkStoredConsent: function() {
            const consentStatus = localStorage.getItem('cf_analytics_consent');

            if (consentStatus === 'granted') {
                this.state.consentGiven = true;
                this.log('Consent previously granted');
            } else {
                this.state.consentGiven = false;
                this.log('No consent found or consent declined');
            }
        },

        /**
         * Set up core event listeners
         */
        setupEventListeners: function() {
            // Listen for page visibility changes to track time spent
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'hidden') {
                    this.trackTimeSpent();
                }
            });

            // Listen for form submissions
            document.addEventListener('submit', (event) => {
                const form = event.target;

                // Ignore if the form has opted out of tracking
                if (form.hasAttribute('data-no-track')) {
                    return;
                }

                const formId = form.id || form.getAttribute('name') || 'unknown-form';
                const formAction = form.action || window.location.href;

                this.trackEvent('form_submit', {
                    form_id: formId,
                    form_action: formAction
                });
            });

            // Track clicks on important elements
            document.addEventListener('click', (event) => {
                const target = event.target.closest('[data-track], a, button');

                if (!target || target.hasAttribute('data-no-track')) {
                    return;
                }

                let trackData = {};

                // Check for custom tracking data
                if (target.hasAttribute('data-track')) {
                    try {
                        const trackAttr = target.getAttribute('data-track');
                        if (trackAttr) {
                            // Handle both JSON and simple values
                            if (trackAttr.startsWith('{')) {
                                trackData = JSON.parse(trackAttr);
                            } else {
                                trackData.action = trackAttr;
                            }
                        }
                    } catch (e) {
                        this.log('Error parsing track data', e);
                    }
                }

                // Gather element data
                trackData.element_type = target.tagName.toLowerCase();
                trackData.element_id = target.id || '';
                trackData.element_class = Array.from(target.classList).join(' ');
                trackData.element_text = target.innerText?.substring(0, 100).trim() || '';

                // For links, track URL
                if (target.tagName === 'A') {
                    trackData.href = target.href;
                }

                this.trackEvent('click', trackData);
            });

            // Track authentication events
            document.addEventListener('auth:stateChanged', (event) => {
                const isAuthenticated = event.detail?.authenticated;

                if (isAuthenticated) {
                    this.trackEvent('user_login', {
                        method: 'session_check'
                    });
                }
            });

            // Track successful login
            window.addEventListener('auth:login', (event) => {
                const userData = window.AuthHelper?.getUserData() || {};

                this.trackEvent('user_login', {
                    method: event.detail?.method || 'direct',
                    user_type: userData.role || 'user'
                });
            });

            // Track logout
            window.addEventListener('auth:logout', () => {
                this.trackEvent('user_logout');
            });

            // Track HTMX events for page transitions
            document.body.addEventListener('htmx:afterOnLoad', (event) => {
                const isFullPageRequest = event.detail.pathInfo?.finalPath || false;

                if (isFullPageRequest) {
                    this.trackPageView();
                }
            });

            // Track errors
            window.addEventListener('error', (event) => {
                this.trackError('js_error', {
                    message: event.message,
                    source: event.filename,
                    line: event.lineno,
                    column: event.colno
                });
            });

            // Track unhandled promise rejections
            window.addEventListener('unhandledrejection', (event) => {
                this.trackError('promise_rejection', {
                    message: event.reason?.message || 'Unknown promise rejection'
                });
            });
        },

        /**
         * Initialize performance tracking
         */
        initializePerformanceTracking: function() {
            if (!window.performance || !window.performance.getEntriesByType) {
                this.log('Performance API not supported');
                return;
            }

            // Track navigation timing
            window.addEventListener('load', () => {
                setTimeout(() => {
                    const navigationTiming = performance.getEntriesByType('navigation')[0];
                    const paintTiming = performance.getEntriesByType('paint');

                    if (navigationTiming) {
                        const metrics = {
                            page_load_time: navigationTiming.loadEventEnd - navigationTiming.startTime,
                            dom_complete: navigationTiming.domComplete - navigationTiming.startTime,
                            dom_interactive: navigationTiming.domInteractive - navigationTiming.startTime,
                            first_byte: navigationTiming.responseStart - navigationTiming.requestStart
                        };

                        // Add paint metrics if available
                        paintTiming.forEach(paint => {
                            if (paint.name === 'first-paint') {
                                metrics.first_paint = paint.startTime;
                            } else if (paint.name === 'first-contentful-paint') {
                                metrics.first_contentful_paint = paint.startTime;
                            }
                        });

                        this.state.performanceMetrics = metrics;
                        this.trackEvent('performance', metrics);
                    }
                }, 0);
            });
        },

        /**
         * Request user consent for analytics tracking
         * @param {Function} [callback] - Callback function to run after consent decision
         */
        requestConsent: function(callback) {
            // Check if we already have consent
            if (this.state.consentGiven) {
                if (typeof callback === 'function') callback(true);
                return;
            }

            // Check if we have a notification system to show consent prompt
            if (CarFuse.notifications) {
                // Show consent prompt using notifications component
                const confirmationMessage = 'Czy zgadzasz się na gromadzenie anonimowych danych o korzystaniu z serwisu w celu poprawy jego funkcjonalności?';

                CarFuse.notifications.showConfirmation(
                    confirmationMessage,
                    () => {
                        this.setConsent(true);
                        if (typeof callback === 'function') callback(true);
                    },
                    () => {
                        this.setConsent(false);
                        if (typeof callback === 'function') callback(false);
                    }
                );
            } else {
                // Fallback to browser confirm
                const consentGiven = confirm('Czy zgadzasz się na gromadzenie anonimowych danych o korzystaniu z serwisu w celu poprawy jego funkcjonalności?');
                this.setConsent(consentGiven);
                if (typeof callback === 'function') callback(consentGiven);
            }
        },

        /**
         * Set consent status
         * @param {boolean} consentGiven - Whether consent was given
         */
        setConsent: function(consentGiven) {
            this.state.consentGiven = consentGiven;

            // Store consent preference
            localStorage.setItem('cf_analytics_consent', consentGiven ? 'granted' : 'denied');

            this.log(`Consent ${consentGiven ? 'granted' : 'denied'}`);

            // If consent was granted, send any batched events
            if (consentGiven && this.state.batchedEvents.length > 0) {
                this.sendBatch();
            }
        },

        /**
         * Track page view
         * @param {string} [url] - URL to track (defaults to current URL)
         * @param {string} [title] - Page title (defaults to document.title)
         */
        trackPageView: function(url, title) {
            const currentUrl = url || window.location.pathname + window.location.search;
            const pageTitle = title || document.title;

            // Store page view timestamp for measuring time spent
            this.state.pageViewTimestamp = Date.now();

            this.trackEvent('page_view', {
                url: currentUrl,
                title: pageTitle,
                referrer: document.referrer || null
            });
        },

        /**
         * Track time spent on current page
         */
        trackTimeSpent: function() {
            if (!this.state.pageViewTimestamp) return;

            const timeSpent = Date.now() - this.state.pageViewTimestamp;

            // Only track if more than 1 second spent on page
            if (timeSpent > 1000) {
                this.trackEvent('time_spent', {
                    url: window.location.pathname + window.location.search,
                    seconds: Math.floor(timeSpent / 1000)
                });
            }
        },

        /**
         * Track user registration
         * @param {Object} data - Registration data
         */
        trackRegistration: function(data = {}) {
            this.trackEvent('user_registration', {
                method: data.method || 'direct',
                user_type: data.user_type || 'user'
            });
        },

        /**
         * Track booking events
         * @param {string} action - Booking action (started, completed, updated, canceled)
         * @param {Object} data - Booking data
         */
        trackBooking: function(action, data = {}) {
            this.trackEvent('booking_' + action, data);
        },

        /**
         * Track feature usage
         * @param {string} feature - Feature name
         * @param {Object} data - Feature usage data
         */
        trackFeatureUsage: function(feature, data = {}) {
            this.trackEvent('feature_used', {
                feature_name: feature,
                ...data
            });
        },

        /**
         * Track user preference changes
         * @param {string} preference - Preference name
         * @param {*} newValue - New preference value
         */
        trackPreferenceChange: function(preference, newValue) {
            this.trackEvent('preference_change', {
                preference_name: preference,
                // Don't track the actual value for privacy, just note it was changed
                changed: true
            });
        },

        /**
         * Track errors
         * @param {string} type - Error type
         * @param {Object} data - Error data
         */
        trackError: function(type, data = {}) {
            this.trackEvent('error', {
                error_type: type,
                ...data
            });
        },

        /**
         * Track general event
         * @param {string} eventName - Event name
         * @param {Object} eventData - Event data
         */
        trackEvent: function(eventName, eventData = {}) {
            // Apply sampling rate
            if (Math.random() > this.config.samplingRate) {
                return;
            }

            // Create event object
            const event = {
                event: eventName,
                timestamp: new Date().toISOString(),
                anonymousId: this.state.anonymousId,
                url: window.location.pathname + window.location.search,
                data: eventData
            };

            // Add authenticated user ID if available
            if (window.AuthHelper && window.AuthHelper.isAuthenticated()) {
                const userData = window.AuthHelper.getUserData();
                if (userData && userData.id) {
                    event.userId = userData.id;
                }
            }

            // Add user agent if configured
            if (this.config.includeUserAgent) {
                event.userAgent = navigator.userAgent;
            }

            this.log(`Tracking event: ${eventName}`, eventData);

            // Add to batch or send immediately
            this.addToBatch(event);
        },

        /**
         * Add event to batch
         * @param {Object} event - Event to add to batch
         */
        addToBatch: function(event) {
            this.state.batchedEvents.push(event);

            // Send batch if reached batch size
            if (this.state.batchedEvents.length >= this.config.batchSize) {
                this.sendBatch();
            } else if (!this.state.batchTimer) {
                // Start batch timer if not already running
                this.state.batchTimer = setTimeout(() => {
                    this.sendBatch();
                }, this.config.batchInterval);
            }
        },

        /**
         * Send batched events
         */
        sendBatch: function() {
            // Clear batch timer
            if (this.state.batchTimer) {
                clearTimeout(this.state.batchTimer);
                this.state.batchTimer = null;
            }

            // Skip if no events or no consent
            if (this.state.batchedEvents.length === 0 || !this.state.consentGiven) {
                return;
            }

            const events = [...this.state.batchedEvents];
            this.state.batchedEvents = [];

            this.log(`Sending batch of ${events.length} events`);

            // Send events to server
            fetch(this.config.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ events }),
                // Use keepalive to ensure the request completes even if the page is unloading
                keepalive: true
            }).catch(error => {
                this.log('Error sending analytics batch', error);
            });
        },

        /**
         * Send event immediately, bypassing batch
         * @param {string} eventName - Event name
         * @param {Object} eventData - Event data
         */
        sendImmediately: function(eventName, eventData = {}) {
            // Create event object
            const event = {
                event: eventName,
                timestamp: new Date().toISOString(),
                anonymousId: this.state.anonymousId,
                url: window.location.pathname + window.location.search,
                data: eventData
            };

            // Skip if no consent
            if (!this.state.consentGiven) {
                this.log(`Skipping immediate event (no consent): ${eventName}`);
                return;
            }

            this.log(`Sending immediate event: ${eventName}`, eventData);

            // Send event to server
            fetch(this.config.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ events: [event] }),
                keepalive: true
            }).catch(error => {
                this.log('Error sending immediate event', error);
            });
        },

        /**
         * Opt out of all tracking
         */
        optOut: function() {
            this.setConsent(false);
            this.state.batchedEvents = [];

            if (this.state.batchTimer) {
                clearTimeout(this.state.batchTimer);
                this.state.batchTimer = null;
            }

            this.log('User opted out of all tracking');
        }
    };
    
    // Register the component
    CarFuse[COMPONENT_NAME] = component;
    
    // Register with CarFuse if available
    if (CarFuse.registerComponent) {
        CarFuse.registerComponent(COMPONENT_NAME, component);
    }
    
    // Initialize Analytics component with default settings
    CarFuse.analytics.init();

    // Integration with other components
    document.addEventListener('carfuse:ready', () => {
        // Track initial page view
        CarFuse.analytics.trackPageView();
    });

    document.addEventListener('carfuse:auth-initialized', () => {
        // Request consent after auth is ready
        CarFuse.analytics.requestConsent();
    });

    // Track form submissions
    document.addEventListener('carfuse:form-submit', (event) => {
        const form = event.detail.target;
        CarFuse.analytics.trackEvent('form_submit', {
            form_id: form.id || form.name || 'unknown',
            form_action: form.action
        });
    });

    // Track errors
    document.addEventListener('carfuse:error', (event) => {
        CarFuse.analytics.trackError(event.detail.type, event.detail.data);
    });
})();
