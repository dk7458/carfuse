/**
 * CarFuse Toast Notifications
 * 
 * Provides a toast notification system that integrates with the event system
 * to display authentication and other messages to users.
 */
(function() {
    // Default options
    const DEFAULT_OPTIONS = {
        position: 'top-right',
        autoClose: true,
        closeTime: 5000,
        animations: true,
        pauseOnHover: true,
        maxToasts: 5,
        appendTo: 'body',
        customClasses: '',
        showProgress: true
    };
    
    // CSS Classes
    const CLASSES = {
        CONTAINER: 'cf-toast-container',
        TOAST: 'cf-toast',
        HEADER: 'cf-toast-header',
        BODY: 'cf-toast-body',
        CLOSE: 'cf-toast-close',
        PROGRESS: 'cf-toast-progress',
        
        // Types
        SUCCESS: 'cf-toast-success',
        ERROR: 'cf-toast-error',
        INFO: 'cf-toast-info',
        WARNING: 'cf-toast-warning',
        
        // Animation
        SHOW: 'cf-toast-show',
        HIDE: 'cf-toast-hide',
        
        // Positions
        TOP_RIGHT: 'cf-toast-top-right',
        TOP_LEFT: 'cf-toast-top-left',
        BOTTOM_RIGHT: 'cf-toast-bottom-right',
        BOTTOM_LEFT: 'cf-toast-bottom-left',
        TOP_CENTER: 'cf-toast-top-center',
        BOTTOM_CENTER: 'cf-toast-bottom-center'
    };
    
    // Container to hold toasts
    let toastContainer = null;
    
    // Track active toasts
    let activeToasts = [];
    
    // Current options
    let currentOptions = { ...DEFAULT_OPTIONS };
    
    /**
     * Create the toast container if it doesn't exist
     */
    function ensureContainer() {
        if (toastContainer) return;
        
        // Create container
        toastContainer = document.createElement('div');
        toastContainer.className = `${CLASSES.CONTAINER} ${CLASSES[currentOptions.position.toUpperCase().replace('-', '_')]} ${currentOptions.customClasses}`.trim();
        
        // Add to DOM
        const target = document.querySelector(currentOptions.appendTo) || document.body;
        target.appendChild(toastContainer);
    }
    
    /**
     * Create a toast notification
     * @param {string} message - Toast message content
     * @param {string} title - Toast title
     * @param {string} type - Toast type (success, error, info, warning)
     * @param {object} options - Toast options
     * @returns {HTMLElement} The toast element
     */
    function createToast(message, title, type = 'info', options = {}) {
        ensureContainer();
        
        // Merge options
        const toastOptions = { ...currentOptions, ...options };
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `${CLASSES.TOAST} ${CLASSES[type.toUpperCase()]}`;
        toast.setAttribute('role', 'alert');
        
        // Create header if title provided
        if (title) {
            const header = document.createElement('div');
            header.className = CLASSES.HEADER;
            header.textContent = title;
            toast.appendChild(header);
        }
        
        // Add close button
        const closeBtn = document.createElement('button');
        closeBtn.className = CLASSES.CLOSE;
        closeBtn.innerHTML = '&times;';
        closeBtn.setAttribute('aria-label', 'Close');
        
        closeBtn.addEventListener('click', () => {
            removeToast(toast);
        });
        
        toast.appendChild(closeBtn);
        
        // Add message body
        const body = document.createElement('div');
        body.className = CLASSES.BODY;
        
        // Support HTML or plain text
        if (options.allowHtml) {
            body.innerHTML = message;
        } else {
            body.textContent = message;
        }
        
        toast.appendChild(body);
        
        // Add progress bar if enabled
        if (toastOptions.showProgress && toastOptions.autoClose) {
            const progress = document.createElement('div');
            progress.className = CLASSES.PROGRESS;
            toast.appendChild(progress);
            
            // Animate progress bar
            setTimeout(() => {
                progress.style.width = '0%';
                progress.style.transition = `width ${toastOptions.closeTime}ms linear`;
            }, 10);
        }
        
        // Pause on hover if enabled
        if (toastOptions.pauseOnHover) {
            let timeLeft = toastOptions.closeTime;
            let timerId = null;
            let startTime;
            
            const startTimer = () => {
                startTime = Date.now();
                timerId = setTimeout(() => {
                    removeToast(toast);
                }, timeLeft);
            };
            
            const pauseTimer = () => {
                if (timerId) {
                    clearTimeout(timerId);
                    timerId = null;
                    timeLeft -= (Date.now() - startTime);
                    
                    // Pause progress bar animation
                    const progress = toast.querySelector(`.${CLASSES.PROGRESS}`);
                    if (progress) {
                        const width = window.getComputedStyle(progress).width;
                        progress.style.transition = 'none';
                        progress.style.width = width;
                    }
                }
            };
            
            toast.addEventListener('mouseenter', pauseTimer);
            toast.addEventListener('mouseleave', startTimer);
            
            // Start initial timer
            if (toastOptions.autoClose) {
                startTimer();
            }
        } else if (toastOptions.autoClose) {
            // Simple auto-close
            setTimeout(() => {
                removeToast(toast);
            }, toastOptions.closeTime);
        }
        
        // Add to container
        toastContainer.appendChild(toast);
        
        // Track the toast
        activeToasts.push(toast);
        
        // Enforce max toasts
        if (activeToasts.length > toastOptions.maxToasts) {
            removeToast(activeToasts[0]);
        }
        
        // Trigger animation
        if (toastOptions.animations) {
            toast.classList.add(CLASSES.SHOW);
        }
        
        return toast;
    }
    
    /**
     * Remove a toast from the container
     * @param {HTMLElement} toast - Toast element to remove
     */
    function removeToast(toast) {
        if (!toast) return;
        
        // Remove from tracking array
        activeToasts = activeToasts.filter(t => t !== toast);
        
        // Animate out
        if (currentOptions.animations) {
            toast.classList.add(CLASSES.HIDE);
            toast.classList.remove(CLASSES.SHOW);
            
            // Wait for animation to finish
            toast.addEventListener('animationend', () => {
                toast.remove();
                
                // Remove container if empty
                if (activeToasts.length === 0 && toastContainer && toastContainer.parentNode) {
                    toastContainer.remove();
                    toastContainer = null;
                }
            }, { once: true });
        } else {
            // Remove immediately
            toast.remove();
            
            // Remove container if empty
            if (activeToasts.length === 0 && toastContainer && toastContainer.parentNode) {
                toastContainer.remove();
                toastContainer = null;
            }
        }
    }
    
    /**
     * Configure the toast notification system
     * @param {object} options - Configuration options
     */
    function configure(options) {
        if (!options || typeof options !== 'object') return;
        
        currentOptions = { ...DEFAULT_OPTIONS, ...options };
        
        // Recreate container with new options if it exists
        if (toastContainer) {
            const parent = toastContainer.parentNode;
            toastContainer.remove();
            toastContainer = null;
            ensureContainer();
        }
    }
    
    /**
     * Create a success toast
     * @param {string} message - Toast message
     * @param {string} title - Toast title
     * @param {object} options - Toast options
     * @returns {HTMLElement} The toast element
     */
    function success(message, title = 'Success', options = {}) {
        return createToast(message, title, 'success', options);
    }
    
    /**
     * Create an error toast
     * @param {string} message - Toast message
     * @param {string} title - Toast title
     * @param {object} options - Toast options
     * @returns {HTMLElement} The toast element
     */
    function error(message, title = 'Error', options = {}) {
        return createToast(message, title, 'error', options);
    }
    
    /**
     * Create an info toast
     * @param {string} message - Toast message
     * @param {string} title - Toast title
     * @param {object} options - Toast options
     * @returns {HTMLElement} The toast element
     */
    function info(message, title = 'Information', options = {}) {
        return createToast(message, title, 'info', options);
    }
    
    /**
     * Create a warning toast
     * @param {string} message - Toast message
     * @param {string} title - Toast title
     * @param {object} options - Toast options
     * @returns {HTMLElement} The toast element
     */
    function warning(message, title = 'Warning', options = {}) {
        return createToast(message, title, 'warning', options);
    }
    
    /**
     * Clear all active toasts
     */
    function clearAll() {
        // Create a copy of activeToasts to avoid modification during iteration
        const toasts = [...activeToasts];
        toasts.forEach(toast => {
            removeToast(toast);
        });
    }
    
    /**
     * Toast API object
     */
    const Toast = {
        configure,
        show: createToast,
        success,
        error,
        info,
        warning,
        clear: removeToast,
        clearAll
    };
    
    // Export globally
    window.CarFuseToast = Toast;
    
    // Listen for toast events from the event system
    document.addEventListener('DOMContentLoaded', () => {
        // Set up toast CSS if not already included
        if (!document.querySelector('#cf-toast-styles')) {
            const style = document.createElement('style');
            style.id = 'cf-toast-styles';
            style.textContent = `
                .cf-toast-container {
                    position: fixed;
                    z-index: 9999;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    max-width: 350px;
                }
                
                .cf-toast-top-right {
                    top: 20px;
                    right: 20px;
                }
                
                .cf-toast-top-left {
                    top: 20px;
                    left: 20px;
                }
                
                .cf-toast-bottom-right {
                    bottom: 20px;
                    right: 20px;
                }
                
                .cf-toast-bottom-left {
                    bottom: 20px;
                    left: 20px;
                }
                
                .cf-toast-top-center {
                    top: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                }
                
                .cf-toast-bottom-center {
                    bottom: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                }
                
                .cf-toast {
                    position: relative;
                    padding: 15px;
                    border-radius: 6px;
                    color: #fff;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    min-width: 250px;
                    max-width: 100%;
                    overflow: hidden;
                    opacity: 0;
                    transform: translateY(-10px);
                    animation: cf-toast-in 0.3s ease forwards;
                }
                
                .cf-toast-success {
                    background-color: #4caf50;
                }
                
                .cf-toast-error {
                    background-color: #f44336;
                }
                
                .cf-toast-info {
                    background-color: #2196f3;
                }
                
                .cf-toast-warning {
                    background-color: #ff9800;
                }
                
                .cf-toast-header {
                    font-weight: bold;
                    margin-bottom: 5px;
                    padding-right: 20px;
                }
                
                .cf-toast-body {
                    word-break: break-word;
                }
                
                .cf-toast-close {
                    position: absolute;
                    top: 5px;
                    right: 5px;
                    background: transparent;
                    border: none;
                    color: #fff;
                    cursor: pointer;
                    font-size: 18px;
                    opacity: 0.8;
                    padding: 5px;
                }
                
                .cf-toast-close:hover {
                    opacity: 1;
                }
                
                .cf-toast-progress {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    width: 100%;
                    height: 3px;
                    background-color: rgba(255, 255, 255, 0.4);
                    transform-origin: left;
                }
                
                .cf-toast-show {
                    opacity: 1;
                    transform: translateY(0);
                }
                
                .cf-toast-hide {
                    animation: cf-toast-out 0.3s forwards;
                }
                
                @keyframes cf-toast-in {
                    from {
                        opacity: 0;
                        transform: translateY(-10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                
                @keyframes cf-toast-out {
                    from {
                        opacity: 1;
                        transform: translateY(0);
                    }
                    to {
                        opacity: 0;
                        transform: translateY(-10px);
                    }
                }
            `;
            
            document.head.appendChild(style);
        }
        
        // Bind event listeners if Events system exists
        if (window.CarFuseEvents) {
            // Listen for toast show events
            window.CarFuseEvents.on(window.CarFuseEvents.NAMES.UI.TOAST_SHOW, (e) => {
                const { title, message, type, options } = e.detail;
                
                if (type === 'success') {
                    Toast.success(message, title, options);
                } else if (type === 'error') {
                    Toast.error(message, title, options);
                } else if (type === 'warning') {
                    Toast.warning(message, title, options);
                } else {
                    Toast.info(message, title, options);
                }
            });
            
            // Listen for specific authentication events and show appropriate toasts
            window.CarFuseEvents.Auth.onLoginSuccess((e) => {
                Toast.success('You have successfully logged in!');
            });
            
            window.CarFuseEvents.Auth.onLoginError((e) => {
                Toast.error(e.detail.message || 'Login failed');
            });
            
            window.CarFuseEvents.Auth.onLogoutSuccess(() => {
                Toast.info('You have been logged out');
            });
            
            window.CarFuseEvents.Auth.onSessionExpired((e) => {
                Toast.warning(e.detail.message || 'Your session has expired. Please log in again.');
            });
            
            window.CarFuseEvents.Auth.onForbidden((e) => {
                Toast.error(e.detail.message || 'Access denied');
            });
            
            window.CarFuseEvents.Auth.onUnauthorized((e) => {
                Toast.warning(e.detail.message || 'Authentication required');
            });
        } else {
            // Fallback for standard events if CarFuseEvents is not available
            document.addEventListener('ui:toast-show', (e) => {
                const { title, message, type, options } = e.detail;
                
                if (type === 'success') {
                    Toast.success(message, title, options);
                } else if (type === 'error') {
                    Toast.error(message, title, options);
                } else if (type === 'warning') {
                    Toast.warning(message, title, options);
                } else {
                    Toast.info(message, title, options);
                }
            });
            
            // Listen for auth events
            document.addEventListener('auth:login-success', () => {
                Toast.success('You have successfully logged in!');
            });
            
            document.addEventListener('auth:login-error', (e) => {
                Toast.error(e.detail.message || 'Login failed');
            });
            
            document.addEventListener('auth:logout-success', () => {
                Toast.info('You have been logged out');
            });
            
            document.addEventListener('auth:session-expired', (e) => {
                Toast.warning(e.detail.message || 'Your session has expired. Please log in again.');
            });
            
            document.addEventListener('auth:forbidden', (e) => {
                Toast.error(e.detail.message || 'Access denied');
            });
            
            document.addEventListener('auth:unauthorized', (e) => {
                Toast.warning(e.detail.message || 'Authentication required');
            });
        }
    });
    
    console.info('CarFuse Toast component loaded');
})();
