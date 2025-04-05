/**
 * Authentication Helper
 * 
 * Provides client-side authentication utilities that work with the server-side TokenService.
 * This helper is the single source of truth for authentication state and manages tokens,
 * user data, permissions, and auth-related events.
 */
class AuthHelper {
    constructor() {
        this.tokenRefreshPromise = null;
        this.tokenRefreshInterval = null;
        this.tokenName = 'jwt';
        this.refreshTokenName = 'refresh_token';
        this.refreshEndpoint = '/api/auth/refresh';
        this.loginEndpoint = '/api/auth/login';
        this.logoutEndpoint = '/api/auth/logout';
        this.userInfoEndpoint = '/api/user/profile';
        this.debugMode = false;
        
        // Define custom error types
        this.ErrorTypes = {
            TOKEN_EXPIRED: 'token_expired',
            TOKEN_INVALID: 'token_invalid',
            REFRESH_FAILED: 'refresh_failed',
            NETWORK_ERROR: 'network_error',
            UNAUTHORIZED: 'unauthorized',
            FORBIDDEN: 'forbidden',
            NOT_AUTHENTICATED: 'not_authenticated',
            PERMISSION_DENIED: 'permission_denied',
            INVALID_RESPONSE: 'invalid_response'
        };
        
        // Role hierarchy (higher index = more privileges)
        this.roleHierarchy = {
            'guest': 0,
            'user': 1,
            'moderator': 2,
            'admin': 3,
            'super_admin': 4
        };
        
        // Role-based redirect paths
        this.redirectPaths = {
            default: '/login',
            admin: '/admin/dashboard',
            user: '/dashboard',
            guest: '/login'
        };
        
        // Store authentication state in memory
        this._authState = {
            isAuthenticated: false,
            userInfo: null,
            permissions: [],
            loading: false
        };
        
        // Initialize HTMX extension if HTMX is available
        this._initHtmxExtension();
        
        // Start token refresh timer if a token exists
        if (this.isAuthenticated()) {
            this._startRefreshTimer();
        }

        // Listen for visibility change to verify session when tab becomes visible
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible' && this.isAuthenticated()) {
                this.verifySession(false)
                    .catch(() => {
                        this._debug('Session verification failed on visibility change');
                    });
            }
        });
    }
    
    /**
     * Enable or disable debug mode
     * @param {boolean} enabled - Whether to enable debug mode
     */
    setDebug(enabled) {
        this.debugMode = !!enabled;
    }
    
    /**
     * Log debug messages when debug mode is enabled
     * @param {string} message - The message to log
     * @param {object} [data] - Optional data to log
     */
    _debug(message, data = null) {
        if (this.debugMode) {
            console.debug(`[AuthHelper] ${message}`, data || '');
        }
    }
    
    /**
     * Get the JWT token from cookies
     * @returns {string|null} The JWT token or null if not found
     */
    getToken() {
        return this._getCookie(this.tokenName);
    }
    
    /**
     * Get the refresh token from cookies
     * @returns {string|null} The refresh token or null if not found
     */
    getRefreshToken() {
        return this._getCookie(this.refreshTokenName);
    }
    
    /**
     * Get authentication state to expose to components
     * @returns {object} Current authentication state
     */
    getAuthState() {
        return {
            isAuthenticated: this.isAuthenticated(),
            userId: this.getUserId(),
            username: this.getUsername(),
            userEmail: this.getUserEmail(),
            userRole: this.getUserRole(),
            userData: this.getUserData(),
            hasRole: (role) => this.hasRole(role),
            hasPermission: (permission) => this.hasPermission(permission),
            canAccess: (resource) => this.canAccess(resource)
        };
    }
    
    /**
     * Check if the user is authenticated (has a valid token)
     * @returns {boolean} True if authenticated, false otherwise
     */
    isAuthenticated() {
        const token = this.getToken();
        if (!token) return false;
        
        try {
            const decoded = this._parseJwt(token);
            // Check if token is expired
            const currentTime = Math.floor(Date.now() / 1000);
            return decoded.exp > currentTime;
        } catch (e) {
            this._debug('Error checking authentication status', e);
            return false;
        }
    }
    
    /**
     * Get user ID from token
     * @returns {string|null} User ID or null if not authenticated
     */
    getUserId() {
        try {
            const token = this.getToken();
            if (!token) return null;
            
            const decoded = this._parseJwt(token);
            return decoded.sub || null;
        } catch (e) {
            this._debug('Error getting user ID', e);
            return null;
        }
    }
    
    /**
     * Get user role from token
     * @returns {string|null} User role or null if not authenticated
     */
    getUserRole() {
        try {
            const token = this.getToken();
            if (!token) return null;
            
            const decoded = this._parseJwt(token);
            return decoded.data?.role || null;
        } catch (e) {
            this._debug('Error getting user role', e);
            return null;
        }
    }
    
    /**
     * Get username from token
     * @returns {string|null} Username or null if not authenticated
     */
    getUsername() {
        try {
            const token = this.getToken();
            if (!token) return null;
            
            const decoded = this._parseJwt(token);
            return decoded.data?.name || decoded.data?.email || null;
        } catch (e) {
            this._debug('Error getting username', e);
            return null;
        }
    }
    
    /**
     * Get user email from token
     * @returns {string|null} Email or null if not authenticated
     */
    getUserEmail() {
        try {
            const token = this.getToken();
            if (!token) return null;
            
            const decoded = this._parseJwt(token);
            return decoded.email || decoded.data?.email || null;
        } catch (e) {
            this._debug('Error getting user email', e);
            return null;
        }
    }
    
    /**
     * Get user data from token
     * @returns {object|null} User data or null if not authenticated
     */
    getUserData() {
        try {
            const token = this.getToken();
            if (!token) return null;
            
            const decoded = this._parseJwt(token);
            return decoded.data || null;
        } catch (e) {
            this._debug('Error getting user data', e);
            return null;
        }
    }
    
    /**
     * Check if current user has a specific role
     * @param {string|string[]} roles - Role(s) to check
     * @returns {boolean} True if user has any of the roles, false otherwise
     */
    hasRole(roles) {
        const userRole = this.getUserRole();
        if (!userRole) return false;
        
        if (Array.isArray(roles)) {
            return roles.includes(userRole);
        }
        return userRole === roles;
    }
    
    /**
     * Check if current user has at least the specified role level in the hierarchy
     * @param {string} minimumRole - Minimum required role
     * @returns {boolean} True if user has at least this role level
     */
    hasRoleLevel(minimumRole) {
        const userRole = this.getUserRole();
        if (!userRole || !this.roleHierarchy[userRole]) return false;
        
        const userRoleLevel = this.roleHierarchy[userRole] || 0;
        const requiredRoleLevel = this.roleHierarchy[minimumRole] || 0;
        
        return userRoleLevel >= requiredRoleLevel;
    }
    
    /**
     * Check if current user has specific permission
     * @param {string|string[]} permissions - Permission(s) to check
     * @returns {boolean} True if user has any of the permissions, false otherwise
     */
    hasPermission(permissions) {
        // If super_admin, always return true
        if (this.hasRole('super_admin')) return true;
        
        // Get permissions from user data
        const userData = this.getUserData();
        const userPermissions = userData?.permissions || [];
        
        if (Array.isArray(permissions)) {
            return permissions.some(permission => userPermissions.includes(permission));
        }
        
        return userPermissions.includes(permissions);
    }
    
    /**
     * Check if user can access a specific resource based on roles/permissions
     * @param {string} resource - Resource identifier to check access for
     * @returns {boolean} True if user can access the resource
     */
    canAccess(resource) {
        // Resource access mapping - extend as needed
        const resourceAccess = {
            'admin-dashboard': ['admin', 'super_admin'],
            'user-management': ['admin', 'super_admin'],
            'settings': ['user', 'admin', 'super_admin'],
            'profile': ['user', 'admin', 'super_admin'],
            'reports': ['admin', 'super_admin']
        };
        
        if (!resource || !resourceAccess[resource]) return false;
        
        return this.hasRole(resourceAccess[resource]);
    }
    
    /**
     * Get token expiration time in seconds
     * @returns {number|null} Seconds until token expiration or null if not authenticated
     */
    getTokenExpiresIn() {
        try {
            const token = this.getToken();
            if (!token) return null;
            
            const decoded = this._parseJwt(token);
            const currentTime = Math.floor(Date.now() / 1000);
            return decoded.exp - currentTime;
        } catch (e) {
            this._debug('Error getting token expiration', e);
            return null;
        }
    }
    
    /**
     * Initialize token refresh mechanism
     * Will attempt to refresh the token when it's close to expiring
     */
    _startRefreshTimer() {
        // Clear any existing refresh timer
        if (this.tokenRefreshInterval) {
            clearInterval(this.tokenRefreshInterval);
        }
        
        this.tokenRefreshInterval = setInterval(() => {
            try {
                const token = this.getToken();
                if (!token) {
                    this._debug('No token found, clearing refresh timer');
                    clearInterval(this.tokenRefreshInterval);
                    return;
                }
                
                const decoded = this._parseJwt(token);
                const expiresIn = decoded.exp - Math.floor(Date.now() / 1000);
                
                // Refresh when token has less than 5 minutes left
                if (expiresIn < 300) {
                    this._debug(`Token expires in ${expiresIn}s, refreshing...`);
                    this.refreshToken();
                }
            } catch (e) {
                this._debug('Error in refresh timer', e);
            }
        }, 60000); // Check every minute
        
        this._debug('Token refresh timer started');
    }
    
    /**
     * Perform user login
     * @param {string} email - User email
     * @param {string} password - User password
     * @returns {Promise} Promise that resolves when login is successful
     */
    async login(email, password) {
        this._debug('Login attempt', { email });
        this._authState.loading = true;
        
        try {
            const response = await fetch(this.loginEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.getCsrfToken() || ''
                },
                credentials: 'include',
                body: JSON.stringify({ email, password })
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                let errorMessage = 'Login failed';
                
                try {
                    const errorData = JSON.parse(errorText);
                    errorMessage = errorData.error || errorData.message || errorMessage;
                } catch (e) {
                    // If not valid JSON, use the error text
                    errorMessage = errorText || errorMessage;
                }
                
                const error = new Error(errorMessage);
                error.status = response.status;
                error.type = this.ErrorTypes.UNAUTHORIZED;
                throw error;
            }
            
            const data = await response.json();
            this._debug('Login successful', { userId: data.user_id });
            
            // Token should be set by the server via cookies
            // Refresh authentication state
            await this.fetchUserData();
            
            // Start token refresh timer
            this._startRefreshTimer();
            
            // Notify that authentication state has changed
            this._notifyAuthStateChanged();
            
            // Also dispatch login success event
            if (window.CarFuseEvents) {
                window.CarFuseEvents.Auth.dispatchLoginSuccess({
                    userId: data.user_id,
                    name: data.name
                });
            } else {
                document.dispatchEvent(new CustomEvent('auth:login-success', {
                    detail: {
                        userId: data.user_id,
                        name: data.name
                    }
                }));
            }
            
            return data;
        } catch (error) {
            this._debug('Login error', error);
            
            // Standardize error and dispatch event
            const eventError = {
                type: error.type || this.ErrorTypes.UNAUTHORIZED,
                message: error.message || 'Login failed',
                status: error.status || 401
            };
            
            if (window.CarFuseEvents) {
                window.CarFuseEvents.Auth.dispatchLoginError(eventError);
            } else {
                document.dispatchEvent(new CustomEvent('auth:login-error', {
                    detail: eventError
                }));
            }
            
            throw error;
        } finally {
            this._authState.loading = false;
        }
    }
    
    /**
     * Refresh the authentication token
     * @returns {Promise} Promise that resolves when token is refreshed
     */
    refreshToken() {
        // Avoid multiple simultaneous refresh requests
        if (this.tokenRefreshPromise) {
            return this.tokenRefreshPromise;
        }
        
        this._debug('Refreshing token...');
        
        // Prepare the refresh request data
        const refreshToken = this.getRefreshToken();
        if (!refreshToken) {
            const error = new Error('No refresh token available');
            error.type = this.ErrorTypes.REFRESH_FAILED;
            return Promise.reject(error);
        }
        
        const requestData = {
            refresh_token: refreshToken
        };
        
        this.tokenRefreshPromise = fetch(this.refreshEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.getCsrfToken() || ''
            },
            credentials: 'same-origin',
            body: JSON.stringify(requestData)
        })
        .then(response => {
            if (!response.ok) {
                const error = new Error(`Token refresh failed: ${response.status} ${response.statusText}`);
                error.status = response.status;
                error.type = this.ErrorTypes.REFRESH_FAILED;
                throw error;
            }
            return response.json();
        })
        .then(data => {
            this._debug('Token refreshed successfully', data);
            // New tokens are automatically set by the server via cookies
            
            // Notify listeners that auth state changed
            this._notifyAuthStateChanged();
            
            // Dispatch token refreshed event
            if (window.CarFuseEvents) {
                window.CarFuseEvents.Auth.dispatchTokenRefreshed();
            } else {
                document.dispatchEvent(new CustomEvent('auth:token-refreshed'));
            }
            
            return data;
        })
        .catch(error => {
            this._debug('Token refresh error', error);
            
            // Handle specific error cases
            if (error.status === 401 || error.status === 403) {
                // Token is invalid or expired beyond refresh capability
                this._debug('Authentication has expired, redirecting to login');
                this.redirectToLogin(false);
            }
            
            // Dispatch error event
            const eventError = {
                type: error.type || this.ErrorTypes.REFRESH_FAILED,
                message: error.message || 'Token refresh failed',
                status: error.status || 500
            };
            
            if (window.CarFuseEvents) {
                window.CarFuseEvents.dispatch(window.CarFuseEvents.NAMES.AUTH.SESSION_EXPIRED, eventError);
            } else {
                document.dispatchEvent(new CustomEvent('auth:session-expired', {
                    detail: eventError
                }));
            }
            
            throw error;
        })
        .finally(() => {
            this.tokenRefreshPromise = null;
        });
        
        return this.tokenRefreshPromise;
    }
    
    /**
     * Fetch current user data including role from API
     * @returns {Promise<object>} Promise that resolves with user data
     */
    fetchUserData() {
        this._debug('Fetching user data from API...');
        this._authState.loading = true;
        
        return fetch(this.userInfoEndpoint, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Authorization': `Bearer ${this.getToken()}`
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                const error = new Error(`Failed to fetch user data: ${response.status} ${response.statusText}`);
                error.status = response.status;
                error.type = response.status === 401 
                    ? this.ErrorTypes.UNAUTHORIZED 
                    : this.ErrorTypes.INVALID_RESPONSE;
                throw error;
            }
            return response.json();
        })
        .then(data => {
            this._debug('User data fetched successfully', data);
            
            // Update stored user info
            if (data && data.user) {
                this._authState.userInfo = data.user;
                this._authState.permissions = data.user.permissions || [];
            } else if (data) {
                this._authState.userInfo = data;
                this._authState.permissions = data.permissions || [];
            }
            
            this._authState.isAuthenticated = true;
            this._authState.loading = false;
            return data;
        })
        .catch(error => {
            this._debug('Error fetching user data', error);
            this._authState.loading = false;
            throw error;
        });
    }
    
    /**
     * Logout the user by revoking tokens
     * @returns {Promise} Promise that resolves when logout is complete
     */
    logout() {
        this._debug('Logging out...');
        this._authState.loading = true;
        
        // Get user ID for logging purposes
        const userId = this.getUserId();
        
        return fetch(this.logoutEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.getCsrfToken() || ''
            },
            credentials: 'same-origin',
            body: JSON.stringify({ user_id: userId })
        })
        .then(response => {
            // Even if server logout fails, continue with client-side logout
            if (!response.ok) {
                this._debug(`Server logout returned status: ${response.status}`);
            }
            
            // Clear any refresh timer
            if (this.tokenRefreshInterval) {
                clearInterval(this.tokenRefreshInterval);
                this.tokenRefreshInterval = null;
            }
            
            // Clear cookies (backend should also do this, but just to be safe)
            this._deleteCookie(this.tokenName);
            this._deleteCookie(this.refreshTokenName);
            
            // Clear all session storage items
            sessionStorage.clear();
            
            // Clear localStorage items related to auth
            localStorage.removeItem('auth_state');
            localStorage.removeItem('user_data');
            
            // Reset auth state
            this._authState = {
                isAuthenticated: false,
                userInfo: null,
                permissions: [],
                loading: false
            };
            
            // Notify listeners that auth state changed
            this._notifyAuthStateChanged();
            
            // Dispatch logout success event
            if (window.CarFuseEvents) {
                window.CarFuseEvents.Auth.dispatchLogoutSuccess();
            } else {
                document.dispatchEvent(new CustomEvent('auth:logout-success'));
            }
            
            this._debug('Logout successful');
            return true;
        })
        .catch(error => {
            this._debug('Logout error', error);
            
            // Still clear client-side auth data even if server logout fails
            this._deleteCookie(this.tokenName);
            this._deleteCookie(this.refreshTokenName);
            sessionStorage.clear();
            
            // Reset auth state
            this._authState = {
                isAuthenticated: false,
                userInfo: null,
                permissions: [],
                loading: false
            };
            
            // Notify listeners that auth state changed
            this._notifyAuthStateChanged();
            
            throw error;
        });
    }
    
    /**
     * Parse a JWT token to get its payload
     * @param {string} token - JWT token to parse
     * @returns {object} Decoded JWT payload
     */
    _parseJwt(token) {
        try {
            const base64Url = token.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));

            return JSON.parse(jsonPayload);
        } catch (e) {
            this._debug('Error parsing JWT', e);
            throw new Error('Invalid token format');
        }
    }
    
    /**
     * Get a cookie value by name
     * @param {string} name - Cookie name
     * @returns {string|null} Cookie value or null if not found
     */
    _getCookie(name) {
        const match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
        return match ? decodeURIComponent(match[3]) : null;
    }
    
    /**
     * Delete a cookie by name
     * @param {string} name - Cookie name to delete
     */
    _deleteCookie(name) {
        document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; SameSite=Strict`;
    }
    
    /**
     * Notify listeners that the authentication state has changed
     */
    _notifyAuthStateChanged() {
        // Create event detail
        const eventDetail = {
            authenticated: this.isAuthenticated(),
            userId: this.getUserId(),
            role: this.getUserRole()
        };
        
        // Dispatch standard event
        if (window.CarFuseEvents) {
            window.CarFuseEvents.Auth.dispatchStateChanged(eventDetail);
        } else {
            document.dispatchEvent(new CustomEvent('auth:state-changed', {
                detail: eventDetail
            }));
        }
        
        // Update Alpine.js stores if Alpine is available
        if (window.Alpine) {
            if (window.Alpine.store && typeof window.Alpine.store === 'function') {
                try {
                    const authStore = window.Alpine.store('auth');
                    if (authStore) {
                        authStore.refresh();
                    }
                } catch (e) {
                    this._debug('Error updating Alpine store', e);
                }
            }
        }
    }
    
    /**
     * Configure redirect paths for different roles
     * @param {object} paths - Object with roles as keys and redirect paths as values
     */
    setRedirectPaths(paths) {
        if (paths && typeof paths === 'object') {
            this.redirectPaths = { ...this.redirectPaths, ...paths };
            this._debug('Redirect paths updated', this.redirectPaths);
        }
    }
    
    /**
     * Verify if the current session is valid
     * @param {boolean} [redirectOnFailure=true] - Whether to redirect if verification fails
     * @returns {Promise} Promise that resolves if session is valid, rejects otherwise
     */
    verifySession(redirectOnFailure = true) {
        return new Promise((resolve, reject) => {
            if (!this.isAuthenticated()) {
                this._debug('Session verification failed: Not authenticated');
                
                if (redirectOnFailure) {
                    this.redirectToLogin();
                }
                
                const error = new Error('Not authenticated');
                error.type = this.ErrorTypes.NOT_AUTHENTICATED;
                reject(error);
                return;
            }
            
            // Check if token is about to expire (less than 2 minutes)
            try {
                const token = this.getToken();
                const decoded = this._parseJwt(token);
                const expiresIn = decoded.exp - Math.floor(Date.now() / 1000);
                
                if (expiresIn < 120) {
                    this._debug(`Token expires soon (${expiresIn}s), refreshing...`);
                    
                    // Refresh the token first
                    this.refreshToken()
                        .then(() => {
                            this._debug('Session verified after token refresh');
                            resolve(true);
                        })
                        .catch(error => {
                            this._debug('Session verification failed during refresh', error);
                            
                            if (redirectOnFailure) {
                                this.redirectToLogin();
                            }
                            
                            reject(error);
                        });
                } else {
                    this._debug('Session verified');
                    resolve(true);
                }
            } catch (error) {
                this._debug('Session verification failed', error);
                
                if (redirectOnFailure) {
                    this.redirectToLogin();
                }
                
                reject(error);
            }
        });
    }
    
    /**
     * Redirect user based on their role or to login page if not authenticated
     * @param {boolean} [checkRole=true] - Whether to check role for redirect path
     */
    redirectToLogin(checkRole = true) {
        let redirectPath = this.redirectPaths.default;
        
        if (checkRole && this.isAuthenticated()) {
            const role = this.getUserRole();
            if (role && this.redirectPaths[role]) {
                redirectPath = this.redirectPaths[role];
            }
        }
        
        this._debug(`Redirecting to ${redirectPath}`);
        window.location.href = redirectPath;
    }
    
    /**
     * Get the CSRF token from meta tag
     * @returns {string|null} CSRF token or null if not found
     */
    getCsrfToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : null;
    }
    
    /**
     * Create fetch request with CSRF token and auth token if available
     * @param {string} url - URL to fetch
     * @param {Object} options - Fetch options
     * @returns {Promise} Fetch promise
     */
    fetch(url, options = {}) {
        const csrfToken = this.getCsrfToken();
        if (!csrfToken) {
            console.warn('CSRF token not found in page meta tags');
        }
        
        // Build headers with CSRF token
        const headers = options.headers || {};
        headers['X-CSRF-Token'] = csrfToken;
        headers['X-Requested-With'] = 'XMLHttpRequest';
        
        // Add Authorization header if authenticated
        if (this.isAuthenticated()) {
            const authToken = this.getToken();
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
            console.error('Fetch error:', error);
            throw error;
        });
    }
    
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
        if (this.isAuthenticated()) {
            const authToken = this.getToken();
            if (authToken) {
                const tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = 'auth_token';
                tokenInput.value = authToken;
                form.appendChild(tokenInput);
            }
        }
        
        // Submit via AJAX if requested
        if (ajax) {
            const formData = new FormData(form);
            const url = form.action;
            const method = form.method.toUpperCase() || 'POST';
            
            return this.fetch(url, {
                method: method,
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            });
        } else {
            // Traditional form submission
            form.submit();
        }
    }
    
    /**
     * Add CSRF token to form
     * @param {HTMLFormElement} form - Form to add CSRF token to
     */
    addCsrfToForm(form) {
        const csrfToken = this.getCsrfToken();
        if (!csrfToken) {
            console.error('CSRF token not found in page meta tags');
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
    }
    
    /**
     * Private method: Handle authentication errors
     * @private
     * @param {Response} response - Fetch response object
     */
    _handleAuthError(response) {
        // Try to refresh the token first
        this.refreshToken()
            .catch(() => {
                // If refresh fails, handle as session expired
                this.redirectToLogin(false);
            });
    }
    
    /**
     * Generate a new CSRF token and update the meta tag
     * Only for development/testing - in production, the server should set this
     * @returns {string} The new CSRF token
     */
    generateCsrfToken() {
        // Generate a random token
        const token = Array.from(window.crypto.getRandomValues(new Uint8Array(32)))
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');
        
        // Find or create meta tag
        let metaTag = document.querySelector('meta[name="csrf-token"]');
        if (!metaTag) {
            metaTag = document.createElement('meta');
            metaTag.setAttribute('name', 'csrf-token');
            document.head.appendChild(metaTag);
        }
        
        // Set the new token
        metaTag.setAttribute('content', token);
        return token;
    }
    
    /**
     * Initialize HTMX extension to add auth headers to requests
     */
    _initHtmxExtension() {
        // Check if HTMX is available
        if (typeof window.htmx !== 'undefined') {
            this._debug('Initializing HTMX extension integration');

            // Use new modular HTMX structure if available
            if (typeof window.CarFuseHTMX !== 'undefined' && typeof window.CarFuseHTMX.registerExtension === 'function') {
                // CarFuseHTMX already handles this through the auth extension
                this._debug('Using modular CarFuseHTMX auth extension');

                // Listen for HTMX ready event to ensure proper integration
                document.addEventListener('carfuse:htmx-ready', () => {
                    this._debug('CarFuseHTMX is ready - integration complete');
                    // Just notify that auth is available
                    if (window.CarFuseEvents) {
                        window.CarFuseEvents.Auth.dispatchReady();
                    } else {
                        document.dispatchEvent(new CustomEvent('auth:ready'));
                    }
                });

                return;
            } else {
                this._debug('CarFuseHTMX is not fully available.');
            }
        }
    }
    
    /**
     * Create an Alpine.js data function for auth state
     * Usage: x-data="authState()"
     * @returns {object} Alpine.js data object with auth state
     */
    authState() {
        const helper = this;
        
        return {
            isAuthenticated: helper.isAuthenticated(),
            userId: helper.getUserId(),
            username: helper.getUsername(),
            email: helper.getUserEmail(),
            role: helper.getUserRole(),
            userData: helper.getUserData(),
            loading: false,
            error: null,
            
            /**
             * Initialize the auth state component
             */
            init() {
                // Listen for auth state changes
                document.addEventListener('auth:state-changed', () => this.refresh());
                
                // Refresh state
                this.refresh();
            },
            
            /**
             * Save auth state to localStorage for persistence
             */
            saveState() {
                // Save state to localStorage for persistence
                localStorage.setItem('auth_state', JSON.stringify({
                    isAuthenticated: this.isAuthenticated,
                    userId: this.userId,
                    username: this.username,
                    email: this.email,
                    role: this.role
                }));
                
                if (this.userData) {
                    localStorage.setItem('user_data', JSON.stringify(this.userData));
                }
            },
            
            /**
             * Restore auth state from localStorage if available
             */
            restoreState() {
                // Restore from localStorage if available
                try {
                    const state = JSON.parse(localStorage.getItem('auth_state'));
                    const userData = JSON.parse(localStorage.getItem('user_data'));
                    
                    if (state && helper.isAuthenticated()) {
                        this.role = state.role;
                        // Only restore data if we're still authenticated
                        if (userData) {
                            this.userData = userData;
                        }
                    }
                } catch (e) {
                    helper._debug('Error restoring auth state', e);
                }
            },
            
            /**
             * Handle user logout
             * @returns {Promise} Promise that resolves when logout is complete
             */
            logout() {
                return helper.logout()
                    .catch(error => {
                        console.error('Logout failed:', error);
                    });
            },
            
            /**
             * Verify if session is still valid
             * @param {boolean} redirect - Whether to redirect on failure
             * @returns {Promise} Promise that resolves if session is valid
             */
            verifySession(redirect = true) {
                return helper.verifySession(redirect);
            },
            
            /**
             * Refresh auth state from AuthHelper
             * @returns {object} Updated auth state
             */
            refresh() {
                this.isAuthenticated = helper.isAuthenticated();
                this.userId = helper.getUserId();
                this.username = helper.getUsername();
                this.email = helper.getUserEmail();
                this.role = helper.getUserRole();
                this.userData = helper.getUserData();
                
                // Save updated state
                this.saveState();
                
                return this;
            },
            
            /**
             * Check if current user has specific role
             * @param {string} role - Role to check
             * @returns {boolean} True if user has the role
             */
            hasRole(role) {
                return helper.hasRole(role);
            },
            
            /**
             * Check if current user has specific permission
             * @param {string} permission - Permission to check
             * @returns {boolean} True if user has the permission
             */
            hasPermission(permission) {
                return helper.hasPermission(permission);
            },
            
            /**
             * Check if current user can access specific resource
             * @param {string} resource - Resource to check
             * @returns {boolean} True if user can access the resource
             */
            canAccess(resource) {
                return helper.canAccess(resource);
            }
        };
    }
    
    /**
     * Register with Alpine.js if available
     */
    registerAlpine() {
        if (window.Alpine) {
            this._debug('Registering with Alpine.js');
            
            // Register auth store
            window.Alpine.store('auth', this.getAuthState());
            
            // Register authState helper
            window.Alpine.data('authState', () => this.authState());
            
            // Add auth directives
            this._registerAlpineDirectives(window.Alpine);
        }
    }
    
    /**
     * Register Alpine.js directives for auth
     * @param {object} Alpine - Alpine.js instance
     * @private
     */
    _registerAlpineDirectives(Alpine) {
        const helper = this;
        
        // x-auth-role directive for showing/hiding based on role
        Alpine.directive('auth-role', (el, { expression }, { evaluate, cleanup }) => {
            const updateVisibility = () => {
                const requiredRole = evaluate(expression);
                const hasRole = helper.hasRole(requiredRole);
                el.style.display = hasRole ? '' : 'none';
            };
            
            updateVisibility();
            
            // Listen for auth state changes to update visibility
            const listener = () => updateVisibility();
            document.addEventListener('auth:state-changed', listener);
            
            cleanup(() => {
                document.removeEventListener('auth:state-changed', listener);
            });
        });
        
        // x-auth-permission directive for showing/hiding based on permission
        Alpine.directive('auth-permission', (el, { expression }, { evaluate, cleanup }) => {
            const updateVisibility = () => {
                const requiredPermission = evaluate(expression);
                const hasPermission = helper.hasPermission(requiredPermission);
                el.style.display = hasPermission ? '' : 'none';
            };
            
            updateVisibility();
            
            // Listen for auth state changes to update visibility
            const listener = () => updateVisibility();
            document.addEventListener('auth:state-changed', listener);
            
            cleanup(() => {
                document.removeEventListener('auth:state-changed', listener);
            });
        });
        
        // x-auth-access directive for showing/hiding based on resource access
        Alpine.directive('auth-access', (el, { expression }, { evaluate, cleanup }) => {
            const updateVisibility = () => {
                const resource = evaluate(expression);
                const hasAccess = helper.canAccess(resource);
                el.style.display = hasAccess ? '' : 'none';
            };
            
            updateVisibility();
            
            // Listen for auth state changes to update visibility
            const listener = () => updateVisibility();
            document.addEventListener('auth:state-changed', listener);
            
            cleanup(() => {
                document.removeEventListener('auth:state-changed', listener);
            });
        });
    }
}

// Create global instance
const auth = new AuthHelper();

// Handle session-to-token transition on page load
document.addEventListener('DOMContentLoaded', () => {
    // Add CSRF token meta tag if not present
    if (!document.querySelector('meta[name="csrf-token"]')) {
        const csrfToken = auth.generateCsrfToken();
        console.info('[AuthHelper] Generated CSRF token for development');
    }
    
    // Check for authentication state
    if (auth.isAuthenticated()) {
        // Notify that authentication is ready
        if (window.CarFuseEvents) {
            window.CarFuseEvents.Auth.dispatchReady();
        } else {
            document.dispatchEvent(new CustomEvent('auth:ready'));
        }
    }
});

// If Alpine.js is already loaded, register with it
if (window.Alpine) {
    auth.registerAlpine();
} else {
    // Otherwise, wait for Alpine to load
    document.addEventListener('alpine:init', () => {
        auth.registerAlpine();
    });
}

// Export the auth instance
window.AuthHelper = auth;
