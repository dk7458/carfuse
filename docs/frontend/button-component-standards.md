# Button Component Standards

## Button Variants

### Primary Button
- **Purpose**: Main call-to-action
- **Visual Style**: Solid fill with brand primary color
- **Usage**: One primary button per view/section

### Secondary Button
- **Purpose**: Alternative actions of equal importance
- **Visual Style**: Less prominent than primary, typically with a lighter fill
- **Usage**: Can be used multiple times when actions have equal weight

### Outline Button
- **Purpose**: Less important actions
- **Visual Style**: Transparent with border
- **Usage**: Secondary actions that shouldn't detract from primary actions

### Text Button
- **Purpose**: Minor actions
- **Visual Style**: Text only, no background or border
- **Usage**: Tertiary actions, cancel options, or in space-constrained contexts

### Icon Button
- **Purpose**: Compact actions with clear meaning
- **Visual Style**: Icon only or icon with minimal text
- **Usage**: Toolbar actions, common functions with recognizable icons

## Button Sizes

### Small (sm)
- **Height**: 32px
- **Padding**: 8px 16px
- **Font Size**: 14px
- **Icon Size**: 16px
- **Usage**: Dense UIs, tables, cards

### Medium (md) - Default
- **Height**: 40px
- **Padding**: 12px 20px
- **Font Size**: 16px
- **Icon Size**: 18px
- **Usage**: Most interface contexts

### Large (lg)
- **Height**: 48px
- **Padding**: 16px 24px
- **Font Size**: 18px
- **Icon Size**: 20px
- **Usage**: Hero sections, prominent CTAs

## State Styling

### Default State
- Normal appearance with standard opacity

### Hover State
- **Visual Change**: Slight darkening of background color (10% overlay)
- **Outline Button**: Fill with light background
- **Text Button**: Text color darkens slightly

### Active/Pressed State
- **Visual Change**: Darker than hover state (20% overlay)
- **Animation**: Subtle scale reduction (98%)

### Disabled State
- **Opacity**: 50%
- **Cursor**: not-allowed
- **No hover effects**

### Loading State
- **Visual**: Spinner icon replacing text or alongside text
- **Cursor**: wait
- **Disabled interaction**

### Focus State (Keyboard)
- **Visual**: 2px outline with 2px offset
- **Color**: Accessible contrast focus color
- **No visible outline for mouse users (only keyboard focus)

## Typography & Spacing

### Typography
- **Font Family**: System primary font
- **Font Weight**: Button text is medium (500) or semi-bold (600)
- **Text Transform**: None (sentence case preferred)
- **Letter Spacing**: 0.2px for improved legibility

### Internal Spacing
- **Icon + Text**: 8px between icon and text
- **Multiple Words**: Default text spacing
- **Multiple Icons**: 8px between icons

### Layout Spacing
- **Button Groups**: 8px between buttons
- **Vertical Stacking**: 16px between buttons

## Implementation Examples

### HTML/CSS Example
```html
<button class="btn btn-primary btn-md">
  <span class="btn-text">Primary Button</span>
</button>

<button class="btn btn-secondary btn-md">
  <span class="btn-icon">
    <svg>...</svg>
  </span>
  <span class="btn-text">Secondary with Icon</span>
</button>

<button class="btn btn-outline btn-sm" disabled>
  <span class="btn-text">Disabled Outline</span>
</button>
```

### CSS Variables
```css
:root {
  --btn-primary-bg: #3366FF;
  --btn-primary-text: #FFFFFF;
  --btn-secondary-bg: #E8EEFF;
  --btn-secondary-text: #3366FF;
  --btn-outline-border: #C5CEE0;
  --btn-outline-text: #2E3A59;
  --btn-text-color: #3366FF;
  
  --btn-radius: 4px;
  --btn-focus-ring-color: rgba(51, 102, 255, 0.5);
}
```

## Accessibility Considerations

- Provide sufficient color contrast (4.5:1 minimum)
- Ensure focus states are clearly visible
- Use `aria-label` for icon-only buttons
- Consider `aria-busy="true"` for loading states
- Maintain touch target size of at least 44x44px for mobile
