import { createContext, useContext, useState, useEffect } from 'react';
import { useSmoothThemeSwitch } from '../utils/performance';

// ...existing code...

export function ThemeProvider({ children }) {
  const [theme, setTheme] = useState(() => {
    // Check localStorage and system preference
    if (typeof window !== 'undefined') {
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme) return savedTheme;
      
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      return prefersDark ? 'dark' : 'light';
    }
    return 'light';
  });
  
  // Optimize theme switching
  useSmoothThemeSwitch(theme);
  
  // ...existing code...
  
  return (
    <ThemeContext.Provider value={{ theme, setTheme }}>
      {children}
    </ThemeContext.Provider>
  );
}

// ...existing code...
