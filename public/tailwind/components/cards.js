/**
 * CarFuse Card Component Styles
 */

module.exports = {
  // Card component
  '.carfuse-card': {
    '@apply bg-white rounded-lg shadow-md p-6 border border-gray-100 transition-all duration-200': {},
    '&:hover': {
      '@apply shadow-lg': {},
    },
    '.dark &': {
      '@apply bg-dark-card border-dark-border shadow-dark-card': {},
      '&:hover': {
        '@apply shadow-dark-card-hover': {},
      },
    },
  },
  
  // Dark themed stats card for dashboard
  '.dashboard-card': {
    '@apply rounded-lg p-6 transition-all duration-200': {},
    backgroundColor: 'var(--color-surface-card)',
    boxShadow: 'var(--shadow-card)',
    '.dark &': {
      backgroundColor: 'var(--color-dark-card)',
      borderColor: 'var(--color-dark-border)',
      boxShadow: 'var(--shadow-dark-card)',
    },
    '&:hover': {
      '@apply -translate-y-1': {},
      boxShadow: 'var(--shadow-card-hover)',
      '.dark &': {
        boxShadow: 'var(--shadow-dark-card-hover)',
      },
    },
  },
};
