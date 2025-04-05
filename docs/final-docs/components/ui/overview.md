# UI Component System

*Last updated: 2023-11-15*

This document provides an overview of the CarFuse UI component system, which offers standardized, accessible, and theme-aware user interface elements.

## Table of Contents
- [Component System Architecture](#component-system-architecture)
- [Core Principles](#core-principles)
- [Component Categories](#component-categories)
- [Using Components](#using-components)
- [Extending Components](#extending-components)
- [Theming Components](#theming-components)
- [Related Documentation](#related-documentation)

## Component System Architecture

The CarFuse UI component system follows a modular architecture:

1. **Base Layer**: Core functionality and shared behaviors
2. **Design System Layer**: Theme implementation and styling
3. **Component Layer**: Specific component implementations
4. **Integration Layer**: Framework-specific adaptors (Alpine.js, HTMX, etc.)

This layered approach ensures components remain consistent while allowing for customization and framework integration.

## Core Principles

Our UI components adhere to the following principles:

1. **Accessibility First**: All components meet or exceed WCAG AA standards
2. **Responsive Design**: Components adapt to different screen sizes and devices
3. **Theme Awareness**: Components automatically adapt to light, dark, and custom themes
4. **Progressive Enhancement**: Core functionality works without JavaScript
5. **Performance Focus**: Components are optimized for minimal payload and runtime cost
6. **Framework Agnostic**: Core components work with any frontend approach
7. **Consistent API**: Components share predictable patterns and behaviors

## Component Categories

The CarFuse UI system includes these component categories:

| Category | Description | Documentation |
|----------|-------------|---------------|
| Buttons | Call-to-action and interactive controls | [Button Components](buttons.md) |
| Cards | Content containers with various layouts | [Card Components](cards.md) |
| Forms | Input elements and validation | [Form Components](../forms/overview.md) |
| Navigation | Menus, tabs, and navigation patterns | [Navigation Components](navigation.md) |
| Feedback | Alerts, toasts, and progress indicators | [Feedback Components](feedback.md) |
| Layout | Grids, containers, and structural elements | [Layout Components](layout.md) |
| Data Display | Tables, lists, and data visualization | [Data Components](data.md) |
| Dashboard | Specialized dashboard elements | [Dashboard Elements](dashboard.md) |

## Using Components

### HTML Approach

Most components can be used with simple HTML markup and appropriate CSS classes:

```html
<!-- Button component -->
<button class="btn btn-primary">
  <span class="btn-text">Submit</span>
</button>

<!-- Card component -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Card Title</h3>
  </div>
  <div class="card-body">
    <p>Card content goes here.</p>
  </div>
</div>
```

### JavaScript Enhancement

Some components require JavaScript for enhanced functionality:

```html
<!-- Dropdown with JavaScript enhancements -->
<div data-component="dropdown" id="my-dropdown">
  <button data-dropdown-trigger>Open Menu</button>
  <div data-dropdown-content>
    <a href="#" data-dropdown-item>Option 1</a>
    <a href="#" data-dropdown-item>Option 2</a>
    <a href="#" data-dropdown-item>Option 3</a>
  </div>
</div>

<script>
  // Initialize all components on the page
  CarFuse.initComponents();
  
  // Or initialize specific components
  CarFuse.initComponent('dropdown', '#my-dropdown');
</script>
```

### Alpine.js Integration

Components are also available as Alpine.js components:

```html
<div x-data="dropdown">
  <button @click="toggle">Open Menu</button>
  <div x-show="isOpen" @click.away="close">
    <a href="#">Option 1</a>
    <a href="#">Option 2</a>
    <a href="#">Option 3</a>
  </div>
</div>
```

## Extending Components

Components can be extended to create custom variations:

### Custom Button Example

```javascript
// Extend the button component
CarFuse.components.extend('button', 'animatedButton', {
  prepare() {
    // Call the parent component's prepare method
    this.parent.prepare.call(this);
    
    // Add custom properties
    this.animationDuration = 300;
  },
  
  mount(element) {
    // Call the parent mount method
    this.parent.mount.call(this, element);
    
    // Add custom behavior
    element.addEventListener('click', () => {
      this.animateButton(element);
    });
  },
  
  // Add custom methods
  animateButton(element) {
    element.style.transition = `transform ${this.animationDuration}ms`;
    element.style.transform = 'scale(0.95)';
    
    setTimeout(() => {
      element.style.transform = '';
    }, this.animationDuration);
  }
});

// Usage
CarFuse.initComponent('animatedButton', '.my-animated-button');
```

## Theming Components

Components automatically adapt to the current theme:

### CSS Variables

Components use CSS variables for themeable properties:

```css
.btn-primary {
  background-color: var(--color-primary);
  color: var(--color-text-inverse);
  border: 1px solid var(--color-primary-dark);
}

.dark .btn-primary {
  /* Dark theme overrides happen automatically */
}
```

### Custom Theme Example

To create a custom theme for components:

```css
/* Custom theme for a specific section */
.custom-theme {
  --color-primary: #8e44ad;
  --color-primary-light: #9b59b6;
  --color-primary-dark: #6c3483;
  
  /* Other theme variables */
}
```

```html
<div class="custom-theme">
  <!-- Components in this container will use custom theme colors -->
  <button class="btn btn-primary">Themed Button</button>
</div>
```

## Related Documentation

- [Button Components](buttons.md)
- [Card Components](cards.md)
- [Navigation Components](navigation.md)
- [Dashboard Elements](dashboard.md)
- [Theme System](../theme/overview.md)
- [Color System](../theme/colors.md)
- [Form Components](../forms/overview.md)
