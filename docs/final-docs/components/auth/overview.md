# Authentication System Overview

*Last updated: 2023-11-15*

This document provides an overview of the CarFuse Authentication System, which manages user identity, authentication state, and access control across the application.

## Table of Contents
- [System Architecture](#system-architecture)
- [Core Features](#core-features)
- [Integration Points](#integration-points)
- [Basic Usage](#basic-usage)
- [Configuration](#configuration)
- [Extension Points](#extension-points)
- [Related Documentation](#related-documentation)

## System Architecture

The CarFuse Authentication System is designed as a unified module that handles all authentication, authorization, and user interface concerns. It consists of several interconnected components:

![Authentication System Architecture](../../assets/auth-system-architecture.png)

### Key Components

1. **AuthSystem** - Main controller for all authentication functionality
2. **AuthHelper** - Core authentication functionality
3. **RBAC** - Role-Based Access Control system
4. **Events System** - Authentication-related events
5. **UI Components** - Ready-to-use interface elements

## Core Features

The Authentication System provides the following core features:

| Feature | Description | 
|---------|-------------|
| User Authentication | Verify user identities using passwords, social login, or MFA |
| Session Management | Secure session handling with proper timeout and regeneration |
| Role-Based Access Control | Control access to resources based on user roles |
| UI Integration | Pre-built components for login, registration, and profile management |
| Event System | Authentication lifecycle events for extending functionality |
| Toast Notifications | User feedback for authentication-related actions |
| Framework Integrations | Built-in support for Alpine.js, HTMX, and other frameworks |

## Integration Points

The Authentication System integrates with other CarFuse systems:

- **Form System** - For validation of authentication forms
- **API System** - For authenticated API requests
- **Security Layer** - For CSRF protection and other security features
- **UI Component System** - For styled and consistent authentication UI elements

## Basic Usage

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

## Configuration

The Authentication System can be configured through the `config/auth.php` file:

```php
return [
    'providers' => [
        'users' => [
            'driver' => 'database',
            'table' => 'users',
            'identity_column' => 'email'
        ]
    ],
    
    'session' => [
        'lifetime' => 7200, // 2 hours
        'refresh_on_activity' => true
    ],
    
    'passwords' => [
        'min_length' => 8,
        'require_special_chars' => true,
        'require_numbers' => true
    ],
    
    'login' => [
        'throttle' => [
            'enabled' => true,
            'max_attempts' => 5,
            'lockout_time' => 300 // 5 minutes
        ],
        'remember_me' => [
            'enabled' => true,
            'lifetime' => 2592000 // 30 days
        ]
    ],
    
    'redirects' => [
        'login' => '/dashboard',
        'logout' => '/login',
        'unauthorized' => '/login'
    ]
];
```

## Extension Points

The Authentication System can be extended through:

1. **Event Listeners** - React to authentication events
2. **Custom Auth Providers** - Implement custom authentication sources
3. **UI Customization** - Style and customize authentication UI
4. **Custom Validators** - Add custom validation logic
5. **Middleware Extensions** - Add functionality to the authentication pipeline

## Related Documentation

- [Login System](login-system.md)
- [Role-Based Access Control](rbac.md)
- [Security Best Practices](../../security/best-practices.md)
- [API Authentication](../../api/endpoints/auth.md)
- [Security Authentication](../../security/authentication.md)
