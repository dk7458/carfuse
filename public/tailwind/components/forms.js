/**
 * CarFuse Form Component Styles
 */

module.exports = {
  // Form input styles
  '.carfuse-input': {
    '@apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm': {},
    '@apply focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500': {},
    '.dark &': {
      '@apply border-gray-600 bg-gray-700 text-gray-100': {},
      '@apply focus:ring-blue-400 focus:border-blue-400': {},
    },
  },
  
  // Form label styles
  '.carfuse-label': {
    '@apply block text-sm font-medium text-gray-700 mb-1': {},
    '.dark &': {
      '@apply text-gray-300': {},
    },
  },
  
  // Form error styles
  '.carfuse-error': {
    '@apply mt-1 text-sm text-red-600': {},
    '.dark &': {
      '@apply text-red-400': {},
    },
  },
};
