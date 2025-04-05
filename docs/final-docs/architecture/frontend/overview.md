# Frontend Architecture Overview

*Last updated: 2023-11-15*

This document provides a comprehensive overview of the CarFuse frontend architecture, explaining its structure, key components, and design principles.

## Table of Contents
- [Architecture Principles](#architecture-principles)
- [Component Architecture](#component-architecture)
- [State Management](#state-management)
- [Module Structure](#module-structure)
- [Directory Organization](#directory-organization)
- [Build and Deployment](#build-and-deployment)
- [Framework Integration](#framework-integration)
- [Related Documentation](#related-documentation)

## Architecture Principles

The CarFuse frontend is built on the following core principles:

1. **Component-Based Design**: Modular, reusable components form the foundation
2. **Progressive Enhancement**: Core functionality works without JavaScript
3. **Responsive by Default**: All interfaces adapt to different screen sizes
4. **Accessibility First**: WCAG AA compliance throughout the application
5. **Performance Optimized**: Fast initial load and interaction times
6. **Framework Agnostic Core**: Core functionality works with any framework
7. **Standardized Patterns**: Consistent conventions across the codebase

## Component Architecture

### Component Layers

The UI components in CarFuse follow a layered architecture:

1. **Base Components**: Low-level, framework-agnostic components (buttons, inputs, etc.)
2. **Composite Components**: Components combining base components (forms, cards, etc.)
3. **Feature Components**: Business-specific components (booking widgets, vehicle displays)
4. **Page Components**: Full page layouts combining multiple components
5. **App Shell**: Core application wrapper providing global services and layout

### Component System

The component system provides several mechanisms for component creation and management:

```javascript
// Component definition
CarFuse.createComponent('componentName', {
    // Component dependencies
    dependencies: ['core', 'events'],
    
    // Component state
    state: {
        initialized: false,
        data: null
    },
    
    // Component lifecycle methods
    prepare() { /* Synchronous preparation */ },
    initialize() { /* Async initialization */ },
    mount(elements) { /* Connect to DOM elements */ },
    render() { /* Update DOM elements */ },
    destroy() { /* Cleanup */ },
    
    // Custom methods
    customMethod() { /* Implementation */ }
});

// Component usage
const elements = document.querySelectorAll('[data-component="componentName"]');
CarFuse.getComponent('componentName').mount(elements);
```

### Component Registry

Components are managed through a central registry that handles dependencies and initialization:

```javascript
// Initialize all components on a page
CarFuse.initComponents();

// Initialize a specific component
CarFuse.initComponent('dropdown');

// Get a reference to a component
const buttonComponent = CarFuse.getComponent('button');
```

## State Management

State management in CarFuse follows these patterns:

### Component-level State

Each component manages its own state:

```javascript
// Component with state management
CarFuse.createComponent('counter', {
    state: {
        count: 0
    },
    
    increment() {
        // Update state and trigger render
        this.update({ count: this.state.count + 1 });
    },
    
    decrement() {
        this.update({ count: this.state.count - 1 });
    },
    
    render() {
        // Update DOM based on state
        this.elements.forEach(el => {
            el.querySelector('.counter-value').textContent = this.state.count;
        });
    }
});
```

### Application-level State

For global state, the application uses a central store:

```javascript
// Create a store
const store = CarFuse.createStore({
    // Initial state
    state: {
        user: null,
        theme: 'light',
        cart: []
    },
    
    // Actions
    actions: {
        setUser(state, user) {
            return { ...state, user };
        },
        toggleTheme(state) {
            const theme = state.theme === 'light' ? 'dark' : 'light';
            return { ...state, theme };
        },
        addToCart(state, item) {
            return { 
                ...state, 
                cart: [...state.cart, item]
            };
        }
    }
});

// Subscribe to state changes
store.subscribe((newState, prevState) => {
    if (newState.theme !== prevState.theme) {
        applyTheme(newState.theme);
    }
});

// Dispatch actions
store.dispatch('setUser', { id: 1, name: 'John' });
store.dispatch('addToCart', { id: 123, name: 'Product', price: 99.99 });
```

## Module Structure

The frontend code is organized into modules:

### Core Modules

- **Core**: Base functionality, utilities, and helpers
- **Components**: UI component implementations
- **State**: State management utilities
- **Events**: Event management system
- **Forms**: Form handling and validation
- **Auth**: Authentication and authorization
- **API**: API communication layer
- **Router**: Client-side routing (for SPAs)

### Feature Modules

- **Booking**: Vehicle booking functionality
- **Vehicles**: Vehicle browsing and filtering
- **Account**: User account management
- **Payments**: Payment processing
- **Admin**: Administrative interface

## Directory Organization

The frontend codebase follows this directory structure:

```
/src
  /core           # Core functionality
  /components     # UI components
    /base         # Base components
    /composite    # Composite components
    /feature      # Feature-specific components
  /styles         # Global styles
    /themes       # Theme definitions
    /utilities    # Utility classes
  /utils          # Utility functions
  /services       # Service classes (API, Auth, etc.)
  /features       # Feature modules
  /pages          # Page components
  /assets         # Static assets
  /config         # Configuration files
  /types          # Type definitions
```

## Build and Deployment

The frontend build process:

1. **Development Build**: Fast builds with source maps for local development
2. **Production Build**: Optimized, minified builds for deployment
3. **Component Library Build**: Standalone package for component usage

Key build features:

- **Code Splitting**: Automatically splits code for optimal loading
- **Tree Shaking**: Removes unused code
- **Asset Optimization**: Compresses and optimizes images and other assets
- **CSS Processing**: Processes and minimizes CSS
- **Bundle Analysis**: Visualizes bundle sizes for optimization

## Framework Integration

While the core components are framework-agnostic, CarFuse provides integrations with popular frameworks:

### Alpine.js Integration

```html
<div x-data="CarFuseAlpine.dropdown({ placement: 'bottom' })">
  <button @click="toggle" x-text="isOpen ? 'Close' : 'Open'"></button>
  <div x-show="isOpen" @click.away="close">
    Dropdown content
  </div>
</div>
```

### HTMX Integration

```html
<div data-component="form">
  <form hx-post="/api/submit" hx-swap="outerHTML">
    <!-- Form fields -->
    <button type="submit">Submit</button>
  </form>
</div>
```

### React Integration

```jsx
import { Button, Form, Input } from '@carfuse/react';

function MyComponent() {
  return (
    <Form onSubmit={handleSubmit}>
      <Input name="email" label="Email" validation="required|email" />
      <Button type="submit" variant="primary">Submit</Button>
    </Form>
  );
}
```

## Related Documentation

- [Component System](component-system.md)
- [State Management](state-management.md)
- [UI Components](../../components/ui/overview.md)
- [Theme System](../../components/theme/overview.md)
- [Authentication System](../../components/auth/overview.md)
- [Forms System](../../components/forms/overview.md)
