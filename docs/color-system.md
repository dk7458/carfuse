# CarFuse Color System Documentation

This document outlines the color system used across CarFuse applications, providing guidance on proper usage of colors in UI components.

## Semantic Color Usage

Our color system is built around semantic names that describe the purpose rather than the appearance of colors. This approach ensures consistency across different themes and helps maintain accessibility standards.

### Primary Color

The primary brand color used for key actions, interactive elements, and brand recognition.

- `primary-lightest`: Backgrounds, subtle highlights
- `primary-lighter`: Hover states for light backgrounds
- `primary-light`: Secondary buttons, less prominent elements
- `primary`: Primary buttons, main interactive elements
- `primary-dark`: Hover states for interactive elements
- `primary-darker`: Active/pressed states
- `primary-darkest`: Text on light backgrounds, high contrast needs

### Secondary Color

The secondary brand color used for complementary interface elements.

- `secondary-lightest` through `secondary-darkest`: Follow same pattern as primary

### Accent Color

Used sparingly for emphasis, highlighting important UI elements, or creating visual interest.

- `accent-lightest` through `accent-darkest`: Follow same pattern as primary

### Surface Colors

Used for backgrounds, cards, and UI containers.

- `surface-white`: Pure white backgrounds
- `surface-lightest`: Very subtle background for cards/containers
- `surface-lighter`: Alternative background
- `surface-light`: Subtle separators, tertiary backgrounds
- `surface`: Default background color
- `surface-dark`: Medium contrast surfaces
- `surface-darker`: High contrast surfaces
- `surface-darkest`: Very high contrast surfaces
- `surface-black`: Maximum contrast background (near-black)

### Text Colors

Used for typography across the interface.

- `text-primary`: Main text color, highest contrast
- `text-secondary`: Secondary text, medium contrast
- `text-tertiary`: Low-emphasis text, lower contrast
- `text-disabled`: Disabled text, lowest contrast
- `text-inverse`: Text on dark/colored backgrounds

### Status Colors

Used to communicate system status, feedback, or outcomes.

- `info`: Informational elements
- `success`: Positive actions or outcomes
- `warning`: Cautionary elements
- `error`: Error states or destructive actions

Each status color includes `-lighter`, `-light`, and `-dark` variants for different emphasis levels.

### UI State Colors

Used to represent different interactive states.

- `state-hover`: Hover state overlay
- `state-active`: Active/pressed state overlay
- `state-selected`: Selected state background
- `state-disabled`: Disabled state appearance
- `state-focus`: Focus indicator color

## Using Colors in Code

### In CSS

```css
.element {
  color: var(--color-text-primary);
  background-color: var(--color-surface-light);
  border: 1px solid var(--color-border);
}
```

### In Tailwind

```html
<div class="text-text-primary bg-surface-light border border-border">
  Content
</div>
```

## Dark Mode Considerations

Colors automatically adjust in dark mode when the `.dark` class is applied to an ancestor element. The transition between modes can be smoothed by adding the `transition-theme` class.

### Dark Mode Inversions

In dark mode:
- Light surfaces become dark (e.g., `surface-white` becomes very dark)
- Dark surfaces become light (e.g., `surface-black` becomes white)
- Text colors invert for proper contrast

## Accessibility

All color pairings in our system maintain a minimum contrast ratio of 4.5:1 for normal text and 3:1 for large text, meeting WCAG AA standards.

### Best Practices

1. **Never use color alone** to convey information
2. **Maintain proper contrast** between text and background
3. **Test with color blindness simulators** to ensure information is perceivable
4. **Use focus indicators** with sufficient contrast

## Legacy Color Variables

For backwards compatibility, the following legacy variable names are mapped to their semantic equivalents:

- `--color-bg-primary` → `--color-surface-white`
- `--color-bg-secondary` → `--color-surface-lightest`
- `--color-bg-tertiary` → `--color-surface-lighter`

These mappings will be removed in a future version, so please migrate to the semantic naming system.

## Creating New Themes

To create a new theme, duplicate the `theme-template.css` file in the themes directory and customize the color variables as needed. Only override the specific colors that differ from the default theme.
