# Theme System Overview

*Last updated: 2023-11-15*

This document outlines the CarFuse theme system, which provides consistent styling and theming capabilities throughout the application.

## Table of Contents
- [System Architecture](#system-architecture)
- [Core Concepts](#core-concepts)
- [Usage Patterns](#usage-patterns)
- [Theme Configuration](#theme-configuration)
- [Creating Custom Themes](#creating-custom-themes)
- [Related Documentation](#related-documentation)

## System Architecture

The CarFuse theme system follows a layered approach:

1. **Base Layer** - Core variables and reset styles
2. **Semantic Layer** - Purpose-based variable mapping
3. **Component Layer** - Theme-aware component styles
4. **Theme Variants** - Light, dark, and custom theme definitions

## Core Concepts

### Semantic Design

Colors and other design values are named by purpose, not appearance:

```css
/* Good - semantic naming */
--color-primary: #1a73e8;
--color-danger: #dc3545;

/* Avoid - appearance-based naming */
--color-blue: #1a73e8;
--color-red: #dc3545;
```

### CSS Variables

Theme values are exposed as CSS custom properties:

```css
:root {
  --color-primary: #1a73e8;
  --font-family-base: 'Inter', sans-serif;
  --spacing-unit: 0.25rem;
}

.btn-primary {
  background-color: var(--color-primary);
}
```

### Theme Switching

Themes are applied by changing CSS classes on the root element:

```html
<!-- Light theme (default) -->
<html>
  <!-- Content uses default theme -->
</html>

<!-- Dark theme -->
<html class="dark">
  <!-- Content automatically adapts -->
</html>

<!-- Custom theme -->
<html class="theme-brand">
  <!-- Content uses brand theme -->
</html>
```

## Usage Patterns

### In CSS

```css
.my-component {
  background-color: var(--color-surface);
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
}
```

### In Tailwind

```html
<div class="bg-surface text-text-primary border border-border rounded">
  Content with semantic colors
</div>
```

### In JavaScript

```javascript
// Access theme values in JavaScript
const primaryColor = getComputedStyle(document.documentElement)
  .getPropertyValue('--color-primary').trim();

// Set theme values
document.documentElement.style.setProperty('--color-primary', '#0052cc');
```

## Theme Configuration

Main theme configuration in `theme.config.js`:

```javascript
module.exports = {
  base: {
    fontFamily: { ... },
    colors: { ... },
    spacing: { ... },
    borderRadius: { ... }
  },
  themes: {
    light: { /* light theme values */ },
    dark: { /* dark theme values */ },
    brand: { /* custom brand theme */ }
  }
};
```

## Creating Custom Themes

To create a custom theme:

1. Duplicate `themes/theme-template.css`
2. Override CSS variables with your custom values
3. Register the theme in `theme.config.js`
4. Add theme class selector to your CSS

Example custom theme:

```css
/* themes/custom-theme.css */
:root.theme-custom {
  --color-primary: #9c27b0;
  --color-primary-light: #d05ce3;
  --color-primary-dark: #6a1b9a;
  /* Add other overrides as needed */
}
```

## Related Documentation

- [Color System](colors.md)
- [Dark Mode Implementation](dark-mode.md)
- [UI Component Principles](../ui/overview.md)
