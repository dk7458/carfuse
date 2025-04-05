/** @type {import('tailwindcss').Config} */

// Import theme modules
const colors = require('./tailwind/theme/colors');
const typography = require('./tailwind/theme/typography');
const spacing = require('./tailwind/theme/spacing');
const shadows = require('./tailwind/theme/shadows');
const animation = require('./tailwind/theme/animation');

// Import component styles
const carfuseComponents = require('./tailwind/components/index');

// Import plugins
const plugins = require('./tailwind/plugins/index');
const safelist = require('./tailwind/plugins/safelist');

module.exports = {
  content: [
    // More specific paths for better performance
    '../app/**/*.php',
    '../templates/**/*.php',
    '../components/**/*.php',
    './views/**/*.{php,html}',
    './js/**/*.{js,jsx,ts,tsx}',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: colors,
      // Merge typography config
      fontSize: typography.fontSize,
      fontFamily: typography.fontFamily,
      lineHeight: typography.lineHeight || {
        none: '1',
        tight: '1.25',
        snug: '1.375',
        normal: '1.5',
        relaxed: '1.625',
        loose: '2',
      },
      letterSpacing: typography.letterSpacing || {
        tighter: '-0.05em',
        tight: '-0.025em',
        normal: '0em',
        wide: '0.025em',
        wider: '0.05em',
        widest: '0.1em',
      },
      // Merge other theme configs
      boxShadow: shadows,
      spacing: spacing,
      animation: animation.animation,
      keyframes: animation.keyframes,
      transitionProperty: {
        ...animation.transitionProperty,
        'theme': 'background-color, border-color, color, fill, stroke, opacity, box-shadow, transform',
      },
      transitionDuration: {
        ...animation.transitionDuration,
        '250': '250ms',
        '350': '350ms',
      },
      transitionTimingFunction: animation.transitionTimingFunction,
      borderRadius: {
        'card': '0.5rem',
        'btn': '0.375rem',
        'panel': '0.5rem',
        'pill': '9999px',
        'toggle': '999px',
      },
      // Standardized screens config
      screens: {
        'xs': '475px',   // Extra small devices
        'sm': '640px',   // Small devices
        'md': '768px',   // Medium devices
        'lg': '1024px',  // Large devices
        'xl': '1280px',  // Extra large devices
        '2xl': '1536px', // 2 Extra large devices
      },
      // Container config for consistent widths
      container: {
        center: true,
        padding: {
          DEFAULT: '1rem',
          sm: '1.5rem',
          lg: '2rem',
        },
      },
      typography: typography.typographyPlugin,
    },
  },
  variants: {
    extend: {
      // Keep essential variants only for better performance
      opacity: ['disabled', 'dark', 'dark-hover'],
      cursor: ['disabled'],
      backgroundColor: ['active', 'disabled', 'dark', 'dark-hover'],
      textColor: ['active', 'disabled', 'dark', 'dark-hover', 'dark-active'],
      borderColor: ['active', 'focus', 'disabled', 'dark', 'dark-focus'],
      ringColor: ['active', 'focus', 'dark', 'dark-focus'],
      ringWidth: ['active', 'focus', 'dark', 'dark-focus'],
      boxShadow: ['dark', 'dark-hover', 'dark-focus'],
      placeholderColor: ['dark', 'dark-focus'],
    },
  },
  plugins: plugins,
  // Remove deprecated JIT mode as it's now default
  // Optimized safelist with only essential classes
  safelist: [
    // Core theme classes
    'dark',
    'light',
    'high-contrast',
    // Essential transition classes
    'transition-theme',
    'duration-300',
    // Filter additional necessary classes from safelist
    ...safelist.filter(className => 
      /^(bg-|text-|border-)/.test(className) || 
      className.includes('theme-')
    )
  ],
  // Future options
  future: {
    hoverOnlyWhenSupported: true,
    respectDefaultRingColorOpacity: true,
  },
};
