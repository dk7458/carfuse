# Tailwind CSS Framework Guidelines

## Core Principles

- **Accessibility First**: Meet or exceed WCAG 2.1 AA standards
- **Performance Optimized**: Minimize render blocking and layout shifts
- **Backend Compatible**: Support dynamic class generation
- **Consistent Design Language**: Follow design system tokens

## Accessibility Standards

### Color Contrast

All color combinations must meet minimum contrast requirements:
- Normal text (14px+): 4.5:1 contrast ratio
- Large text (18px+): 3:1 contrast ratio

Use our color variables which are pre-tested for contrast:

```jsx
// Use semantic color values
<button className="bg-primary-600 text-white">Good Contrast</button>

// Avoid using colors that haven't been tested
<button className="bg-[#someHexValue] text-white">Avoid this</button>
```

### Focus Management

Always use our focus utilities:

```jsx
import { focusRingClasses } from '../utils/a11y';

<button className={`bg-blue-500 ${focusRingClasses}`}>
  Accessible Button
</button>
```

### Screen Reader Support

- Use semantic HTML elements
- Add ARIA attributes where needed
- Add descriptive alt text to images
- Hide decorative elements from screen readers

### Reduced Motion

Always wrap animations in the reducedMotionClasses:

```jsx
import { reducedMotionClasses } from '../utils/a11y';

<div className={`animate-bounce ${reducedMotionClasses}`}>
  Content
</div>
```

## Performance Optimization

### Content Visibility

Use content-visibility for large off-screen sections:

```jsx
import { contentVisibilityClasses } from '../utils/performance';

<section className={contentVisibilityClasses}>
  {/* Large content that's initially off-screen */}
</section>
```

### Theme Switching

Use the smooth theme switch utility:

```jsx
import { useSmoothThemeSwitch } from '../utils/performance';

function MyApp() {
  const [theme, setTheme] = useState('light');
  useSmoothThemeSwitch(theme);
  
  // rest of component
}
```

### Lazy Loading

Use the lazy import utility for non-critical components:

```jsx
import { lazyImport } from '../utils/performance';

const HeavyComponent = lazyImport(() => import('./HeavyComponent'));
```

## Backend Integration

### Working with Backend-Generated Classes

Use mergeClasses utility to safely combine classes:

```jsx
import { mergeClasses } from '../utils/backendIntegration';

<div className={mergeClasses(backendClasses, 'text-lg font-bold')}>
  Content
</div>
```

### Guidelines for Backend Developers

When generating Tailwind classes from the backend:

1. Use only standard Tailwind classes documented in our design system
2. Prioritize utility classes over component classes
3. Test your classes with the validateJitClasses utility
4. Document any custom classes that might need to be added to safelist

## Migration Checklist

- [ ] Update color usage to ensure proper contrast
- [ ] Add proper focus states to all interactive elements
- [ ] Add aria attributes where needed
- [ ] Implement reduced motion alternatives
- [ ] Test with keyboard navigation
- [ ] Test with screen readers
- [ ] Apply performance optimizations
- [ ] Verify backend class compatibility
