/**
 * CarFuse Animation System
 * This file contains animations and transitions used across the application
 */

module.exports = {
  animation: {
    'fade-in': 'fadeIn 0.3s ease-in-out',
    'slide-in': 'slideIn 0.3s ease-in-out',
    'slide-up': 'slideUp 0.3s ease-in-out',
    'slide-down': 'slideDown 0.3s ease-in-out',
    'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
    'bounce-slow': 'bounce 2s infinite',
  },
  
  keyframes: {
    fadeIn: {
      '0%': { opacity: '0' },
      '100%': { opacity: '1' },
    },
    slideIn: {
      '0%': { transform: 'translateX(-10px)', opacity: '0' },
      '100%': { transform: 'translateX(0)', opacity: '1' },
    },
    slideUp: {
      '0%': { transform: 'translateY(10px)', opacity: '0' },
      '100%': { transform: 'translateY(0)', opacity: '1' },
    },
    slideDown: {
      '0%': { transform: 'translateY(-10px)', opacity: '0' },
      '100%': { transform: 'translateY(0)', opacity: '1' },
    },
  },
  
  // Enhanced transitions for dark/light mode switching
  transitionProperty: {
    'colors': 'color, background-color, border-color, text-decoration-color, fill, stroke',
    'theme': 'color, background-color, border-color, text-decoration-color, fill, stroke, box-shadow, transform',
  },
  
  transitionDuration: {
    '250': '250ms',
    '350': '350ms',
    '400': '400ms',
  },
  
  transitionTimingFunction: {
    'theme': 'cubic-bezier(0.4, 0, 0.2, 1)',
  },
};
