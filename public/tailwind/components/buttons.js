/**
 * CarFuse Button Component Styles
 */

module.exports = {
  // Button components with dark mode support
  '.carfuse-btn': {
    '@apply px-4 py-2 rounded-btn font-medium transition-colors duration-200': {},
  },
  '.carfuse-btn-primary': {
    '@apply bg-blue-600 hover:bg-blue-700 text-white': {},
    '.dark &': {
      '@apply bg-blue-600 hover:bg-blue-700 text-white': {},
    },
  },
  '.carfuse-btn-secondary': {
    '@apply bg-green-600 hover:bg-green-700 text-white': {},
    '.dark &': {
      '@apply bg-green-600 hover:bg-green-700 text-white': {},
    },
  },
  '.carfuse-btn-outline': {
    '@apply border border-gray-300 hover:bg-gray-50 text-gray-700': {},
    '.dark &': {
      '@apply border-gray-600 hover:bg-gray-800 text-gray-200': {},
    },
  },
};
