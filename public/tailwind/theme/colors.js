/**
 * CarFuse Color System
 * This file contains all color definitions used in the application
 */

const colors = {
  transparent: 'transparent',
  current: 'currentColor',

  // Primary brand colors
  primary: {
    lightest: 'var(--color-primary-lightest)',
    lighter: 'var(--color-primary-lighter)',
    light: 'var(--color-primary-light)',
    DEFAULT: 'var(--color-primary)',
    dark: 'var(--color-primary-dark)',
    darker: 'var(--color-primary-darker)',
    darkest: 'var(--color-primary-darkest)',
  },

  // Secondary brand colors
  secondary: {
    lightest: 'var(--color-secondary-lightest)',
    lighter: 'var(--color-secondary-lighter)',
    light: 'var(--color-secondary-light)',
    DEFAULT: 'var(--color-secondary)',
    dark: 'var(--color-secondary-dark)',
    darker: 'var(--color-secondary-darker)',
    darkest: 'var(--color-secondary-darkest)',
  },

  // Accent color
  accent: {
    lightest: 'var(--color-accent-lightest)',
    lighter: 'var(--color-accent-lighter)',
    light: 'var(--color-accent-light)',
    DEFAULT: 'var(--color-accent)',
    dark: 'var(--color-accent-dark)',
    darker: 'var(--color-accent-darker)',
    darkest: 'var(--color-accent-darkest)',
  },

  // Semantic surface colors
  surface: {
    white: 'var(--color-surface-white)',
    lightest: 'var(--color-surface-lightest)',
    lighter: 'var(--color-surface-lighter)',
    light: 'var(--color-surface-light)',
    DEFAULT: 'var(--color-surface)',
    dark: 'var(--color-surface-dark)',
    darker: 'var(--color-surface-darker)',
    darkest: 'var(--color-surface-darkest)',
    black: 'var(--color-surface-black)',
  },

  // Text colors
  text: {
    primary: 'var(--color-text-primary)',
    secondary: 'var(--color-text-secondary)',
    tertiary: 'var(--color-text-tertiary)',
    disabled: 'var(--color-text-disabled)',
    inverse: 'var(--color-text-inverse)',
  },

  // Status colors
  status: {
    info: {
      lighter: 'var(--color-info-lighter)',
      light: 'var(--color-info-light)',
      DEFAULT: 'var(--color-info)',
      dark: 'var(--color-info-dark)',
    },
    success: {
      lighter: 'var(--color-success-lighter)',
      light: 'var(--color-success-light)',
      DEFAULT: 'var(--color-success)',
      dark: 'var(--color-success-dark)',
    },
    warning: {
      lighter: 'var(--color-warning-lighter)',
      light: 'var(--color-warning-light)',
      DEFAULT: 'var(--color-warning)',
      dark: 'var(--color-warning-dark)',
    },
    error: {
      lighter: 'var(--color-error-lighter)',
      light: 'var(--color-error-light)',
      DEFAULT: 'var(--color-error)',
      dark: 'var(--color-error-dark)',
    },
  },

  // UI state colors
  state: {
    hover: 'var(--color-state-hover)',
    active: 'var(--color-state-active)',
    selected: 'var(--color-state-selected)',
    disabled: 'var(--color-state-disabled)',
    focus: 'var(--color-state-focus)',
  },

  // Border colors
  border: {
    light: 'var(--color-border-light)',
    DEFAULT: 'var(--color-border)',
    dark: 'var(--color-border-dark)',
  },

  // Shadow colors (for box-shadow)
  shadow: {
    light: 'var(--color-shadow-light)',
    DEFAULT: 'var(--color-shadow)',
    dark: 'var(--color-shadow-dark)',
  },
};

module.exports = colors;
