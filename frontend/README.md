# CarFuze Frontend Documentation

## Directory Structure

```
frontend/
├── assets/
│   ├── css/        # Global styles and CSS modules
│   ├── js/         # JavaScript utilities and services
│   └── images/     # Static images and icons
├── components/     # Reusable Vue components
├── views/         # Page-level Vue components
└── shared/        # Shared utilities and constants
```

## Adding New Components

### Vue Components
- Create components in `components/` for reusable elements
- Use `views/` for page-level components
- Follow the naming convention: PascalCase.vue
- Include component documentation in a comment block

```vue
<!-- Example component structure -->
<template>
  <!-- Template content -->
</template>

<script>
/**
 * @component ComponentName
 * @description Brief description of the component
 * @props {Type} propName - Prop description
 */
export default {
  name: 'ComponentName',
  // Component logic
}
</script>
```

### CSS Guidelines
- Use BEM methodology for class naming
- Place global styles in `assets/css/global.css`
- Component-specific styles should be scoped
- Use CSS variables for theming

### JavaScript Files
- Place utilities in `assets/js/`
- Include JSDoc documentation for all functions
- Group related functionality into modules
- Use ES6+ features with proper polyfills

## API Integration

### Using shared/utils.js
- Import the API utilities:
```javascript
import { apiRequest } from '@/shared/utils';
```

- Make API requests:
```javascript
const response = await apiRequest({
  endpoint: '/api/endpoint',
  method: 'POST',
  data: payload
});
```

### Using Centralized Utilities

```javascript
import { fetchData, handleApiError, showAlert } from '@/shared/utils';

// Making API requests
try {
    const data = await fetchData('/api/endpoint', {
        method: 'POST',
        body: payload,
        params: queryParams
    });
    // Handle success
} catch (error) {
    handleApiError(error, 'operation context');
}
```

### Error Handling
The `handleApiError` utility provides consistent error handling:
- Displays user-friendly error messages
- Logs errors to console in development
- Handles session expiration automatically
- Supports toast notifications

### Loading States
The `showLoader` utility manages loading states:
- Displays a global loading indicator
- Handles multiple concurrent requests
- Automatically hides when all requests complete

### CSRF Protection
All POST requests automatically include CSRF tokens from:
```html
<meta name="csrf-token" content="token-value">
```

### Adding New API Endpoints
1. Define the endpoint in `shared/constants.js`
2. Create corresponding service functions in `assets/js/services/`
3. Document the endpoint with JSDoc comments

### Example API Structure

```javascript
// Request
{
  endpoint: '/api/cars/search',
  method: 'POST',
  data: {
    make: 'Toyota',
    model: 'Camry',
    year: 2020
  }
}

// Response
{
  status: 200,
  data: {
    results: [{
      id: '123',
      make: 'Toyota',
      model: 'Camry',
      year: 2020,
      price: 25000
    }]
  }
}
```

## Browser Compatibility

### Minimum Requirements
- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

### Performance Best Practices
- Lazy load components and routes
- Optimize images using WebP format
- Implement proper caching strategies
- Use code splitting for large bundles
- Minimize CSS and JavaScript files
- Enable Gzip compression
- Use service workers for offline functionality

### Development Tools
- Vue DevTools for debugging
- Lighthouse for performance auditing
- webpack-bundle-analyzer for bundle optimization
