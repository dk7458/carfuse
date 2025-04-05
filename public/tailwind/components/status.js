/**
 * CarFuse Status Indicators
 * Tailwind components for displaying statuses
 */

module.exports = function({ addComponents, theme }) {
  // Get colors from theme
  const colors = theme('colors');
  
  // Define status indicator components
  const statusComponents = {
    // Base status styles
    '.status-base': {
      display: 'inline-flex',
      alignItems: 'center',
      justifyContent: 'center',
      fontWeight: theme('fontWeight.medium'),
    },
    
    // Badge components
    '.badge': {
      '@apply status-base': {},
      borderRadius: '9999px',
      fontSize: theme('fontSize.xs')[0],
      lineHeight: theme('fontSize.xs')[1].lineHeight,
      padding: `${theme('padding.0.5')} ${theme('padding.2.5')}`,
    },
    
    // Badge sizes
    '.badge-sm': {
      padding: `${theme('padding.0.5')} ${theme('padding.2')}`,
      fontSize: theme('fontSize.xs')[0],
    },
    '.badge-md': {
      padding: `${theme('padding.0.5')} ${theme('padding.2.5')}`,
      fontSize: theme('fontSize.xs')[0],
    },
    '.badge-lg': {
      padding: `${theme('padding.1')} ${theme('padding.3')}`,
      fontSize: theme('fontSize.sm')[0],
    },
    
    // Pill components
    '.pill': {
      '@apply status-base': {},
      borderRadius: '9999px',
      fontSize: theme('fontSize.sm')[0],
      lineHeight: theme('fontSize.sm')[1].lineHeight,
      padding: `${theme('padding.1')} ${theme('padding.3')}`,
    },
    
    // Pill sizes
    '.pill-sm': {
      padding: `${theme('padding.0.5')} ${theme('padding.2.5')}`,
      fontSize: theme('fontSize.xs')[0],
    },
    '.pill-md': {
      padding: `${theme('padding.1')} ${theme('padding.3')}`,
      fontSize: theme('fontSize.sm')[0],
    },
    '.pill-lg': {
      padding: `${theme('padding.1.5')} ${theme('padding.4')}`,
      fontSize: theme('fontSize.base')[0],
    },
    
    // Status dots
    '.status-dot': {
      display: 'inline-block',
      height: theme('height.2.5'),
      width: theme('width.2.5'),
      borderRadius: '9999px',
    },
    
    // Status dot sizes
    '.status-dot-sm': {
      height: theme('height.2'),
      width: theme('width.2'),
    },
    '.status-dot-md': {
      height: theme('height.2.5'),
      width: theme('width.2.5'),
    },
    '.status-dot-lg': {
      height: theme('height.3'),
      width: theme('width.3'),
    },
    
    // Status text
    '.status-text': {
      fontWeight: theme('fontWeight.medium'),
    },
    
    // Status text sizes
    '.status-text-sm': {
      fontSize: theme('fontSize.xs')[0],
    },
    '.status-text-md': {
      fontSize: theme('fontSize.sm')[0],
    },
    '.status-text-lg': {
      fontSize: theme('fontSize.base')[0],
    },
    
    // Icon utilities
    '.status-with-icon': {
      display: 'inline-flex',
      alignItems: 'center',
    },
    '.status-icon': {
      marginLeft: theme('margin.-0.5'),
      marginRight: theme('margin.1'),
      flexShrink: '0',
    },
    '.status-icon-sm': {
      height: theme('height.3'),
      width: theme('width.3'),
    },
    '.status-icon-md': {
      height: theme('height.4'),
      width: theme('width.4'),
    },
    '.status-icon-lg': {
      height: theme('height.5'),
      width: theme('width.5'),
    },
    
    // Dashboard elements
    '.dashboard-card': {
      backgroundColor: theme('colors.white'),
      borderRadius: theme('borderRadius.lg'),
      boxShadow: theme('boxShadow.sm'),
      padding: theme('padding.6'),
      '@media (prefers-color-scheme: dark)': {
        backgroundColor: theme('colors.gray.800'),
        borderColor: theme('colors.gray.700'),
      },
    },
    
    '.stat-card': {
      '@apply dashboard-card': {},
      display: 'flex',
      flexDirection: 'column',
      gap: theme('gap.2'),
    },
    
    '.stat-card-title': {
      color: theme('colors.gray.600'),
      fontSize: theme('fontSize.sm')[0],
      '@media (prefers-color-scheme: dark)': {
        color: theme('colors.gray.400'),
      },
    },
    
    '.stat-card-value': {
      color: theme('colors.gray.900'),
      fontSize: theme('fontSize.3xl')[0],
      fontWeight: theme('fontWeight.semibold'),
      lineHeight: theme('lineHeight.tight'),
      '@media (prefers-color-scheme: dark)': {
        color: theme('colors.white'),
      },
    },
    
    '.data-table-container': {
      borderRadius: theme('borderRadius.lg'),
      overflow: 'hidden',
      border: `1px solid ${theme('colors.gray.200')}`,
      '@media (prefers-color-scheme: dark)': {
        borderColor: theme('colors.gray.700'),
      },
    },
    
    '.data-table': {
      width: '100%',
      borderCollapse: 'collapse',
    },
    
    '.data-table-header': {
      backgroundColor: theme('colors.gray.50'),
      '@media (prefers-color-scheme: dark)': {
        backgroundColor: theme('colors.gray.800'),
      },
    },
    
    '.data-table-th': {
      padding: theme('padding.4'),
      fontSize: theme('fontSize.sm')[0],
      fontWeight: theme('fontWeight.medium'),
      color: theme('colors.gray.700'),
      textAlign: 'left',
      '@media (prefers-color-scheme: dark)': {
        color: theme('colors.gray.300'),
      },
    },
    
    '.data-table-cell': {
      padding: theme('padding.4'),
      fontSize: theme('fontSize.sm')[0],
      borderTop: `1px solid ${theme('colors.gray.200')}`,
      '@media (prefers-color-scheme: dark)': {
        borderColor: theme('colors.gray.700'),
      },
    },
    
    '.chart-container': {
      padding: theme('padding.4'),
      backgroundColor: theme('colors.white'),
      borderRadius: theme('borderRadius.lg'),
      boxShadow: theme('boxShadow.sm'),
      '@media (prefers-color-scheme: dark)': {
        backgroundColor: theme('colors.gray.800'),
        borderColor: theme('colors.gray.700'),
      },
    },
    
    '.dashboard-grid': {
      display: 'grid',
      gap: theme('gap.6'),
      gridTemplateColumns: 'repeat(1, minmax(0, 1fr))',
      '@screen sm': {
        gridTemplateColumns: 'repeat(2, minmax(0, 1fr))',
      },
      '@screen lg': {
        gridTemplateColumns: 'repeat(4, minmax(0, 1fr))',
      },
    },
    
    '.dashboard-section': {
      marginBottom: theme('margin.8'),
    },
    
    '.dashboard-section-title': {
      fontSize: theme('fontSize.xl')[0],
      fontWeight: theme('fontWeight.semibold'),
      marginBottom: theme('margin.4'),
      color: theme('colors.gray.900'),
      '@media (prefers-color-scheme: dark)': {
        color: theme('colors.white'),
      },
    },
  };
  
  // Add components to Tailwind
  addComponents(statusComponents);
};
