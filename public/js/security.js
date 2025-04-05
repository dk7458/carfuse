/**
 * CarFuse Security Module
 * Consolidated client-side security utilities
 */

// Self-executing function for encapsulation
(function() {
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Main security object to expose publicly
    const CarFuseSecurity = {
        /**
         * Get the CSRF token
         * @returns {string} The CSRF token
         */
        getCsrfToken() {
            return csrfToken;
        },
        
        /**
         * Add CSRF token to form
         * @param {HTMLFormElement} form - Form to add CSRF token to
         */
        addCsrfToForm(form) {
            if (!csrfToken) {
                if (CarFuse.config.debug) {
                    console.error('CSRF token not found in page meta tags');
                }
                return;
            }
            
            // Check if form already has token
            let tokenInput = form.querySelector('input[name="_token"]');
            
            if (!tokenInput) {
                // Create and add token input
                tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = '_token';
                tokenInput.value = csrfToken;
                form.appendChild(tokenInput);
            } else {
                // Update existing token
                tokenInput.value = csrfToken;
            }
        },
        
        /**
         * Create fetch request with CSRF token and auth token if available
         * @param {string} url - URL to fetch
         * @param {Object} options - Fetch options
         * @returns {Promise} Fetch promise
         */
        fetch(url, options = {}) {
            if (!csrfToken) {
                if (CarFuse.config.debug) {
                    console.warn('CSRF token not found in page meta tags');
                }
            }
            
            // Build headers with CSRF token
            const headers = options.headers || {};
            headers['X-CSRF-Token'] = csrfToken;
            headers['X-Requested-With'] = 'XMLHttpRequest';
            
            // Add Authorization header if authenticated via AuthHelper
            if (window.AuthHelper && window.AuthHelper.isAuthenticated()) {
                const authToken = window.AuthHelper.getToken();
                if (authToken) {
                    headers['Authorization'] = `Bearer ${authToken}`;
                }
            }
            
            if (!headers['Content-Type'] && !options.body?.toString().includes('FormData')) {
                headers['Content-Type'] = 'application/json';
            }
            
            return fetch(url, {
                ...options,
                headers
            })
            .then(response => {
                // Check for authentication issues
                if (response.status === 401) {
                    this._handleAuthError(response);
                }
                return response;
            })
            .catch(error => {
                if (CarFuse.config.debug) {
                    console.error('Fetch error:', error);
                }
                throw error;
            });
        },
        
        /**
         * Submit a form with security headers (CSRF + Auth)
         * @param {HTMLFormElement} form - Form to submit
         * @param {boolean} ajax - Whether to submit via AJAX or traditional form submission
         * @returns {Promise|void} Promise if ajax=true, otherwise void
         */
        submitForm(form, ajax = true) {
            // Add CSRF token
            this.addCsrfToForm(form);
            
            // Add JWT token if authenticated
            if (window.AuthHelper && window.AuthHelper.isAuthenticated()) {
                const authToken = window.AuthHelper.getToken();
                if (authToken) {
                    const tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = 'auth_token';
                    tokenInput.value = authToken;
                    form.appendChild(tokenInput);
                }
            }
            
            // Sanitize form inputs to prevent XSS (except file inputs)
            form.querySelectorAll('input:not([type="file"]), textarea').forEach(input => {
                if (input.value) {
                    // Basic sanitization - encode HTML entities
                    input.value = this._sanitizeInput(input.value);
                }
            });
            
            // Submit via AJAX if requested
            if (ajax) {
                const formData = new FormData(form);
                const url = form.action;
                const method = form.method.toUpperCase() || 'POST';
                
                // Check if form contains file inputs
                const hasFiles = Array.from(form.elements).some(el => 
                    el.type === 'file' && el.files && el.files.length > 0
                );
                
                // Set appropriate headers based on content type
                const headers = {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                };
                
                // Only set Content-Type for non-file submissions
                // (browser will set multipart/form-data with boundary for file uploads)
                if (!hasFiles) {
                    headers['Content-Type'] = 'application/json';
                }
                
                // Add Authorization header if authenticated
                if (window.AuthHelper && window.AuthHelper.isAuthenticated()) {
                    const authToken = window.AuthHelper.getToken();
                    if (authToken) {
                        headers['Authorization'] = `Bearer ${authToken}`;
                    }
                }
                
                return fetch(url, {
                    method: method,
                    headers: headers,
                    body: hasFiles ? formData : JSON.stringify(Object.fromEntries(formData))
                })
                .then(response => {
                    // Check for authentication issues
                    if (response.status === 401) {
                        this._handleAuthError(response);
                    }
                    return response;
                })
                .catch(error => {
                    if (CarFuse.config.debug) {
                        console.error('Fetch error:', error);
                    }
                    throw error;
                });
            } else {
                // Traditional form submission
                form.submit();
            }
        },
        
        /**
         * Verify if user is authenticated (using AuthHelper if available)
         * @returns {boolean} True if user has session data or JWT token
         */
        isAuthenticated() {
            // Prefer JWT token authentication from AuthHelper
            if (window.AuthHelper) {
                return window.AuthHelper.isAuthenticated();
            }
            
            // Fall back to session-based authentication check
            return !!document.body.getAttribute('data-authenticated');
        },
        
        /**
         * Check if user has a specific role (using AuthHelper if available)
         * @param {string|string[]} roles - Role(s) to check
         * @returns {boolean} True if user has one of the roles
         */
        hasRole(roles) {
            // Prefer role check from AuthHelper
            if (window.AuthHelper && window.AuthHelper.isAuthenticated()) {
                const userRole = window.AuthHelper.getUserRole();
                
                if (Array.isArray(roles)) {
                    return roles.includes(userRole);
                }
                return roles === userRole;
            }
            
            // Fall back to data attribute role check
            const userRole = document.body.getAttribute('data-role');
            
            if (!userRole) return false;
            
            if (Array.isArray(roles)) {
                return roles.includes(userRole);
            }
            
            return roles === userRole;
        },
        
        /**
         * Show unauthorized access error
         * @param {string} message - Error message
         */
        showUnauthorizedError(message = 'Nie masz uprawnień do wykonania tej operacji') {
            if (window.dispatchEvent && typeof CustomEvent === 'function') {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: {
                        title: 'Brak uprawnień',
                        message,
                        type: 'error'
                    }
                }));
            } else {
                alert(`Błąd: ${message}`);
            }
        },
        
        /**
         * Handle session expiration, coordinating with AuthHelper
         */
        handleSessionExpired() {
            // First handle auth token expiration with AuthHelper if available
            if (window.AuthHelper) {
                // Try to refresh the token first
                window.AuthHelper.refreshToken()
                    .then(() => {
                        if (CarFuse.config.debug) {
                            console.debug('Token refreshed successfully after session expiration');
                        }
                        // Optionally reload page to restore state
                        window.location.reload();
                    })
                    .catch(() => {
                        // If refresh fails, proceed with logout and message
                        this._showSessionExpiredMessage();
                        
                        // Clear session
                        window.AuthHelper.logout()
                            .finally(() => {
                                // Redirect after a short delay
                                setTimeout(() => {
                                    window.location.href = '/auth/login?redirect=' + encodeURIComponent(window.location.pathname);
                                }, 3000);
                            });
                    });
            } else {
                // Traditional session expiration handling
                this._showSessionExpiredMessage();
                
                // Redirect to login after showing message
                setTimeout(() => {
                    window.location.href = '/auth/login?redirect=' + encodeURIComponent(window.location.pathname);
                }, 3000);
            }
        },
        
        /**
         * Start session timeout monitoring, coordinating with token refresh
         * @param {number} timeoutMinutes - Session timeout in minutes
         */
        startSessionMonitor(timeoutMinutes = 30) {
            if (!this.isAuthenticated()) return;
            
            let lastActivity = Date.now();
            const timeoutMs = timeoutMinutes * 60 * 1000;
            let warningShown = false;
            
            // Update last activity time on user interaction
            const updateActivity = () => {
                lastActivity = Date.now();
                warningShown = false;
                
                // If using AuthHelper, let it handle token refresh
                if (window.AuthHelper && window.AuthHelper.isAuthenticated()) {
                    // AuthHelper handles token refresh internally
                    return;
                }
                
                // Otherwise, send session heartbeat
                if (this._lastHeartbeat === undefined || Date.now() - this._lastHeartbeat > 5 * 60 * 1000) {
                    this._lastHeartbeat = Date.now();
                    this.fetch('/api/auth/heartbeat', { method: 'POST' })
                        .catch(err => {
                            if (CarFuse.config.debug) {
                                console.error('Session heartbeat failed:', err);
                            }
                        });
                }
            };
            
            // Activity events to monitor
            ['click', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
                document.addEventListener(event, updateActivity, { passive: true });
            });
            
            // Check session every minute
            setInterval(() => {
                const inactiveTime = Date.now() - lastActivity;
                
                // Show warning 2 minutes before timeout
                if (inactiveTime > (timeoutMs - 2 * 60 * 1000) && !warningShown) {
                    warningShown = true;
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: {
                            title: 'Ostrzeżenie o sesji',
                            message: 'Twoja sesja wygaśnie za 2 minuty z powodu braku aktywności',
                            type: 'warning'
                        }
                    }));
                }
                
                // Handle session timeout
                if (inactiveTime > timeoutMs) {
                    this.handleSessionExpired();
                }
            }, 60000); // Check every minute
            
            // First activity update
            updateActivity();
        },
        
        /**
         * Private method: Handle authentication errors
         * @private
         * @param {Response} response - Fetch response object
         */
        _handleAuthError(response) {
            if (window.AuthHelper) {
                // Try to refresh the token first
                window.AuthHelper.refreshToken()
                    .catch(() => {
                        // If refresh fails, handle as session expired
                        this.handleSessionExpired();
                    });
            } else {
                this.handleSessionExpired();
            }
        },
        
        /**
         * Private method: Show session expired message
         * @private
         */
        _showSessionExpiredMessage() {
            if (window.dispatchEvent && typeof CustomEvent === 'function') {
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: {
                        title: 'Sesja wygasła',
                        message: 'Twoja sesja wygasła. Za chwilę nastąpi przekierowanie do strony logowania.',
                        type: 'warning'
                    }
                }));
            } else {
                alert('Twoja sesja wygasła. Za chwilę nastąpi przekierowanie do strony logowania.');
            }
        },
        
        /**
         * Private method: Sanitize user input to prevent XSS
         * @private
         * @param {string} input - User input to sanitize
         * @returns {string} Sanitized input
         */
        _sanitizeInput(input) {
            if (!input) return '';
            
            // Basic sanitization - encode HTML entities
            return String(input)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },
        
        /**
         * Protect against common frontend attacks
         * @param {Window} [win=window] - Window object for testing
         */
        protectWindow(win = window) {
            // Prevent clickjacking
            if (win.top !== win.self) {
                win.top.location = win.self.location;
            }
            
            // Set security headers using CSP meta tag if not already present
            if (!document.querySelector('meta[http-equiv="Content-Security-Policy"]')) {
                const meta = document.createElement('meta');
                meta.setAttribute('http-equiv', 'Content-Security-Policy');
                meta.setAttribute('content', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https://api.carfuse.pl;");
                document.head.appendChild(meta);
            }
            
            // Protect localStorage and sessionStorage against XSS
            const secureStorage = (storage) => {
                const originalSetItem = storage.setItem;
                storage.setItem = function(key, value) {
                    if (typeof value === 'string') {
                        // Sanitize stored values to prevent stored XSS
                        value = CarFuseSecurity._sanitizeInput(value);
                    }
                    originalSetItem.call(this, key, value);
                };
            };
            
            try {
                secureStorage(win.localStorage);
                secureStorage(win.sessionStorage);
            } catch (e) {
                if (CarFuse.config.debug) {
                    console.warn('Could not secure storage:', e);
                }
            }
            
            // Set secure and HTTPOnly cookie attributes where possible
            document.cookie = "cookieSecurityTest=1; SameSite=Strict; Secure; Path=/";
        },
        
        /**
         * Check for security vulnerabilities in the page
         * @returns {Array} Array of security issues found
         */
        securityCheck() {
            const issues = [];
            
            // Check for CSP
            if (!document.querySelector('meta[http-equiv="Content-Security-Policy"]')) {
                issues.push('No Content Security Policy found');
            }
            
            // Check for safe-auth in forms
            document.querySelectorAll('form').forEach(form => {
                if (!form.querySelector('input[name="_token"]')) {
                    issues.push(`Form missing CSRF token: ${form.id || form.action || 'unnamed form'}`);
                }
            });
            
            // Check for secure cookies
            if (!document.cookie.includes('SameSite=Strict') && !document.cookie.includes('SameSite=Lax')) {
                issues.push('Cookies missing SameSite attribute');
            }
            
            // If using AuthHelper, ensure it's properly loaded
            if (window.AuthHelper === undefined && document.body.hasAttribute('data-requires-auth')) {
                issues.push('AuthHelper not loaded on protected page');
            }
            
            return issues;
        }
    };
    
    // Listen for auth events from AuthHelper
    document.addEventListener('auth:stateChanged', function(event) {
        // If user logged out, do security cleanup
        if (!event.detail?.authenticated && window.AuthHelper && !window.AuthHelper.isAuthenticated()) {
            // Clear any sensitive data from storage
            try {
                localStorage.removeItem('user_preferences');
                sessionStorage.removeItem('temp_data');
                // Clear any other application-specific storage
            } catch (e) {
                if (CarFuse.config.debug) {
                    console.warn('Error clearing storage:', e);
                }
            }
        }
    });
    
    // Listen for authentication failures to coordinate security response
    document.addEventListener('auth:error', function(event) {
        const errorType = event.detail?.type;
        
        if (errorType === 'token_expired') {
            CarFuseSecurity.handleSessionExpired();
        } else if (errorType === 'unauthorized') {
            CarFuseSecurity.showUnauthorizedError(event.detail?.message);
        }
    });
    
    // Handle 401 and 403 responses globally for both fetch and XHR
    window.addEventListener('htmx:responseError', event => {
        const status = event.detail.xhr.status;
        
        if (status === 401) {
            // Try AuthHelper first
            if (window.AuthHelper) {
                window.AuthHelper.refreshToken()
                    .catch(() => {
                        CarFuseSecurity.handleSessionExpired();
                    });
            } else {
                CarFuseSecurity.handleSessionExpired();
            }
        } else if (status === 403) {
            CarFuseSecurity.showUnauthorizedError('Nie masz uprawnień do wykonania tej operacji');
        }
    });
    
    // Expose to global scope
    window.CarFuseSecurity = CarFuseSecurity;
    
    // Set up when DOM is loaded
    document.addEventListener('DOMContentLoaded', () => {
        // Add CSRF token to all forms
        document.querySelectorAll('form').forEach(form => {
            CarFuseSecurity.addCsrfToForm(form);
        });
        
        // Apply enhanced protection to window
        CarFuseSecurity.protectWindow();
        
        // Start session monitor 
        // Use either JWT expiry from AuthHelper or default 30 minutes
        let sessionTimeout = 30; // default 30 minutes
        
        if (window.AuthHelper && window.AuthHelper.isAuthenticated()) {
            try {
                const token = window.AuthHelper.getToken();
                if (token) {
                    const decoded = window.AuthHelper._parseJwt(token);
                    const expiresIn = decoded.exp - Math.floor(Date.now() / 1000);
                    // Convert to minutes and use as session timeout, with 5 minute buffer
                    sessionTimeout = Math.floor(expiresIn / 60) - 5;
                }
            } catch (e) {
                if (CarFuse.config.debug) {
                    console.warn('Error determining JWT expiration:', e);
                }
            }
        } else {
            // Get session timeout from meta tag or use default (30 minutes)
            sessionTimeout = parseInt(document.querySelector('meta[name="session-timeout"]')?.getAttribute('content') || '30');
        }
        
        // Only start session monitor if authenticated by any means
        if (CarFuseSecurity.isAuthenticated()) {
            // Ensure session timeout is reasonable (between 5 and 120 minutes)
            sessionTimeout = Math.max(5, Math.min(120, sessionTimeout));
            CarFuseSecurity.startSessionMonitor(sessionTimeout);
        }
        
        // Listen for dynamic form creation
        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    // Check if added node is a form or contains forms
                    if (node.tagName === 'FORM') {
                        CarFuseSecurity.addCsrfToForm(node);
                    } else if (node.querySelectorAll) {
                        node.querySelectorAll('form').forEach(form => {
                            CarFuseSecurity.addCsrfToForm(form);
                        });
                    }
                });
            });
        });
        
        // Start observing document body for added nodes
        observer.observe(document.body, { childList: true, subtree: true });
        
        // Run security check in development mode
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            const securityIssues = CarFuseSecurity.securityCheck();
            if (securityIssues.length > 0) {
                if (CarFuse.config.debug) {
                    console.warn('Security issues detected:', securityIssues);
                }
            }
        }
    });
})();
