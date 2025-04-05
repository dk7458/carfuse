/**
 * CarFuse Tailwind Plugins
 * This file exports all plugins used in the Tailwind configuration
 */

const carfusePlugin = require('./carfuse');
const typographyPlugin = require('@tailwindcss/typography');
const formsPlugin = require('@tailwindcss/forms');
const aspectRatioPlugin = require('@tailwindcss/aspect-ratio');
const lineClampPlugin = require('@tailwindcss/line-clamp');

// Export all plugins as an array
module.exports = [
  typographyPlugin,
  formsPlugin({
    strategy: 'class',
  }),
  aspectRatioPlugin,
  lineClampPlugin,
  carfusePlugin,
];
