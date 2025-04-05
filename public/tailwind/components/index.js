/**
 * CarFuse Component Styles
 * This file exports all component styles used by the Tailwind plugin
 */

const buttons = require('./buttons');
const cards = require('./cards');
const status = require('./status');
const forms = require('./forms');

// Export all component styles
module.exports = {
  ...buttons,
  ...cards,
  ...status,
  ...forms,
};
