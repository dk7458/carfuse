# Component Usage Guide

This guide demonstrates how to use the standardized CarFuse components.

## Button Components

### Basic Buttons

```html
<!-- Primary Button (Default Size) -->
<button class="btn btn-primary btn-md">
  <span class="btn-text">Submit</span>
</button>

<!-- Secondary Button with Icon -->
<button class="btn btn-secondary">
  <span class="btn-icon">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
    </svg>
  </span>
  <span class="btn-text">Add New</span>
</button>

<!-- Outline Button (Small) -->
<button class="btn btn-outline btn-sm">
  <span class="btn-text">Cancel</span>
</button>

<!-- Text Button -->
<button class="btn btn-text">
  <span class="btn-text">Learn More</span>
</button>

<!-- Large Button -->
<button class="btn btn-primary btn-lg">
  <span class="btn-text">Create Account</span>
</button>
```

### Button States

```html
<!-- Disabled Button -->
<button class="btn btn-primary" disabled>
  <span class="btn-text">Disabled</span>
</button>

<!-- Loading Button (applied via JS) -->
<button class="btn btn-primary loading">
  <span class="btn-spinner">
    <div class="spinner spinner-border-t h-4 w-4"></div>
  </span>
  <span class="btn-text">Loading...</span>
</button>
```

## Form Components

### Basic Form

```html
<form class="form">
  <div class="form-group">
    <label for="name" class="form-label">Name</label>
    <input type="text" id="name" class="form-input" placeholder="Enter your name">
  </div>
  
  <div class="form-group">
    <label for="email" class="form-label">Email</label>
    <input type="email" id="email" class="form-input" placeholder="Enter your email">
    <div class="form-help">We'll never share your email with anyone else.</div>
  </div>
  
  <div class="form-group">
    <label for="country" class="form-label">Country</label>
    <select id="country" class="form-select">
      <option value="">Select a country</option>
      <option value="us">United States</option>
      <option value="ca">Canada</option>
      <option value="mx">Mexico</option>
    </select>
  </div>
  
  <div class="form-group">
    <label class="flex items-center">
      <input type="checkbox" class="form-checkbox">
      <span class="ml-2">Subscribe to newsletter</span>
    </label>
  </div>
  
  <button type="submit" class="btn btn-primary">Submit</button>
</form>
```

### Validation States

```html
<!-- Error State -->
<div class="form-group">
  <label for="password" class="form-label">Password</label>
  <input type="password" id="password" class="form-input error" value="123">
  <div class="form-error">Password must be at least 8 characters long</div>
</div>

<!-- Success State -->
<div class="form-group">
  <label for="username" class="form-label">Username</label>
  <input type="text" id="username" class="form-input success" value="johndoe">
</div>

<!-- Warning State -->
<div class="form-group">
  <label for="age" class="form-label">Age</label>
  <input type="number" id="age" class="form-input warning" value="17">
  <div class="form-error">You must be 18 or older</div>
</div>
```

## Card Components

### Basic Card

```html
<div class="card">
  <div class="card-body">
    <h3 class="text-lg font-medium mb-2">Card Title</h3>
    <p class="text-gray-600">This is a basic card with only body content.</p>
  </div>
</div>
```

### Card with Header and Footer

```html
<div class="card card-raised">
  <div class="card-header">
    <h3 class="font-medium">Featured</h3>
  </div>
  <div class="card-body">
    <h4 class="text-xl font-medium mb-2">Special title treatment</h4>
    <p class="mb-4">With supporting text below as a natural lead-in to additional content.</p>
    <button class="btn btn-primary">Go somewhere</button>
  </div>
  <div class="card-footer text-gray-500">
    2 days ago
  </div>
</div>
```

### Card with Media

```html
<div class="card">
  <div class="card-media-top">
    <img src="/images/sample.jpg" alt="Card image">
  </div>
  <div class="card-body">
    <h3 class="text-lg font-medium mb-2">Media Card</h3>
    <p class="text-gray-600">This card has an image at the top.</p>
  </div>
</div>
```

## Dark Mode Support

All components are designed to work with dark mode. Simply add the `dark` class to a parent element or use the CarFuse theme toggler to enable dark mode.

```html
<div class="dark">
  <!-- Components inside will use dark mode styles -->
  <button class="btn btn-primary">Dark Mode Button</button>
  <div class="card">
    <div class="card-body">
      <p>Dark mode card content</p>
    </div>
  </div>
</div>
```
