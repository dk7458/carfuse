/**
 * Accessibility utility functions and constants
 */

// Focus ring styles that maintain visibility in both light/dark modes
export const focusRingClasses = 'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-primary-400 dark:focus:ring-offset-gray-900';

// Screen reader only content
export const srOnlyClasses = 'sr-only';

// Respect reduced motion preferences
export const reducedMotionClasses = 'motion-reduce:transition-none motion-reduce:transform-none motion-reduce:animate-none';

// Generate aria attributes helpers
export const ariaExpanded = (isExpanded: boolean) => ({ 'aria-expanded': isExpanded });
export const ariaPressed = (isPressed: boolean) => ({ 'aria-pressed': isPressed });
export const ariaControls = (id: string) => ({ 'aria-controls': id });
