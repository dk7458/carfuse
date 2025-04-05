# CarFuse Alpine.js Component Guide

This guide provides best practices for developing Alpine.js components using the CarFuse standardized patterns.

## Table of Contents

1. [Component Structure](#component-structure)
2. [Standardized Patterns](#standardized-patterns)
3. [Error Handling](#error-handling)
4. [State Management](#state-management)
5. [Component Communication](#component-communication)
6. [Integration with CarFuse](#integration-with-carfuse)

## Component Structure

Alpine.js components in the CarFuse system should follow a standard structure:

```javascript
window.CarFuseAlpine.createComponent('componentName', (params = {}) => {
    return {
        // Component state properties
        loading: false,
        error: null,
        data: null,
        
        // Configuration (merge defaults with params)
        config: {
            option1: params.option1 || 'default',
            option2: params.option2 || false,
            ...params
        },
        
        // Lifecycle: Initialize (called by Alpine)
        initialize() {
            // Initialization logic
        },
        
        // Component methods
        async loadData() {
            return this.withLoading(async () => {
                const response = await fetch('/api/data');
                const data = await response.json();
                this.data = data;
                return data;
            });
        },
        
        // Example of other methods...
        someMethod() {
            // Implementation
        }
    };
});
```

## Standardized Patterns

### 1. Use the Base Component Features

Every Alpine component created with `CarFuseAlpine.createComponent` automatically inherits these standard features:

- **Loading State Management**: `loading` property and `withLoading()` method
- **Error Handling**: `error` property, `handleError()`, and `clearError()` methods
- **Initialization**: Standard `init()` that handles async initialization safely

### 2. Use Standard HTML Structure

```html
<div x-data="componentName({ option1: 'value' })" x-init="initialize">
    <!-- Loading state -->
    <div x-show="loading">Loading...</div>
    
    <!-- Error state -->
    <div x-show="error" class="error" x-text="error"></div>
    
    <!-- Content (only shown when not loading and no errors) -->
    <div x-show="!loading && !error">
        <!-- Component content -->
    </div>
</div>
```

## Error Handling

Use the built-in error handling patterns for consistent error management:

```javascript
// Within a component
try {
    // Risky operation
} catch (error) {
    this.handleError(error, 'custom context');
}

// For async operations, use withLoading
await this.withLoading(async () => {
    // Async operations that might fail
    const result = await riskyOperation();
    return result;
}, 'optional context message');
```

## State Management

### Local State

```javascript
// Update component state
this.updateState({
    count: this.count + 1,
    lastUpdated: new Date()
});
```

### Global Alpine Store

For shared state between components, use Alpine stores:

```javascript
// Define store
Alpine.store('appState', {
    theme: 'light',
    user: null,
    setTheme(theme) {
        this.theme = theme;
    }
});

// In component
Alpine.store('appState').setTheme('dark');
```

## Component Communication

### Event-based Communication

```javascript
// Emit events
this.$dispatch('custom-event', { data: this.data });

// Listen for events
<div @custom-event="handleCustomEvent($event.detail)"></div>
```

### Direct Component Access

```javascript
// From component method
this.$refs.otherComponent.__x.getUnobservedData().someMethod();
```

## Integration with CarFuse

### Using CarFuse Services

```javascript
// Access event bus
CarFuse.eventBus.on('global-event', data => {
    this.updateState({ 
        globalData: data 
    });
});

// Publish to event bus
CarFuse.eventBus.emit('component-event', { 
    id: this.$id,
    action: 'updated' 
});
```

### Accessing CarFuse Components

```javascript
// Use modal component
CarFuse.modal.openById('my-modal');

// Use toast notifications
window.dispatchEvent(new CustomEvent('show-toast', {
    detail: {
        title: 'Success',
        message: 'Operation completed',
        type: 'success'
    }
}));
```

## Examples

### Basic Data Display Component

```html
<div x-data="dataDisplay({ endpoint: '/api/users' })">
    <button @click="loadData" :disabled="loading">
        <span x-show="loading">Loading...</span>
        <span x-show="!loading">Load Data</span>
    </button>
    
    <div x-show="error" class="error" x-text="error"></div>
    
    <ul x-show="data && data.length">
        <template x-for="item in data" :key="item.id">
            <li x-text="item.name"></li>
        </template>
    </ul>
</div>

<script>
CarFuseAlpine.createComponent('dataDisplay', (params = {}) => {
    return {
        data: null,
        
        config: {
            endpoint: params.endpoint || '/api/data',
            autoLoad: params.autoLoad || false
        },
        
        initialize() {
            if (this.config.autoLoad) {
                this.loadData();
            }
        },
        
        async loadData() {
            return this.withLoading(async () => {
                const response = await fetch(this.config.endpoint);
                
                if (!response.ok) {
                    throw new Error(`Failed to load data: ${response.status}`);
                }
                
                const data = await response.json();
                this.data = data;
                return data;
            });
        }
    };
});
</script>
```

### Interactive Form Component

```html
<form x-data="contactForm" @submit.prevent="submit">
    <div x-show="success" class="success-message">
        Thank you for your message!
    </div>
    
    <div x-show="!success">
        <div class="form-group">
            <label for="name">Name</label>
            <input 
                id="name" 
                type="text" 
                x-model="formData.name"
                @blur="handleBlur('name')"
                :class="{'error': errors.name && touched.name}"
            >
            <p x-show="errors.name && touched.name" x-text="errors.name" class="error-text"></p>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input 
                id="email" 
                type="email" 
                x-model="formData.email"
                @blur="handleBlur('email')"
                :class="{'error': errors.email && touched.email}"
            >
            <p x-show="errors.email && touched.email" x-text="errors.email" class="error-text"></p>
        </div>
        
        <div class="form-group">
            <label for="message">Message</label>
            <textarea 
                id="message" 
                x-model="formData.message"
                @blur="handleBlur('message')"
                :class="{'error': errors.message && touched.message}"
            ></textarea>
            <p x-show="errors.message && touched.message" x-text="errors.message" class="error-text"></p>
        </div>
        
        <button type="submit" :disabled="loading">
            <span x-show="loading">Sending...</span>
            <span x-show="!loading">Send Message</span>
        </button>
    </div>
</form>

<script>
CarFuseAlpine.createComponent('contactForm', () => {
    return {
        formData: {
            name: '',
            email: '',
            message: ''
        },
        errors: {},
        touched: {},
        success: false,
        
        // Validation rules
        rules: {
            name: 'required|min:2',
            email: 'required|email',
            message: 'required|min:10'
        },
        
        initialize() {
            this.debouncedValidate = this.debounce(this.validate.bind(this), 300);
        },
        
        handleBlur(field) {
            this.touched[field] = true;
            this.validate(field);
        },
        
        validate(field = null) {
            // Implementation from the form component
            // This would validate the form based on the rules
        },
        
        async submit() {
            if (!this.validate()) {
                return false;
            }
            
            return this.withLoading(async () => {
                const response = await fetch('/api/contact', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(this.formData)
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    if (data.errors) {
                        this.errors = data.errors;
                        return false;
                    }
                    throw new Error(data.message || 'Failed to submit form');
                }
                
                this.success = true;
                return true;
            });
        },
        
        debounce(fn, delay) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => fn.apply(this, args), delay);
            };
        }
    };
});
</script>
```

By following these patterns, your Alpine.js components will be consistent, maintainable, and integrate seamlessly with the rest of the CarFuse system.
