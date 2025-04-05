import { mergeClasses, validateJitClasses } from '../utils/backendIntegration';
import { useEffect } from 'react';

interface DynamicContentProps {
  backendClasses?: string;
  frontendClasses?: string;
  children: React.ReactNode;
  htmlContent?: string;
}

export function DynamicContent({
  backendClasses,
  frontendClasses,
  children,
  htmlContent
}: DynamicContentProps) {
  const mergedClasses = mergeClasses(backendClasses, frontendClasses);
  
  useEffect(() => {
    // Development helper to warn about unsupported classes
    if (process.env.NODE_ENV === 'development' && backendClasses) {
      const { isValid, invalidClasses } = validateJitClasses(backendClasses);
      if (!isValid) {
        console.warn(
          'Potentially unsupported Tailwind classes from backend:',
          invalidClasses
        );
      }
    }
  }, [backendClasses]);
  
  if (htmlContent) {
    return (
      <div 
        className={mergedClasses}
        dangerouslySetInnerHTML={{ __html: htmlContent }}
      />
    );
  }
  
  return <div className={mergedClasses}>{children}</div>;
}
