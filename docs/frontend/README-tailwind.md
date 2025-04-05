# CarFuse Tailwind CSS Structure

This document outlines the organization of our Tailwind CSS codebase.

## Directory Structure

```
/home/dorian/carfuse/
├── public/
│   ├── css/
│   │   ├── tailwind.css            # Main CSS entry point (imports all modules)
│   │   ├── base/
│   │   │   ├── variables.css       # CSS variables and design tokens
│   │   │   ├── typography.css      # Base typography styles
│   │   │   └── reset.css           # Base element styles
│   │   ├── components/
│   │   │   ├── buttons.css         # Button components
│   │   │   ├── forms.css           # Form controls
│   │   │   ├── cards.css           # Card layouts
│   │   │   └── ...                 # Other component styles
│   │   ├── themes/
│   │   │   ├── light.css           # Light theme variables
│   │   │   └── dark.css            # Dark theme variables
│   │   └── utilities/
│   │       ├── animations.css      # Animation utilities
│   │       ├── responsive.css      # Responsive utilities
│   │       └── print.css           # Print styles
│   ├── tailwind.config.js          # Main Tailwind configuration
│   └── js/
│       └── components/
│           └── theme.js            # Theme toggling functionality
└── tailwind/                       # Modular Tailwind configuration
    ├── theme/
    │   ├── colors.js               # Color definitions
    │   ├── typography.js           # Font definitions
    │   ├── spacing.js              # Spacing scale
    │   ├── shadows.js              # Shadow definitions
    │   └── animation.js            # Animation configurations
    ├── components/
    │   ├── index.js                # Component exports
    │   ├── buttons.js              # Button component styles
    │   ├── cards.js                # Card component styles
    │   ├── status.js               # Status indicator styles
    │   └── forms.js                # Form component styles
    └── plugins/
        ├── index.js                # Plugin exports
        ├── carfuse.js              # Custom CarFuse plugin
        ├── register.js             # Plugin registration
        └── safelist.js             # Safelist configuration
```

## CSS Variables System

Our design system uses CSS variables for all design tokens to ensure consistency across themes. The variables follow this naming convention:

```
--{category}-{subcategory}-{variant}
```

Example: `--color-primary-500`, `--spacing-4`, `--transition-duration-normal`

## Typography System

Our typography system follows a consistent scale with standardized:
- Font sizes
- Line heights (none, tight, snug, normal, relaxed, loose)
- Letter spacing (tighter, tight, normal, wide, wider, widest)
- Font families

All typography settings maintain consistency between light and dark modes.

## Spacing and Sizing System

We use a systematic approach to spacing:
- Consistent spacing scale based on increments of 4px
- Standardized container widths at each breakpoint
- Responsive padding and margins that follow the same scale

## Breakpoints

Our application uses these standardized breakpoints:
- `xs`: 475px (Extra small devices)
- `sm`: 640px (Small devices)
- `md`: 768px (Medium devices)
- `lg`: 1024px (Large devices)
- `xl`: 1280px (Extra large devices)
- `2xl`: 1536px (2 Extra large devices)

## Theme Switching

Theme switching is handled by the `CarFuse.theme` component in `js/components/theme.js`. It supports:

- Light and dark modes
- System preference detection
- High contrast mode
- Theme persistence in localStorage
- Smooth transitions between themes

## Building CSS

To build the CSS files:

```bash
# Development build with watching
npm run watch:css

# Production build
npm run build:css
```

## Performance Optimization

Our Tailwind configuration is optimized for performance:
- Specific content paths to reduce build time
- Minimal safelist to reduce CSS size
- Optimized variants to limit unnecessary CSS generation
- Container configuration for consistent responsive behavior

## Adding New Components

1. Create a new file in the appropriate directory (e.g., `components/tooltips.css`)
2. Import it in the main `tailwind.css` file
3. Add any required JavaScript functionality
4. Update the documentation

## Tailwind Configuration

The Tailwind configuration is modularized for better maintainability:

- Each theme aspect (colors, typography, etc.) is in its own file
- Custom components are defined in separate files
- Plugins are organized in a dedicated directory

## Best Practices

1. Use the CSS variables system for all colors, spacing, and other design tokens
2. Group related styles in the appropriate component files
3. Add new utility classes in the relevant utility files
4. Document any significant changes
5. Use the provided theme switching functionality instead of custom implementations
6. Follow the standardized typography and spacing systems
7. Minimize safelist usage to keep generated CSS lean

## Understanding Optimizations

The changes made improve:
1. **Build Performance**: More specific content paths reduce unnecessary file scanning
2. **CSS Size**: Optimized safelist and variant configuration reduces output size
3. **Design Consistency**: Standardized typography, spacing and container systems
4. **Responsive Design**: More comprehensive breakpoint system with xs added
