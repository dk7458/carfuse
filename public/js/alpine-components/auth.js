/**
 * Alpine Auth Component
 * Handles authentication UI interactions and state management
 * Integrates with AuthHelper as the single source of truth for auth state
 */

(() => {
    // Check if CarFuseAlpine is available
    if (!window.CarFuseAlpine) {
        console.error('CarFuseAlpine is not initialized. Make sure alpine.js is loaded before this component.');
        return;
    }
    
    /**
     * Alpine Auth component
     * Provides UI components and state management for authentication
     */
    window.CarFuseAlpine.registerComponent('auth', () => {
        return {
            // Core authentication state
            isLoggedIn: false,
            user: null,
            userRole: null,
            userId: null,
            loading: false,
            error: null,
            authReady: false,
            
            /**
             * Initialize the auth component
             * Sets up error boundaries and event listeners
             */
            init() {
                // Set up error boundary
                this.$el.setAttribute('x-error-boundary', '');
                
                try {
                    this.checkAuthState();
                    
                    // Listen for auth state changes using standardized events
                    const eventName = window.CarFuseEvents 
                        ? window.CarFuseEvents.NAMES.AUTH.STATE_CHANGED 
                        : 'auth:state-changed';
                    
                    document.addEventListener(eventName, () => this.checkAuthState());
                    
                    // Mark as ready
                    this.authReady = true;
                } catch (e) {
                    this.error = e.message || 'Authentication error';
                    console.error('[Alpine Auth] Error:', e);
                }
            },
            
            /**
             * Updates local state from AuthHelper (single source of truth)
             */
            checkAuthState() {
                if (window.AuthHelper) {
                    const authState = window.AuthHelper.getAuthState();
                    this.isLoggedIn = authState.isAuthenticated;
                    this.userRole = authState.userRole;
                    this.userId = authState.userId;
                    
                    if (this.isLoggedIn) {
                        this.user = authState.userData;
                    } else {
                        this.user = null;
                    }
                }
            },
            
            /**
             * Handle login form submission
             * @param {Event} e - Form submit event
             */
            async login(e) {
                e.preventDefault();
                this.loading = true;
                this.error = null;
                
                try {
                    const form = e.target;
                    const email = form.elements.email?.value;
                    const password = form.elements.password?.value;
                    
                    if (!email || !password) {
                        throw new Error('Email and password are required');
                    }
                    
                    if (!window.AuthHelper) {
                        throw new Error('Authentication system not available');
                    }
                    
                    // Use AuthHelper's login method
                    await window.AuthHelper.login(email, password);
                    this.checkAuthState();
                    
                    // Show success message using standardized event
                    if (window.CarFuseEvents) {
                        window.CarFuseEvents.UI.dispatchToastShow({
                            title: 'Success',
                            message: 'Logged in successfully!',
                            type: 'success'
                        });
                    } else {
                        window.dispatchEvent(new CustomEvent('ui:toast-show', {
                            detail: {
                                title: 'Success',
                                message: 'Logged in successfully!',
                                type: 'success'
                            }
                        }));
                    }
                    
                    // Redirect if specified
                    const redirect = form.getAttribute('data-redirect');
                    if (redirect) {
                        window.location.href = redirect;
                    }
                } catch (e) {
                    this.error = e.message || 'Login failed';
                    console.error('[Alpine Auth] Login error:', e);
                    
                    // Show error message
                    if (window.CarFuseEvents) {
                        window.CarFuseEvents.UI.dispatchToastShow({
                            title: 'Error',
                            message: this.error,
                            type: 'error'
                        });
                    } else {
                        window.dispatchEvent(new CustomEvent('ui:toast-show', {
                            detail: {
                                title: 'Error',
                                message: this.error,
                                type: 'error'
                            }
                        }));
                    }
                } finally {
                    this.loading = false;
                }
            },
            
            /**
             * Handle user logout
             * Clears auth state and redirects user
             */
            async logout() {
                if (!this.isLoggedIn) return;
                
                this.loading = true;
                this.error = null;
                
                try {
                    if (!window.AuthHelper) {
                        throw new Error('Authentication system not available');
                    }
                    
                    // Use AuthHelper's logout method
                    await window.AuthHelper.logout();
                    this.checkAuthState();
                    
                    // Show success message
                    if (window.CarFuseEvents) {
                        window.CarFuseEvents.UI.dispatchToastShow({
                            title: 'Success',
                            message: 'Logged out successfully',
                            type: 'success'
                        });
                    } else {
                        window.dispatchEvent(new CustomEvent('ui:toast-show', {
                            detail: {
                                title: 'Success',
                                message: 'Logged out successfully',
                                type: 'success'
                            }
                        }));
                    }
                    
                    // Redirect to home page
                    window.location.href = '/';
                } catch (e) {
                    this.error = e.message || 'Logout failed';
                    console.error('[Alpine Auth] Logout error:', e);
                    
                    // Show error message
                    if (window.CarFuseEvents) {
                        window.CarFuseEvents.UI.dispatchToastShow({
                            title: 'Error',
                            message: this.error,
                            type: 'error'
                        });
                    } else {
                        window.dispatchEvent(new CustomEvent('ui:toast-show', {
                            detail: {
                                title: 'Error',
                                message: this.error,
                                type: 'error'
                            }
                        }));
                    }
                } finally {
                    this.loading = false;
                }
            },
            
            /**
             * Check if current user has a specific role
             * @param {string|string[]} role - Role(s) to check
             * @returns {boolean} True if user has any of the roles
             */
            hasRole(role) {
                return window.AuthHelper ? window.AuthHelper.hasRole(role) : false;
            },
            
            /**
             * Check if current user has at least the specified role level
             * @param {string} minimumRole - Minimum required role in hierarchy
             * @returns {boolean} True if user has at least this role level
             */
            hasRoleLevel(minimumRole) {
                return window.AuthHelper ? window.AuthHelper.hasRoleLevel(minimumRole) : false;
            },
            
            /**
             * Check if current user has specific permission
             * @param {string|string[]} permission - Permission(s) to check
             * @returns {boolean} True if user has any of the permissions
             */
            hasPermission(permission) {
                return window.AuthHelper ? window.AuthHelper.hasPermission(permission) : false;
            },
            
            /**
             * Check if user can access a specific resource
             * @param {string} resource - Resource identifier to check access for
             * @returns {boolean} True if user can access the resource
             */
            canAccess(resource) {
                return window.AuthHelper ? window.AuthHelper.canAccess(resource) : false;
            }
        };
    });
    
    /**
     * User Profile component
     * Handles user profile interactions and state management
     */
    window.CarFuseAlpine.registerComponent('userProfile', () => {
        return {
            user: null,
            loading: false,
            error: null,
            edit: false,
            
            /**
             * Initialize the user profile component
             */
            init() {
                // Set up error boundary
                this.$el.setAttribute('x-error-boundary', '');
                
                try {
                    this.fetchUserData();
                    
                    // Listen for auth state changes using standardized events
                    const eventName = window.CarFuseEvents 
                        ? window.CarFuseEvents.NAMES.AUTH.STATE_CHANGED 
                        : 'auth:state-changed';
                    
                    document.addEventListener(eventName, () => this.fetchUserData());
                } catch (e) {
                    this.error = e.message || 'Profile error';
                    console.error('[Alpine User Profile] Error:', e);
                }
            },
            
            /**
             * Fetch user data from AuthHelper
             */
            fetchUserData() {
                if (window.AuthHelper && window.AuthHelper.isAuthenticated()) {
                    this.user = window.AuthHelper.getUserData();
                } else {
                    this.user = null;
                }
            },
            
            /**
             * Toggle profile edit mode
             */
            toggleEdit() {
                this.edit = !this.edit;
                this.error = null;
            },
            
            /**
             * Save profile changes
             * @param {Event} e - Form submit event
             */
            async saveProfile(e) {
                e.preventDefault();
                this.loading = true;
                this.error = null;
                
                try {
                    const form = e.target;
                    const formData = new FormData(form);
                    
                    // Use AuthHelper's fetch method for secure API calls
                    if (!window.AuthHelper) {
                        throw new Error('Authentication system not available');
                    }
                    
                    const response = await window.AuthHelper.fetch('/api/user/profile', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        throw new Error(`Failed to update profile: ${response.status} ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    
                    // Update local user data
                    this.user = data.user || data;
                    this.edit = false;
                    
                    // Show success message using standardized events
                    if (window.CarFuseEvents) {
                        window.CarFuseEvents.UI.dispatchToastShow({
                            title: 'Success',
                            message: 'Profile updated successfully',
                            type: 'success'
                        });
                    } else {
                        window.dispatchEvent(new CustomEvent('ui:toast-show', {
                            detail: {
                                title: 'Success',
                                message: 'Profile updated successfully',
                                type: 'success'
                            }
                        }));
                    }
                } catch (e) {
                    this.error = e.message || 'Failed to update profile';
                    console.error('[Alpine User Profile] Save error:', e);
                    
                    // Show error message
                    if (window.CarFuseEvents) {
                        window.CarFuseEvents.UI.dispatchToastShow({
                            title: 'Error',
                            message: this.error,
                            type: 'error'
                        });
                    } else {
                        window.dispatchEvent(new CustomEvent('ui:toast-show', {
                            detail: {
                                title: 'Error',
                                message: this.error,
                                type: 'error'
                            }
                        }));
                    }
                } finally {
                    this.loading = false;
                }
            }
        };
    });
})();
