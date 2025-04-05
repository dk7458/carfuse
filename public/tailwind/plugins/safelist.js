/**
 * CarFuse Tailwind Safelist
 * This file defines classes that should be included in the build
 * even if they're not found in the content files
 */

module.exports = [
  'bg-blue-100', 'text-blue-800',
  'bg-green-100', 'text-green-800',
  'bg-red-100', 'text-red-800',
  'bg-yellow-100', 'text-yellow-800',
  'bg-purple-100', 'text-purple-800',
  // Dark variants
  'dark:bg-blue-900', 'dark:bg-opacity-30', 'dark:text-blue-300',
  'dark:bg-green-900', 'dark:bg-opacity-30', 'dark:text-green-300',
  'dark:bg-red-900', 'dark:bg-opacity-30', 'dark:text-red-300',
  'dark:bg-yellow-900', 'dark:bg-opacity-30', 'dark:text-yellow-300',
  'dark:bg-purple-900', 'dark:bg-opacity-30', 'dark:text-purple-300',
  // Theme transition classes
  'transition-theme', 'transition-theme-slow', 'transition-theme-fast',
  'theme-auto', 'dark', 'light',
  // Status indicator classes
  'status-active', 'status-completed', 'status-pending', 'status-canceled',
  'badge-valid', 'badge-invalid', 'badge-warning',
  'badge-active', 'badge-inactive',
];
