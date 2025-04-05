/**
 * Typography System Reference Guide
 * 
 * This file provides reference information about our typography system.
 * It's not imported into the Tailwind config, but serves as documentation.
 */

module.exports = {
  /**
   * Font Size Scale - Following an 8pt scale with 1.25 ratio
   * Each step is approximately 1.25x larger than the previous
   */
  fontSizeScale: {
    'xs':    ['0.75rem',  { lineHeight: '1rem' }],      // 12px
    'sm':    ['0.875rem', { lineHeight: '1.25rem' }],   // 14px
    'base':  ['1rem',     { lineHeight: '1.5rem' }],    // 16px
    'lg':    ['1.125rem', { lineHeight: '1.75rem' }],   // 18px
    'xl':    ['1.25rem',  { lineHeight: '1.75rem' }],   // 20px
    '2xl':   ['1.5rem',   { lineHeight: '2rem' }],      // 24px
    '3xl':   ['1.875rem', { lineHeight: '2.25rem' }],   // 30px
    '4xl':   ['2.25rem',  { lineHeight: '2.5rem' }],    // 36px
    '5xl':   ['3rem',     { lineHeight: '1' }],         // 48px
    '6xl':   ['3.75rem',  { lineHeight: '1' }],         // 60px
    '7xl':   ['4.5rem',   { lineHeight: '1' }],         // 72px
    '8xl':   ['6rem',     { lineHeight: '1' }],         // 96px
    '9xl':   ['8rem',     { lineHeight: '1' }],         // 128px
  },

  /**
   * Line Height Scale
   * Used for fine-tuning typography across different contexts
   */
  lineHeightScale: {
    'none':     '1',      // No line height (use font size only)
    'tight':    '1.25',   // Tight headings
    'snug':     '1.375',  // Reduced space
    'normal':   '1.5',    // Standard text
    'relaxed':  '1.625',  // Slightly more space
    'loose':    '2',      // Maximum space
  },

  /**
   * Letter Spacing Scale
   * For adjusting letter spacing in different typographic contexts
   */
  letterSpacingScale: {
    'tighter': '-0.05em',  // Very tight (headings)
    'tight':   '-0.025em', // Slightly tight (headings)
    'normal':  '0em',      // Normal spacing
    'wide':    '0.025em',  // Slightly wide (subheadings)
    'wider':   '0.05em',   // Wide (labels, all-caps)
    'widest':  '0.1em',    // Very wide (small caps, labels)
  },

  /**
   * Font Weight Reference
   * Standard font weight values and their common names
   */
  fontWeightReference: {
    'thin':       100,
    'extralight': 200,
    'light':      300,
    'normal':     400,
    'medium':     500,
    'semibold':   600,
    'bold':       700,
    'extrabold':  800,
    'black':      900,
  },

  /**
   * Typography Usage Guidelines
   */
  usageGuidelines: {
    headings: {
      h1: 'text-4xl font-bold md:text-5xl',
      h2: 'text-3xl font-bold md:text-4xl',
      h3: 'text-2xl font-semibold md:text-3xl',
      h4: 'text-xl font-semibold md:text-2xl',
      h5: 'text-lg font-medium md:text-xl',
      h6: 'text-base font-medium md:text-lg',
    },
    body: {
      large: 'text-lg leading-relaxed',
      base: 'text-base leading-normal',
      small: 'text-sm leading-normal',
      tiny: 'text-xs leading-normal',
    },
    display: {
      large: 'text-7xl font-bold leading-tight tracking-tight',
      medium: 'text-6xl font-bold leading-tight tracking-tight',
      small: 'text-5xl font-bold leading-tight tracking-tight',
    }
  }
};
