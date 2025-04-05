/**
 * CarFuse Theme Preload Script
 * This script runs immediately to prevent flash of incorrect theme
 * Must be included in the <head> of the document
 */

(function() {
  // Try to get theme from localStorage
  try {
    const storedTheme = localStorage.getItem('carfuse_theme');
    let theme = 'light'; // Default theme
    
    if (storedTheme) {
      // Use stored theme if available
      if (['light', 'dark', 'high-contrast'].includes(storedTheme)) {
        theme = storedTheme;
      } else if (storedTheme === 'system') {
        // Check system preference
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
        theme = prefersDark.matches ? 'dark' : 'light';
      }
    } else {
      // No stored preference, check system
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
      theme = prefersDark.matches ? 'dark' : 'light';
    }
    
    // Apply theme class to document immediately
    document.documentElement.classList.add(theme);
    
    // Set a meta tag for page coloring during load
    let metaThemeColor = document.querySelector('meta[name="theme-color"]');
    if (!metaThemeColor) {
      metaThemeColor = document.createElement('meta');
      metaThemeColor.setAttribute('name', 'theme-color');
      document.head.appendChild(metaThemeColor);
    }
    
    // Set appropriate color based on theme
    if (theme === 'dark') {
      metaThemeColor.setAttribute('content', '#121212');
    } else if (theme === 'high-contrast') {
      metaThemeColor.setAttribute('content', '#000000');
    } else {
      metaThemeColor.setAttribute('content', '#ffffff');
    }
    
  } catch (e) {
    // If error, default to light mode
    document.documentElement.classList.add('light');
  }
})();
