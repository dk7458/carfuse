# Dark Mode Implementation

*Last updated: 2023-11-15*

This document explains how to implement and work with dark mode in CarFuse applications.

## Table of Contents
- [Dark Mode Architecture](#dark-mode-architecture)
- [Enabling Dark Mode](#enabling-dark-mode)
- [Theme Switching](#theme-switching)
- [CSS Implementation](#css-implementation)
- [Component Adaptation](#component-adaptation)
- [Testing Dark Mode](#testing-dark-mode)
- [Common Issues](#common-issues)
- [Related Documentation](#related-documentation)

## Dark Mode Architecture

CarFuse's dark mode implementation follows these principles:

1. **CSS-based**: Uses CSS variables and classes for theme switching
2. **Semantic Colors**: All components use semantic color references that adapt to the active theme
3. **Progressive Enhancement**: Falls back gracefully in unsupported environments
4. **System Preference Awareness**: Can detect and respect user's system preference
5. **Persistence**: Remembers user preference across sessions

## Enabling Dark Mode

### Basic Implementation

To enable dark mode, add the `dark` class to the HTML element:

```html
<!DOCTYPE html>
<html class="dark">
<head>
    <!-- ... -->
</head>
<body>
    <!-- Content will use dark mode -->
</body>
</html>
```

### With System Preference Detection

To detect and respect the user's system preference:

```javascript
// Check if the user prefers dark mode
if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
    document.documentElement.classList.add('dark');
} else {
    document.documentElement.classList.remove('dark');
}

// Listen for changes in system preference
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
    if (event.matches) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
});
```

## Theme Switching

### Basic Theme Toggle

```javascript
function toggleDarkMode() {
    document.documentElement.classList.toggle('dark');
    
    // Optional: Save preference to localStorage
    const isDarkMode = document.documentElement.classList.contains('dark');
    localStorage.setItem('darkMode', isDarkMode ? 'dark' : 'light');
}

// Example usage with a button
document.getElementById('theme-toggle').addEventListener('click', toggleDarkMode);
```

### Using the Theme Manager

CarFuse provides a built-in theme manager for more advanced use cases:

```javascript
// Initialize theme manager
CarFuse.themeManager.init({
    storageKey: 'user-theme-preference',
    defaultTheme: 'light'
});

// Switch to dark mode
CarFuse.themeManager.setTheme('dark');

// Toggle between light and dark
CarFuse.themeManager.toggleTheme();

// Get current theme
const currentTheme = CarFuse.themeManager.getCurrentTheme();
```

### Alpine.js Integration

```html
<div x-data="themeController">
    <button @click="toggleTheme" 
            :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'">
        <span x-show="isDark" class="icon-sun"></span>
        <span x-show="!isDark" class="icon-moon"></span>
        <span x-text="isDark ? 'Light Mode' : 'Dark Mode'"></span>
    </button>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('themeController', () => ({
        isDark: document.documentElement.classList.contains('dark'),
        
        init() {
            this.$watch('isDark', value => {
                if (value) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                localStorage.setItem('theme', value ? 'dark' : 'light');
            });
            
            // Check for saved preference or system preference
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                this.isDark = savedTheme === 'dark';
            } else {
                this.isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            }
        },
        
        toggleTheme() {
            this.isDark = !this.isDark;
        }
    }));
});
</script>
```

## CSS Implementation

### Smooth Transitions

To enable smooth transitions between light and dark mode:

```css
/* Add to your main CSS file */
:root {
  --transition-theme: 0.3s ease;
}

.transition-theme,
.transition-theme * {
  transition: background-color var(--transition-theme),
              border-color var(--transition-theme),
              color var(--transition-theme),
              fill var(--transition-theme),
              stroke var(--transition-theme),
              box-shadow var(--transition-theme);
}
```

Then apply the `transition-theme` class to the body or any container that should transition smoothly:

```html
<body class="transition-theme">
  <!-- Content will transition smoothly when theme changes -->
</body>
```

### Dark Mode Variable Overrides

The CarFuse theme system automatically handles dark mode variable mapping, but you can customize it:

```css
/* Light theme (default) */
:root {
  --color-surface: #ffffff;
  --color-text-primary: #1a1a1a;
  /* Other variables */
}

/* Dark theme overrides */
.dark {
  --color-surface: #1a1a1a;
  --color-text-primary: #f5f5f5;
  /* Override other variables */
}
```

## Component Adaptation

Most CarFuse components automatically adapt to dark mode when the `dark` class is present. However, some components may need special consideration:

### Images and Icons

For images that should change in dark mode:

```html
<!-- Method 1: CSS-based switcher -->
<div class="theme-aware-image" aria-label="Logo"></div>

<style>
.theme-aware-image {
  background-image: url('/images/logo-light.png');
}

.dark .theme-aware-image {
  background-image: url('/images/logo-dark.png');
}
</style>

<!-- Method 2: Using <picture> element -->
<picture>
  <source srcset="/images/image-dark.png" media="(prefers-color-scheme: dark)">
  <img src="/images/image-light.png" alt="Adaptable image">
</picture>
```

### Custom Components

When creating custom components:

1. Always use semantic color variables instead of hard-coded colors
2. Test both light and dark mode during development
3. Consider creating dark-specific variants only when necessary

```javascript
// Example custom component handling dark mode
CarFuse.createComponent('customWidget', {
    render(element) {
        const isDarkMode = document.documentElement.classList.contains('dark');
        
        // Apply appropriate styling based on theme
        element.style.backgroundColor = `var(--color-surface-${isDarkMode ? 'dark' : 'light'})`;
    }
});
```

## Testing Dark Mode

To thoroughly test dark mode:

1. **Manual testing**: Toggle between light and dark mode to catch visual issues
2. **Automated visual testing**: Use tools like Percy or Chromatic to capture screenshots in both modes
3. **Content readability**: Ensure all text meets contrast requirements in both themes
4. **Interactive states**: Check hover, focus, and active states in both themes

## Common Issues

### Flashing on Page Load

If the page briefly shows the wrong theme when loading:

```html
<!-- Add this to <head> -->
<script>
  // Immediately set theme to prevent flash
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'dark' || 
     (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
  }
</script>
```

### Images with Wrong Colors

For images with transparent backgrounds that look wrong in dark mode:

```css
.dark .invert-in-dark {
  filter: invert(1) hue-rotate(180deg);
}
```

### Third-Party Widgets

For third-party widgets that don't respect your dark mode:

```css
/* Example for embedding a light-themed widget in dark mode */
.dark .third-party-widget {
  /* Create a light container for the widget */
  background-color: white;
  padding: 1rem;
  border-radius: 0.5rem;
  color: black;
}
```

## Related Documentation

- [Theme System Overview](overview.md)
- [Color System](colors.md)
- [UI Component Principles](../ui/overview.md)
