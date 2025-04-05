# CarFuse Component Style Guide

This style guide provides standards, patterns, and best practices for developing components in the CarFuse ecosystem.

## Table of Contents

1. [Component Architecture](#component-architecture)
2. [Lifecycle Hooks](#lifecycle-hooks)
3. [State Management](#state-management)
4. [Event Handling](#event-handling)
5. [Dependency Management](#dependency-management)
6. [Error Handling](#error-handling)
7. [Testing Components](#testing-components)
8. [Component Documentation](#component-documentation)

## Component Architecture

All components should follow the standardized architecture for consistency and maintainability.

### Standard Component Structure

```javascript
CarFuse.createComponent('componentName', {
    // Component dependencies
    dependencies: ['core', 'events'],
    
    // Initial state (object or function returning object)
    state: {
        initialized: false,
        data: null
    },
    
    // Props definition with validation
    props: {
        option1: CarFuse.utils.PropValidator.types.string({ default: 'default' }),
        option2: CarFuse.utils.PropValidator.types.number({ required: true }),
        variant: CarFuse.utils.PropValidator.types.oneOf(['primary', 'secondary'])
    },
    
    // Lifecycle: Prepare (sync)
    prepare() {
        // Synchronous preparation
    },
    
    // Lifecycle: Initialize (async)
    initialize() {
        // Initialization logic
        return this.fetchInitialData();
    },
    
    // Lifecycle: Mount
    mount(elements) {
        elements.forEach(el => {
            this.renderElement(el);
        });
    },
    
    // Lifecycle: Render
    render() {
        this.elements.forEach(el => {
            this.renderElement(el);
        });
    },
    
    // Lifecycle: Destroy
    destroy() {
        // Cleanup logic
    },
    
    // Custom methods
    async fetchInitialData() {
        // Implementation
    },
    
    renderElement(el) {
        // Element-specific rendering
    }
});
```

### Component Registration

Components should be registered using the `createComponent` method, which handles the proper extension of the BaseComponent class and dependency registration.

## Lifecycle Hooks

Components follow a standard lifecycle:

1. **Prepare**: Synchronous preparation before initialization
2. **Initialize**: Main initialization logic (can return a Promise)
3. **Mount**: Connect to DOM elements
4. **Render**: Update DOM elements based on state 
5. **Destroy**: Clean up resources

### Lifecyle Implementation Example

```javascript
// Prepare (always synchronous)
prepare() {
    // Setup initial configuration
    this.config = {
        ...this.config,
        // Component-specific defaults
    };
    
    // Initialize collections
    this.items = [];
}

// Initialize (can be async)
initialize() {
    return fetch('/api/data')
        .then(response => response.json())
        .then(data => {
            this.state.data = data;
        });
}

// Mount to DOM elements
mount(elements) {
    elements.forEach(element => {
        // Parse element-specific configuration
        const config = JSON.parse(element.dataset.config || '{}');
        this.setProps(config);
        
        // Store reference to element
        element.carfuseComponent = this;
        
        // Initial render
        this.renderElement(element);
        
        // Setup element-specific event listeners
        this.setupElementEvents(element);
    });
}
```

## State Management

State should be managed through the `update` method to ensure proper rendering and event emission:

```javascript
// Update component state
this.update({
    loading: false,
    data: response.data,
    lastUpdated: new Date()
});
```

For more complex state needs, use the state manager utility:

```javascript
// Create a state manager
this.stateManager = CarFuse.utils.createStateManager(
    {
        items: [],
        selectedId: null,
        loading: false
    },
    (newState, prevState) => {
        // State change handler
        this.render();
    }
);

// Get state
const currentState = this.stateManager.getState();

// Update state
this.stateManager.setState({
    selectedId: itemId,
    loading: false
});

// Reset to initial state
this.stateManager.resetState();
```

## Event Handling

Components should use the standardized event system:

```javascript
// Component event handling
this.on('click', '.button', (event, element) => {
    // Handle click on .button elements
});

// Global event emission
this.emit('itemSelected', { id: selectedId });

// Using EventBus for cross-component communication
CarFuse.eventBus.on('global-theme-change', (theme) => {
    this.update({ theme });
    this.render();
});
```

## Dependency Management

Components should declare their dependencies:

```javascript
dependencies: ['core', 'utils', 'events']
```

These dependencies will be automatically checked and loaded before initialization.

## Error Handling

Components should use structured error handling:

```javascript
try {
    // Operation that might fail
} catch (error) {
    this.logError('Failed to perform operation', error);
    
    // Update component state to show error
    this.update({ error: error.message, loading: false });
}
```

## Testing Components

Each component should have corresponding tests that verify:

1. Proper initialization
2. State management
3. Event handling
4. Rendering accuracy
5. Error handling

Example test structure:

```javascript
describe('MyComponent', () => {
    let component;
    
    beforeEach(() => {
        component = new CarFuse.BaseComponent('testComponent');
    });
    
    test('should initialize correctly', async () => {
        await component.init();
        expect(component.initialized).toBeTruthy();
    });
    
    test('should update state correctly', () => {
        component.update({ test: true });
        expect(component.state.test).toBe(true);
    });
    
    // More tests...
});
```

## Component Documentation

Each component should include clear documentation:

1. Purpose and usage
2. Available props/options
3. Events emitted
4. Dependencies
5. Examples

Documentation should be provided as JSDoc comments in the component file and as a separate markdown document in the docs folder.
