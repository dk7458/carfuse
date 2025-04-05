/**
 * CarFuse Typography System
 * This file contains font families, sizes, and typography plugin configuration
 */

module.exports = {
  fontSize: {
    'xs': ['0.75rem', { lineHeight: '1rem' }],
    'sm': ['0.875rem', { lineHeight: '1.25rem' }],
    'base': ['1rem', { lineHeight: '1.5rem' }],
    'lg': ['1.125rem', { lineHeight: '1.75rem' }],
    'xl': ['1.25rem', { lineHeight: '1.75rem' }],
    '2xl': ['1.5rem', { lineHeight: '2rem' }],
    '3xl': ['1.875rem', { lineHeight: '2.25rem' }],
    '4xl': ['2.25rem', { lineHeight: '2.5rem' }],
    '5xl': ['3rem', { lineHeight: '1' }],
    '6xl': ['3.75rem', { lineHeight: '1' }],
    '7xl': ['4.5rem', { lineHeight: '1' }],
    '8xl': ['6rem', { lineHeight: '1' }],
    '9xl': ['8rem', { lineHeight: '1' }],
  },
  
  fontFamily: {
    sans: [
      'Inter',
      'system-ui',
      '-apple-system',
      'BlinkMacSystemFont',
      '"Segoe UI"',
      'Roboto',
      '"Helvetica Neue"',
      'Arial',
      '"Noto Sans"',
      'sans-serif',
      '"Apple Color Emoji"',
      '"Segoe UI Emoji"',
      '"Segoe UI Symbol"',
      '"Noto Color Emoji"',
    ],
    // Add Polish-optimized font stacks
    'polish': [
      'Inter',
      'Lato',
      'Roboto',
      'system-ui',
      'sans-serif'
    ],
    // Add monospace font for code/dashboard data
    'mono': [
      'ui-monospace',
      'SFMono-Regular',
      'Menlo',
      'Monaco',
      'Consolas',
      'Liberation Mono',
      'Courier New',
      'monospace',
    ],
  },
  
  // Typography plugin configuration
  typographyPlugin: (theme) => ({
    DEFAULT: {
      css: {
        color: theme('colors.text.primary'),
        maxWidth: '65ch',
        '[class~="lead"]': {
          color: theme('colors.text.secondary'),
        },
        a: {
          color: theme('colors.primary.DEFAULT'),
          textDecoration: 'none',
          '&:hover': {
            textDecoration: 'underline',
          },
        },
        // Polish quotation marks
        'blockquote p:first-of-type::before': { content: '"„"' },
        'blockquote p:last-of-type::after': { content: '"""' },
        // Support for Polish special characters in headings
        h1: {
          fontWeight: '600',
          letterSpacing: '-0.025em',
        },
        h2: {
          fontWeight: '600',
          letterSpacing: '-0.025em',
        },
        h3: {
          fontWeight: '600',
        },
        // Improve spacing for paragraphs with Polish accents
        p: {
          lineHeight: '1.6',
          marginTop: '1.25em',
          marginBottom: '1.25em',
        },
        // Adjust list spacing for Polish text
        ul: {
          marginTop: '1em',
          marginBottom: '1em',
        },
        ol: {
          marginTop: '1em',
          marginBottom: '1em',
        },
      },
    },
    // Polish variant with adjusted metrics
    polish: {
      css: {
        '--tw-prose-body': theme('colors.text.primary'),
        '--tw-prose-headings': theme('colors.text.primary'),
        '--tw-prose-quotes': theme('colors.text.secondary'),
        // Polish quotes
        'blockquote p:first-of-type::before': { content: '"„"' },
        'blockquote p:last-of-type::after': { content: '"""' },
        fontFamily: theme('fontFamily.polish').join(', '),
      }
    },
    // Dark mode typography
    dark: {
      css: {
        color: theme('colors.text.dark-primary'),
        '--tw-prose-body': theme('colors.text.dark-primary'),
        '--tw-prose-headings': theme('colors.text.dark-primary'),
        '--tw-prose-lead': theme('colors.text.dark-secondary'),
        '--tw-prose-links': theme('colors.primary.light'),
        '--tw-prose-bold': theme('colors.text.dark-primary'),
        '--tw-prose-counters': theme('colors.text.dark-secondary'),
        '--tw-prose-bullets': theme('colors.text.dark-tertiary'),
        '--tw-prose-hr': theme('colors.dark.border'),
        '--tw-prose-quotes': theme('colors.text.dark-secondary'),
        '--tw-prose-quote-borders': theme('colors.dark.border'),
        '--tw-prose-captions': theme('colors.text.dark-tertiary'),
        '--tw-prose-code': theme('colors.text.dark-primary'),
        '--tw-prose-pre-code': theme('colors.text.dark-primary'),
        '--tw-prose-pre-bg': theme('colors.dark.card'),
        '--tw-prose-th-borders': theme('colors.dark.border'),
        '--tw-prose-td-borders': theme('colors.dark.border'),
        
        a: {
          color: theme('colors.primary.light'),
        },
      },
    },
  }),
};
