/**
 * Custom CarFuse Tailwind Plugin
 * This plugin adds custom utilities and components specific to CarFuse
 */

module.exports = function({ addUtilities, theme, addComponents, addBase }) {
  const newUtilities = {
    '.text-shadow': {
      textShadow: '0px 1px 2px rgba(0, 0, 0, 0.1)',
    },
    '.text-shadow-md': {
      textShadow: '0px 2px 4px rgba(0, 0, 0, 0.1)',
    },
    '.text-shadow-none': {
      textShadow: 'none',
    },
    // Transitions for Polish UI
    '.transition-smooth': {
      transition: 'all 0.3s ease-in-out',
    },
    // Status indicators for CarFuse
    '.status-badge': {
      padding: '0.25rem 0.75rem',
      borderRadius: '9999px',
      fontSize: '0.75rem',
      fontWeight: '600',
      display: 'inline-flex',
      alignItems: 'center',
    },
    // Polish text adjustments
    '.polish-quotes': {
      quotes: '"„" """',
    },
    // Polish currency formatting
    '.polish-currency': {
      whiteSpace: 'nowrap',
      fontVariantNumeric: 'tabular-nums',
    },
    // Polish date formatting
    '.polish-date': {
      fontVariantNumeric: 'tabular-nums',
    },
    // Dark mode utilities
    '.dark-bg': {
      backgroundColor: theme('colors.dark.bg'),
    },
    '.dark-card': {
      backgroundColor: theme('colors.dark.card'),
    },
    '.dark-text-primary': {
      color: theme('colors.text.dark-primary'),
    },
    '.dark-text-secondary': {
      color: theme('colors.text.dark-secondary'),
    },
    // Dashboard specific utilities
    '.dashboard-scrollbar': {
      scrollbarWidth: 'thin',
      '&::-webkit-scrollbar': {
        width: '6px',
        height: '6px',
      },
      '&::-webkit-scrollbar-track': {
        backgroundColor: theme('colors.gray.100'),
      },
      '&::-webkit-scrollbar-thumb': {
        backgroundColor: theme('colors.gray.400'),
        borderRadius: '3px',
      },
      '.dark &::-webkit-scrollbar-track': {
        backgroundColor: theme('colors.dark.bg'),
      },
      '.dark &::-webkit-scrollbar-thumb': {
        backgroundColor: theme('colors.dark.border'),
      },
    },
  };
  
  // Add theme transition utilities
  const themeTransitionUtilities = {
    '.transition-theme': {
      transitionProperty: 'color, background-color, border-color, text-decoration-color, fill, stroke, box-shadow',
      transitionTimingFunction: 'cubic-bezier(0.4, 0, 0.2, 1)',
      transitionDuration: '300ms',
    },
    '.transition-theme-slow': {
      transitionProperty: 'color, background-color, border-color, text-decoration-color, fill, stroke, box-shadow',
      transitionTimingFunction: 'cubic-bezier(0.4, 0, 0.2, 1)',
      transitionDuration: '500ms',
    },
    '.transition-theme-fast': {
      transitionProperty: 'color, background-color, border-color, text-decoration-color, fill, stroke, box-shadow',
      transitionTimingFunction: 'cubic-bezier(0.4, 0, 0.2, 1)',
      transitionDuration: '150ms',
    },
    // System preference detection utilities
    '.theme-auto': {
      '@media (prefers-color-scheme: dark)': {
        ':root:not(.light)': {
          colorScheme: 'dark',
        }
      }
    },
  };
  
  // Add CSS variable base
  addBase({
    ':root': {
      // Primary colors
      '--color-primary': theme('colors.blue.500'),
      '--color-primary-light': theme('colors.blue.400'),
      '--color-primary-dark': theme('colors.blue.600'),
      
      // Secondary colors
      '--color-secondary': theme('colors.green.500'),
      '--color-secondary-light': theme('colors.green.400'),
      '--color-secondary-dark': theme('colors.green.600'),
      
      // Accent colors
      '--color-accent': theme('colors.violet.500'),
      '--color-accent-light': theme('colors.violet.400'),
      '--color-accent-dark': theme('colors.violet.600'),
      
      // Surface colors
      '--color-surface-light': '#ffffff',
      '--color-surface': '#f9fafb',
      '--color-surface-dark': '#f3f4f6',
      
      // Text colors
      '--color-text-primary': '#111827',
      '--color-text-secondary': '#374151',
      '--color-text-tertiary': '#6b7280',
      '--color-text-inverted': '#ffffff',
      
      // Status colors
      '--color-status-success': theme('colors.green.500'),
      '--color-status-warning': theme('colors.amber.500'),
      '--color-status-danger': theme('colors.red.500'),
      '--color-status-info': theme('colors.blue.500'),
      '--color-status-pending': theme('colors.amber.500'),
      '--color-status-completed': theme('colors.green.500'),
      '--color-status-cancelled': theme('colors.red.500'),
      '--color-status-active': theme('colors.blue.500'),
      
      // Standard semantic colors
      '--color-danger': theme('colors.red.500'),
      '--color-danger-light': theme('colors.red.400'),
      '--color-danger-dark': theme('colors.red.600'),
      
      '--color-warning': theme('colors.amber.500'),
      '--color-warning-light': theme('colors.amber.400'),
      '--color-warning-dark': theme('colors.amber.600'),
      
      '--color-success': theme('colors.green.500'),
      '--color-success-light': theme('colors.green.400'),
      '--color-success-dark': theme('colors.green.600'),
      
      '--color-info': theme('colors.blue.500'),
      '--color-info-light': theme('colors.blue.400'),
      '--color-info-dark': theme('colors.blue.600'),
      
      // Dark mode specific colors
      '--color-dark-bg': '#121212',
      '--color-dark-card': '#1E1E1E',
      '--color-dark-border': '#333333',
      '--color-dark-hover': '#2A2A2A',
      '--color-dark-active': '#303030',
      
      // Dark surface colors
      '--color-surface-dark-light': '#1E1E1E',
      '--color-surface-dark-default': '#171717',
      '--color-surface-dark-dark': '#121212',
      
      // Dark text colors
      '--color-text-dark-primary': '#E0E0E0',
      '--color-text-dark-secondary': '#AAAAAA',
      '--color-text-dark-tertiary': '#6B7280',
      '--color-text-dark-inverted': '#111827',
    },
    '.dark': {
      colorScheme: 'dark',
      
      // Dark mode color overrides
      '--color-primary': theme('colors.blue.400'),
      '--color-primary-light': theme('colors.blue.300'),
      '--color-primary-dark': theme('colors.blue.500'),
      
      '--color-secondary': theme('colors.green.400'),
      '--color-secondary-light': theme('colors.green.300'),
      '--color-secondary-dark': theme('colors.green.500'),
      
      '--color-accent': theme('colors.violet.400'),
      '--color-accent-light': theme('colors.violet.300'),
      '--color-accent-dark': theme('colors.violet.500'),
      
      // Surface colors in dark mode
      '--color-surface-light': '#1E1E1E',
      '--color-surface': '#171717',
      '--color-surface-dark': '#121212',
      
      // Text colors in dark mode
      '--color-text-primary': '#E0E0E0',
      '--color-text-secondary': '#AAAAAA',
      '--color-text-tertiary': '#6B7280',
      '--color-text-inverted': '#111827',
      
      backgroundColor: 'var(--color-dark-bg)',
      color: 'var(--color-text-dark-primary)',
    },
    // Add Polish-specific typography defaults
    'html[lang="pl"]': {
      fontFeatureSettings: '"locl"',
      hyphens: 'auto',
    },
    // Polish currency formatting
    '.pln': {
      '&::after': {
        content: '" zł"',
      }
    },
  });
  
  // Add custom utilities
  addUtilities(newUtilities);
  addUtilities(themeTransitionUtilities);
};
