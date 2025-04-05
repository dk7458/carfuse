/**
 * CarFuse Tailwind Plugin Registration
 * This file registers all Tailwind plugins and can be used
 * to quickly add/remove plugins from the configuration
 */

const registerPlugins = (plugins) => {
  // Core plugins
  const typography = require('@tailwindcss/typography');
  const forms = require('@tailwindcss/forms');
  const aspectRatio = require('@tailwindcss/aspect-ratio');
  const lineClamp = require('@tailwindcss/line-clamp');
  
  // Custom plugins
  const carfusePlugin = require('./carfuse');
  
  return [
    typography,
    forms({
      strategy: 'class',
    }),
    aspectRatio,
    lineClamp,
    carfusePlugin,
    ...plugins,
  ];
};

module.exports = registerPlugins;
