# Button Components

*Last updated: 2023-11-15*

This document details the button components available in the CarFuse UI system and provides guidance on their proper use.

## Table of Contents
- [Button Variants](#button-variants)
- [Button Sizes](#button-sizes)
- [Button States](#button-states)
- [Button Content](#button-content)
- [Button Groups](#button-groups)
- [Accessibility](#accessibility)
- [JavaScript API](#javascript-api)
- [Related Documentation](#related-documentation)

## Button Variants

CarFuse provides several button variants for different purposes:

### Primary Button
The primary action button. Use for the main action in a form or section.

```html
<button class="btn btn-primary">
  <span class="btn-text">Submit</span>
</button>
```

### Secondary Button
Used for secondary actions that should be less visually prominent.

```html
<button class="btn btn-secondary">
  <span class="btn-text">Cancel</span>
</button>
```

### Outline Button
A less visually prominent alternative that still maintains clear affordance.

```html
<button class="btn btn-outline">
  <span class="btn-text">View Details</span>
</button>
```

### Text Button
The least visually prominent button. Used for low-emphasis actions.

```html
<button class="btn btn-text">
  <span class="btn-text">Learn More</span>
</button>
```

### Icon Button
Used for actions that can be clearly represented by an icon.

```html
<button class="btn-icon" aria-label="Delete">
  <i class="icon-trash"></i>
</button>
```

### Danger Button
Used for destructive actions like delete or remove.

```html
<button class="btn btn-danger">
  <span class="btn-text">Delete Account</span>
</button>
```

### Success Button
Used for positive actions like confirm or approve.

```html
<button class="btn btn-success">
  <span class="btn-text">Approve</span>
</button>
```

## Button Sizes

Buttons are available in multiple sizes:

### Small Button
```html
<button class="btn btn-primary btn-sm">
  <span class="btn-text">Small</span>
</button>
```

### Default (Medium) Button
```html
<button class="btn btn-primary">
  <span class="btn-text">Default</span>
</button>
```

### Large Button
```html
<button class="btn btn-primary btn-lg">
  <span class="btn-text">Large</span>
</button>
```

### Full Width Button
Expands to the full width of its container.

```html
<button class="btn btn-primary btn-block">
  <span class="btn-text">Full Width</span>
</button>
```

## Button States

Buttons have several possible states:

### Default State
The normal appearance of a button.

### Hover State
Activated when the user hovers over a button.

### Focus State
Activated when the button receives focus, typically via keyboard navigation.

### Active State
The appearance when the button is being pressed.

### Disabled State
Used when the action is unavailable.

```html
<button class="btn btn-primary" disabled>
  <span class="btn-text">Disabled</span>
</button>
```

### Loading State
Indicates that the action is in progress.

```html
<button class="btn btn-primary loading">
  <span class="btn-spinner">
    <div class="spinner"></div>
  </span>
  <span class="btn-text">Processing...</span>
</button>
```

## Button Content

Buttons can contain various types of content:

### Text with Icon

```html
<button class="btn btn-primary">
  <span class="btn-icon">
    <i class="icon-plus"></i>
  </span>
  <span class="btn-text">Add Item</span>
</button>
```

### Icon with Text

```html
<button class="btn btn-primary">
  <span class="btn-text">Next</span>
  <span class="btn-icon">
    <i class="icon-arrow-right"></i>
  </span>
</button>
```

### Badge with Text

```html
<button class="btn btn-outline">
  <span class="btn-text">Notifications</span>
  <span class="btn-badge">5</span>
</button>
```

## Button Groups

Group related buttons together:

### Standard Button Group

```html
<div class="btn-group">
  <button class="btn btn-outline">Left</button>
  <button class="btn btn-outline">Middle</button>
  <button class="btn btn-outline">Right</button>
</div>
```

### Button Group with Active Selection

```html
<div class="btn-group" role="group" aria-label="View options">
  <button class="btn btn-outline active">Day</button>
  <button class="btn btn-outline">Week</button>
  <button class="btn btn-outline">Month</button>
</div>
```

### Vertical Button Group

```html
<div class="btn-group btn-group-vertical">
  <button class="btn btn-outline">Top</button>
  <button class="btn btn-outline">Middle</button>
  <button class="btn btn-outline">Bottom</button>
</div>
```

### Mixed Size Button Group

```html
<div class="btn-group">
  <button class="btn btn-primary">Primary</button>
  <button class="btn btn-outline">Secondary</button>
  <button class="btn-icon" aria-label="Menu">
    <i class="icon-menu"></i>
  </button>
</div>
```

## Accessibility

Ensure buttons are accessible:

### Proper Element Usage

- Use `<button>` for actions that don't navigate to a new page
- Use `<a>` with `role="button"` only when navigation is required

### Keyboard Support

- Ensure all buttons are keyboard accessible
- Test with keyboard navigation (Tab to focus, Space/Enter to activate)

### ARIA Attributes

For icon buttons without visible text:

```html
<button class="btn-icon" aria-label="Delete item">
  <i class="icon-trash"></i>
</button>
```

For buttons with additional context:

```html
<button class="btn btn-danger" aria-describedby="delete-description">
  <span class="btn-text">Delete</span>
</button>
<div id="delete-description" class="sr-only">
  Permanently delete this item. This action cannot be undone.
</div>
```

### Focus Indicators

Ensure buttons have visible focus indicators:

```css
.btn:focus {
  outline: 2px solid var(--color-focus);
  outline-offset: 2px;
}
```

## JavaScript API

Buttons can be enhanced with JavaScript functionality:

### Loading State Management

```javascript
const button = document.querySelector('#submit-button');

// Set loading state
button.classList.add('loading');
button.disabled = true;

// Reset after action completes
function resetButton() {
  button.classList.remove('loading');
  button.disabled = false;
}

// Example usage
submitForm().then(resetButton).catch(resetButton);
```

### Using the Button Component API

```javascript
// Get the button component
const buttonComponent = CarFuse.getComponent('button');

// Set loading state for a button
buttonComponent.setLoading('#submit-button', true);

// Toggle active state
buttonComponent.toggleActive('#option-button');

// Listen for button events
buttonComponent.on('click', '#submit-button', (event) => {
  // Handle button click
});
```

### Alpine.js Integration

```html
<button 
  class="btn btn-primary" 
  x-data="{ loading: false }" 
  x-bind:class="{ 'loading': loading }"
  x-bind:disabled="loading"
  @click="loading = true; submitForm().finally(() => loading = false)">
  
  <span class="btn-spinner" x-show="loading">
    <div class="spinner"></div>
  </span>
  
  <span class="btn-text" x-text="loading ? 'Processing...' : 'Submit'"></span>
</button>
```

## Related Documentation

- [UI Component Overview](overview.md)
- [Form Components](../forms/overview.md)
- [Card Components](cards.md)
- [Color System](../theme/colors.md)
- [Accessibility Guidelines](../../development/standards/accessibility.md)
