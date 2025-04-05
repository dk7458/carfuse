# Login System

*Last updated: 2023-11-15*

This document explains the login system components and implementation in the CarFuse framework.

## Table of Contents
- [Login Components](#login-components)
- [Authentication Flow](#authentication-flow)
- [Login Form Implementation](#login-form-implementation)
- [Event Handling](#event-handling)
- [Advanced Login Features](#advanced-login-features)
- [Form Validation](#form-validation)
- [Testing Login Functionality](#testing-login-functionality)
- [Related Documentation](#related-documentation)

## Login Components

The CarFuse login system provides several pre-built components:

1. **Login Form** - A complete login form with validation
2. **Auth Helper** - Core authentication functionality 
3. **Auth Events** - Login lifecycle events
4. **Login UI** - Ready-to-use UI components
5. **Toast Notifications** - User feedback during login process

## Authentication Flow

The standard authentication flow follows these steps:

1. User submits credentials via login form
2. Client-side validation is performed
3. Credentials are sent to the server via AJAX or form submission
4. Server validates credentials against the user database
5. On success:
   - User session is created
   - Authentication events are triggered
   - Success feedback is shown
   - User is redirected to the dashboard/home page
6. On failure:
   - Error feedback is shown
   - Login form remains active
   - Failed attempt is logged

## Login Form Implementation

### Basic Login Form

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
    <div class="cf-auth-options">
        <label>
            <input type="checkbox" name="remember_me">
            Remember me
        </label>
        <a href="/forgot-password">Forgot password?</a>
    </div>
    <button type="submit" class="cf-auth-button">Login</button>
</form>
```

### Creating Login Form Programmatically

```javascript
// Create a login form programmatically
CarFuseAuthUI.createLoginForm('#login-container', {
    showRememberMe: true,
    showForgotPassword: true,
    showRegisterLink: true,
    loginEndpoint: '/api/auth/login',
    loginRedirect: '/dashboard'
});
```

### Using Alpine.js Login Component

```html
<div x-data="CarFuseAlpine.auth">
    <!-- Login Form -->
    <form @submit.prevent="login" x-show="!isLoggedIn">
        <input type="email" name="email" x-model="credentials.email" placeholder="Email">
        <input type="password" name="password" x-model="credentials.password" placeholder="Password">
        
        <div class="error" x-text="error" x-show="error"></div>
        
        <button type="submit" :disabled="loading">
            <span x-show="loading">Loading...</span>
            <span x-show="!loading">Login</span>
        </button>
    </form>
    
    <!-- User Info -->
    <div x-show="isLoggedIn">
        <p>Welcome, <span x-text="user?.name || userId"></span>!</p>
        <button @click="logout" :disabled="loading">Logout</button>
    </div>
</div>
```

## Event Handling

### Login Events

```javascript
// Listen for login success
document.addEventListener('auth:login-success', event => {
    console.log('Login successful:', event.detail.userId);
    console.log('User role:', event.detail.userRole);
});

// Listen for login failure
document.addEventListener('auth:login-failure', event => {
    console.log('Login failed:', event.detail.error);
});

// Listen for logout
document.addEventListener('auth:logout', () => {
    console.log('User logged out');
});
```

### Using the Events API

```javascript
// Using the structured events API
CarFuseEvents.Auth.onLoginSuccess(event => {
    console.log('User logged in:', event.detail);
});

CarFuseEvents.Auth.onLoginFailure(event => {
    console.log('Login failed:', event.detail.error);
});

CarFuseEvents.Auth.onLogoutSuccess(() => {
    console.log('User logged out');
});
```

## Advanced Login Features

### Social Login

```html
<div class="cf-auth-social-login">
    <button type="button" data-auth-provider="google" class="cf-auth-social-button">
        <i class="icon-google"></i> Log in with Google
    </button>
    
    <button type="button" data-auth-provider="facebook" class="cf-auth-social-button">
        <i class="icon-facebook"></i> Log in with Facebook
    </button>
</div>
```

### Multi-Factor Authentication

```javascript
// After initial authentication, check if MFA is required
auth.login('user@example.com', 'password')
    .then(response => {
        if (response.requiresMfa) {
            // Show MFA form
            showMfaForm(response.mfaOptions);
        } else {
            // Normal login success
            redirectToDashboard();
        }
    });

// Verify MFA code
auth.verifyMfa('123456')
    .then(response => {
        if (response.success) {
            // MFA verification successful
            redirectToDashboard();
        } else {
            // MFA verification failed
            showError('Invalid verification code');
        }
    });
```

### Remember Me Functionality

```html
<div class="cf-auth-remember">
    <label>
        <input type="checkbox" name="remember_me" data-auth-remember>
        Remember me
    </label>
</div>
```

```javascript
// Handle remember me in login
auth.login('user@example.com', 'password', {
    rememberMe: true
}).then(response => {
    // User will be remembered for the duration specified in config
});
```

## Form Validation

The login form includes built-in validation:

```html
<input type="email" 
       name="email" 
       data-validate="required|email" 
       data-error-message="Please enter a valid email address">

<input type="password" 
       name="password" 
       data-validate="required|min:8" 
       data-error-message="Password must be at least 8 characters">
```

Custom validation can be added:

```javascript
CarFuse.forms.validation.addRule('custom-email-domain', value => {
    // Check if email ends with company domain
    return value.endsWith('@company.com');
}, 'Must use a company email address');

// Then use in the form
<input type="email" data-validate="required|email|custom-email-domain">
```

## Testing Login Functionality

### Integration Testing

```php
public function testSuccessfulLogin()
{
    // Create a test user
    $user = [
        'email' => 'test@example.com',
        'password' => 'password123'
    ];
    
    // Send login request
    $response = $this->postJson('/api/auth/login', $user);
    
    // Assert successful login
    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'userId' => $user->id
             ]);
             
    // Assert that user is actually authenticated
    $this->assertTrue(SecurityService::isAuthenticated());
}

public function testFailedLogin()
{
    // Send login request with invalid credentials
    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password'
    ]);
    
    // Assert failed login
    $response->assertStatus(401)
             ->assertJson([
                 'success' => false,
                 'message' => 'Invalid credentials'
             ]);
             
    // Assert that user is not authenticated
    $this->assertFalse(SecurityService::isAuthenticated());
}
```

### End-to-End Testing

```javascript
describe('Login Form', () => {
    it('should log in successfully with valid credentials', () => {
        cy.visit('/login');
        
        cy.get('input[name="email"]').type('test@example.com');
        cy.get('input[name="password"]').type('password123');
        cy.get('form.cf-auth-form').submit();
        
        // Assert redirect to dashboard
        cy.url().should('include', '/dashboard');
        
        // Assert user is logged in
        cy.window().its('CarFuseAuthSystem.isAuthenticated').should('be.true');
    });
    
    it('should show error with invalid credentials', () => {
        cy.visit('/login');
        
        cy.get('input[name="email"]').type('test@example.com');
        cy.get('input[name="password"]').type('wrong-password');
        cy.get('form.cf-auth-form').submit();
        
        // Assert error message
        cy.get('.cf-auth-error').should('be.visible');
        cy.get('.cf-auth-error').should('contain', 'Invalid credentials');
        
        // Assert URL remains on login page
        cy.url().should('include', '/login');
    });
});
```

## Related Documentation

- [Authentication System Overview](overview.md)
- [Role-Based Access Control](rbac.md)
- [Security Authentication](../../security/authentication.md)
- [Form Validation](../forms/validation.md)
