import { focusRingClasses, reducedMotionClasses } from '../../utils/a11y';
// ...existing code...

export const Button = ({ 
  children, 
  onClick, 
  variant = 'primary', 
  disabled = false,
  ariaLabel,
  // ...existing code...
}) => {
  // ...existing code...
  
  return (
    <button 
      className={`
        ${baseStyles}
        ${variantStyles[variant]}
        ${focusRingClasses}
        ${reducedMotionClasses}
        ${disabled ? 'opacity-50 cursor-not-allowed' : ''}
        // ...existing code...
      `}
      onClick={onClick}
      disabled={disabled}
      aria-label={ariaLabel}
      // ...existing code...
    >
      {children}
    </button>
  );
};
