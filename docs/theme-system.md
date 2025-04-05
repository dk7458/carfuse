# Theme System Documentation

## Overview

Our theme system supports light and dark modes by default and can be extended with custom themes.

## Theme Structure

Themes are defined in `tailwind.config.js` using color scales that ensure accessibility:

```js
// Example color structure
colors: {
  primary: {
    50: '#f0f9ff',  // Lightest - for backgrounds
    100: '#e0f2fe', // Light hover states
    200: '#bae6fd', // Light borders
    300: '#7dd3fc', // ...
    400: '#38bdf8', // ...
    500: '#0ea5e9', // Default/base color
    600: '#0284c7', // Darker/accessible on white
    700: '#0369a1', // ...
    800: '#075985', // ...
    900: '#0c4a6e', // Darkest - high contrast
  },
  // Additional color scales...
}
```

## Using Theme Variables

Access theme colors via Tailwind classes:

```jsx
// Primary color with different shades
<div className="bg-primary-100 border-primary-300 text-primary-800">
  Themed content
</div>

// Dark mode variants
<div className="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
  Adapts to dark mode
</div>
```

## Extending the Theme

To add a new color to the theme:

1. Add it to `tailwind.config.js`:
   ```js
   module.exports = {
     theme: {
       extend: {
         colors: {
           'accent': {
             50: '#fffbeb',
             // ... other shades
             900: '#78350f',
           }
         }
       }
     }
   }
   ```

2. Use in components:
   ```jsx
   <button className="bg-accent-500 hover:bg-accent-600 text-white">
     Accent Button
   </button>
   ```

## Creating Custom Themes

For multi-theme support beyond light/dark:

```js
// Theme definitions
const themes = {
  light: {
    background: 'bg-white',
    text: 'text-gray-900',
    // ... more properties
  },
  dark: {
    background: 'bg-gray-900',
    text: 'text-white',
    // ... more properties
  },
  brand: {
    background: 'bg-primary-900',
    text: 'text-primary-50',
    // ... more properties
  }
};
```

Switching between themes:

```jsx
function applyTheme(themeName) {
  Object.entries(themes).forEach(([name]) => {
    document.documentElement.classList.remove(`theme-${name}`);
  });
  document.documentElement.classList.add(`theme-${themeName}`);
}
```
