import { twMerge } from 'tailwind-merge';

/**
 * Merges backend-generated classes with frontend Tailwind classes
 * Resolves conflicts in favor of frontend classes
 */
export function mergeClasses(
  backendClasses?: string,
  frontendClasses?: string
): string {
  return twMerge(backendClasses || '', frontendClasses || '');
}

/**
 * Creates tailwind classes conditionally
 */
export function conditionalClasses(
  baseClasses: string,
  conditions: Record<string, boolean>
): string {
  const activeClasses = Object.entries(conditions)
    .filter(([_, isActive]) => isActive)
    .map(([className]) => className)
    .join(' ');
    
  return twMerge(baseClasses, activeClasses);
}

/**
 * Validates if classes are JIT-compatible 
 * Used to warn about potentially unsupported backend class names
 */
export function validateJitClasses(classes: string): {
  isValid: boolean;
  invalidClasses: string[];
} {
  const knownPatterns = [
    /^(bg|text|border|p|m|flex|grid|w|h)-/,
    /^(hover|focus|active|disabled|dark):/,
    // Common utility classes
    /^(block|flex|grid|hidden|inline|inline-block)$/
  ];
  
  const classList = classes.split(/\s+/).filter(Boolean);
  const invalidClasses = classList.filter(
    cls => !knownPatterns.some(pattern => pattern.test(cls))
  );
  
  return {
    isValid: invalidClasses.length === 0,
    invalidClasses
  };
}
