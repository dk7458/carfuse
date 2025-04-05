import { useEffect, useRef } from 'react';

/**
 * Apply content-visibility to off-screen elements for performance
 */
export const contentVisibilityClasses = 'content-visibility-auto contain-intrinsic-size-[1000px]';

/**
 * Optimize theme switching to prevent layout shifts
 */
export function useSmoothThemeSwitch(theme: string) {
  const prevTheme = useRef<string>(theme);
  
  useEffect(() => {
    if (prevTheme.current === theme) return;
    
    // Apply class with transition suppression to prevent FOUC
    document.documentElement.classList.add('theme-transition-suppress');
    
    // Update theme classes
    document.documentElement.classList.remove(prevTheme.current);
    document.documentElement.classList.add(theme);
    
    // Force browser to apply changes before removing suppression
    window.setTimeout(() => {
      document.documentElement.classList.remove('theme-transition-suppress');
      prevTheme.current = theme;
    }, 50);
  }, [theme]);
}

/**
 * Dynamic import wrapper for lazy loading components
 */
export function lazyImport(factory, fallback = null) {
  const LazyComponent = React.lazy(factory);
  return (props) => (
    <React.Suspense fallback={fallback}>
      <LazyComponent {...props} />
    </React.Suspense>
  );
}
