# CarFuse Authentication System

This documentation provides examples and usage instructions for the unified authentication system in CarFuse.

## Table of Contents

1. [Basic Usage](#basic-usage)
2. [Authentication Helper](#authentication-helper)
3. [Events System](#events-system)
4. [Role-Based Access Control](#role-based-access-control)
5. [UI Components](#ui-components)
6. [Toast Notifications](#toast-notifications)
7. [Alpine.js Integration](#alpinejs-integration)
8. [HTMX Integration](#htmx-integration)

## Basic Usage

The authentication system is designed to be used as a unified module that handles all authentication, authorization, and user interface concerns.

### Initialize the System

```javascript
document.addEventListener('DOMContentLoaded', () => {
    // Basic initialization
    CarFuseAuthSystem.init();
    
    // Advanced initialization with options
    CarFuseAuthSystem.init({
        debug: true,
        enableToasts: true,
        autoApplyRBAC: true,
        redirects: {
            afterLogin: '/dashboard',
            afterLogout: '/login'
        },
        resourceMappings: {
            'custom-resource': ['user', 'admin']
        }
    });
});
```

### Check Authentication State

```javascript
// Check if user is logged in
if (CarFuseAuthSystem.isAuthenticated()) {
    console.log('User is authenticated');
}

// Get detailed auth state
const authState = CarFuseAuthSystem.getState();
console.log('User ID:', authState.userId);
console.log('User Role:', authState.userRole);
```

### Handle User Login/Logout

```javascript
// Get reference to AuthHelper
const auth = CarFuseAuthSystem.helper;

// Login a user
auth.login('user@example.com', 'password')
    .then(response => {
        console.log('Login successful!');
    })
    .catch(error => {
        console.error('Login failed:', error.message);
    });

// Logout a user
auth.logout()
    .then(() => {
        console.log('Logout successful!');
    });
```

## Authentication Helper

The `AuthHelper` provides core authentication functionality:

```javascript
// Get user information
const userId = AuthHelper.getUserId();
const userRole = AuthHelper.getUserRole();
const userData = AuthHelper.getUserData();

// Check permissions
if (AuthHelper.hasRole('admin')) {
    // Show admin features
}

if (AuthHelper.hasPermission('edit_content')) {
    // Allow editing
}

// Verify session is still valid
AuthHelper.verifySession()
    .then(() => {
        console.log('Session is valid');
    })
    .catch(error => {
        console.error('Session expired or invalid');
    });

// Make authenticated API requests
AuthHelper.fetch('/api/protected-resource', {
    method: 'POST',
    body: JSON.stringify({ data: 'value' })
})
.then(response => response.json())
.then(data => console.log(data));
```

## Events System

The events system allows components to communicate with each other:

```javascript
// Listen for authentication events
CarFuseEvents.Auth.onLoginSuccess(event => {
    console.log('User logged in:', event.detail);
});

CarFuseEvents.Auth.onLogoutSuccess(() => {
    console.log('User logged out');
});

CarFuseEvents.Auth.onSessionExpired(() => {
    console.log('Session expired, redirecting to login');
});

// Dispatch custom auth-related events
CarFuseEvents.Auth.dispatchStateChanged({
    authenticated: true,
    userId: 123,
    role: 'user'
});

// Listen for UI events
CarFuseEvents.UI.onToastShow(event => {
    console.log('Toast shown:', event.detail);
});

// Use once listener that automatically removes itself
CarFuseEvents.once('auth:login-success', () => {
    console.log('This runs only once after login');
});
```

## Role-Based Access Control

RBAC allows controlling access to UI elements and functionality:

```javascript
// Check if user can access a resource
if (CarFuseRBAC.checkResourceAccess('admin-dashboard')) {
    // Show admin dashboard link
}

// Check if user has minimum role level
if (CarFuseRBAC.checkRoleLevel('moderator')) {
    // Show moderator+ features
}

// Apply RBAC to all elements with data attributes
CarFuseRBAC.applyAccessControl();

// Add custom resource mappings
CarFuseRBAC.configureResourceAccess({
    'analytics-dashboard': ['analyst', 'admin', 'super_admin'],
    'user-reports': ['moderator', 'admin', 'super_admin']
});
```

### HTML Data Attributes for RBAC

```html
<!-- Element visible only to admins -->
<div data-rbac-role="admin">Admin only content</div>

<!-- Element visible to users with access to specific resource -->
<div data-rbac-resource="reports-view">Reports content</div>

<!-- Specify behavior when unauthorized -->
<button data-rbac-resource="content-delete" data-rbac-unauthorized="disable">
    Delete Content
</button>
```

## UI Components

The authentication UI components provide ready-to-use interface elements:

```javascript
// Initialize all auth UI components on the page
CarFuseAuthUI.initializeComponents();

// Create a login form programmatically
CarFuseAuthUI.createLoginForm('#login-container', {
    showRememberMe: true,
    showForgotPassword: true,
    showRegisterLink: true,
    loginEndpoint: '/api/auth/login',
    loginRedirect: '/dashboard'
});

// Update authentication dependent UI
CarFuseAuthUI.updateAuthDependentUI();

// Show auth-related messages
CarFuseAuthUI.showMessage('success', 'Operation completed successfully');
```

### Auth UI HTML Components

```html
<!-- Login Form -->
<form class="cf-auth-form" data-form-type="login" data-redirect="/dashboard">
    <div class="cf-auth-field">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" required>
    </div>
    <div class="cf-auth-field">
        <label for="password">Password</label>
        <input type="password" name="password" id="password" required>
    </div>
    <button type="submit" class="cf-auth-button">Login</button>
</form>

<!-- User Profile Display -->
<div class="cf-auth-profile" data-profile-template="detailed" data-show-avatar="true"></div>

<!-- Conditional Display Elements -->
<div data-cf-auth-logged-in>
    This is only visible when logged in
</div>
<div data-cf-auth-logged-out>
    This is only visible when logged out
</div>

<!-- Logout button -->
<button data-cf-auth-logout>Logout</button>
```

## Toast Notifications

The toast system provides user feedback for auth actions:

```javascript
// Show success notification
CarFuseToast.success('Login successful!');

// Show error notification
CarFuseToast.error('Authentication failed');

// Show info notification
CarFuseToast.info('Please log in to continue');

// Show warning notification
CarFuseToast.warning('Your session is about to expire');

// Show with custom options
CarFuseToast.show(
    'Custom message', 
    'Custom Title', 
    'info', 
    { closeTime: 10000, position: 'top-center' }
);

// Configure toast system
CarFuseToast.configure({
    position: 'bottom-right',
    autoClose: true,
    closeTime: 5000,
    animations: true,
    pauseOnHover: true
});
```

## Alpine.js Integration

The authentication system integrates with Alpine.js:

```html
<!-- Alpine.js Auth State Component -->
<div x-data="authState()">
    <div x-show="isAuthenticated">
        Welcome, <span x-text="username"></span>!
        <button @click="logout">Logout</button>
    </div>
    <div x-show="!isAuthenticated">
        Please <a href="/login">login</a> to continue.
    </div>
</div>

<!-- Auth Component -->
<div x-data="CarFuseAlpine.auth">
    <!-- Login Form -->
    <form @submit.prevent="login" x-show="!isLoggedIn">
        <input type="email" name="email" placeholder="Email">
        <input type="password" name="password" placeholder="Password">
        <button type="submit" :disabled="loading">
            <span x-show="loading">Loading...</span>
            <span x-show="!loading">Login</span>
        </button>
        <div class="error" x-text="error" x-show="error"></div>
    </form>
    
    <!-- User Info -->
    <div x-show="isLoggedIn">
        <p>Welcome, <span x-text="user?.name || userId"></span>!</p>
        <button @click="logout" :disabled="loading">Logout</button>
    </div>
</div>

<!-- Alpine Data Attributes -->
<div x-show="$store.auth.isAuthenticated">
    Authenticated content using global store
</div>

<!-- RBAC Directives -->
<button x-auth-role="admin">Admin Only Button</button>
<div x-auth-permission="edit_content">Edit Content Access</div>
<section x-auth-access="reports-view">Reports Section</section>
```

## HTMX Integration

The authentication system integrates with HTMX for authenticated AJAX requests:

```html
<!-- Add auth extension to HTMX -->
<body hx-ext="auth">
    <!-- Authenticated request -->
    <button hx-get="/api/protected-data" 
            hx-trigger="click"
            hx-target="#result">
        Load Protected Data
    </button>
    
    <div id="result"></div>
</body>
```

### Server-side Auth Errors with HTMX

When the server returns 401 or 403 responses:

1. For 401 (Unauthorized): The auth extension automatically attempts to refresh the token and retry the request.
2. For 403 (Forbidden): The extension displays a toast notification about insufficient permissions.

All authentication headers are automatically added to every HTMX request.
