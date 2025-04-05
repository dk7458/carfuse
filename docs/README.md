# CarFuse Design System

## Color System

The CarFuse design system uses a semantic color naming system that describes the purpose rather than the appearance of colors. This approach ensures consistency across different themes and helps maintain accessibility standards.

### Key Features

- **Semantic Naming**: Colors are named by their function (e.g., `primary`, `surface-light`) rather than appearance
- **Theme Support**: Full support for light, dark, and custom themes
- **Accessibility**: WCAG AA compliant color contrast ratios
- **Tailwind Integration**: Seamless use in both CSS and Tailwind classes

### Documentation

For comprehensive color system documentation, see:
- [Color System Guide](./color-system.md)

### Migration

If you're working with legacy code that uses older color naming patterns, use the migration helper:

```bash
node scripts/color-migration-helper.js src/components
```

## Getting Started

To use the color system in your components:

```html
<!-- Using Tailwind classes -->
<div class="bg-surface-light text-text-primary border border-border">
  Content with semantic colors
</div>

<!-- Using CSS variables -->
<style>
.my-component {
  background-color: var(--color-surface-light);
  color: var(--color-text-primary);
  border: 1px solid var(--color-border);
}
</style>
```

### Theme Switching

To support smooth theme transitions:

```html
<body class="transition-theme">
  <!-- Content -->
</body>

<!-- To switch to dark mode -->
<script>
  document.documentElement.classList.toggle('dark');
</script>
```
