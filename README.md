# CarFuse Forms System

A comprehensive, modular form handling system for CarFuse web applications with validation, submission handling, custom components, and file uploads.

## Features

- **Form Validation**: Client-side validation with customizable rules
- **Form Submission**: AJAX form submission with loading states and error handling
- **Form Components**: Enhanced form controls with additional functionality
- **File Uploads**: Drag & drop file uploads with preview and progress tracking
- **Form Templates**: Pre-built form templates for common use cases
- **Form Utilities**: Helper functions for working with forms
- **Accessibility**: Built with accessibility in mind

## Installation

Include the required JavaScript and CSS files in your HTML:

```html
<!-- CSS -->
<link href="/css/carfuse-forms.css" rel="stylesheet">

<!-- JavaScript -->
<script src="/js/forms/validation/index.js"></script>
<script src="/js/forms/submission/index.js"></script>
<script src="/js/forms/components/index.js"></script>
<script src="/js/forms/uploads/index.js"></script>
<script src="/js/forms/utils/index.js"></script>
<script src="/js/forms/templates/index.js"></script>
<script src="/js/forms/index.js"></script>
```

## Usage

### Basic Form

```html
<form data-cf-form>
  <div class="form-group">
    <label for="name" class="form-label required">Name</label>
    <input type="text" id="name" name="name" class="form-input" 
           data-validate="required|min:2|max:50">
  </div>
  
  <div class="form-group">
    <label for="email" class="form-label required">Email</label>
    <input type="email" id="email" name="email" class="form-input" 
           data-validate="required|email">
  </div>
  
  <button type="submit" class="btn btn-primary">Submit</button>
</form>
```

### Enhanced Components

```html
<div class="form-group">
  <label for="country" class="form-label">Country</label>
  <select id="country" name="country" data-cf-select data-cf-searchable="true">
    <option value="">Select a country</option>
    <option value="pl">Poland</option>
    <option value="de">Germany</option>
    <!-- More options -->
  </select>
</div>

<div class="form-group">
  <label for="bio" class="form-label">Bio</label>
  <textarea id="bio" name="bio" data-cf-textarea data-cf-auto-resize="true" maxlength="200" data-cf-show-counter="true"></textarea>
</div>
```

### File Uploads

```html
<div class="form-group">
  <label for="documents" class="form-label">Documents</label>
  <input type="file" id="documents" name="documents" 
         data-cf-upload
         data-cf-multiple="true" 
         data-cf-allowed-types="application/pdf,.doc,.docx">
</div>
```

### Form Templates

```javascript
// Create a login form
const loginForm = CarFuse.forms.templates.createLoginForm({
  action: '/auth/login',
  redirectUrl: '/dashboard'
});
document.getElementById('login-container').appendChild(loginForm);

// Create a registration form
const registrationForm = CarFuse.forms.templates.createRegistrationForm();
document.getElementById('registration-container').appendChild(registrationForm);
```

## Configuration

The form system can be configured globally:

```javascript
// Initialize with custom options
CarFuse.forms.init({
  validation: {
    validateOnBlur: true,
    validateOnInput: false
  },
  submission: {
    resetOnSuccess: true,
    showSuccessMessage: true
  },
  components: {
    defaultTheme: 'custom'
  },
  uploads: {
    maxFileSize: 10 * 1024 * 1024, // 10MB
    uploadUrl: '/api/custom-upload'
  }
});
```

## API Reference

See the [Forms Guide](/public/docs/forms-guide.md) for detailed API documentation.

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Opera (latest)
- Mobile browsers (iOS Safari, Android Chrome)

## Development

### Testing

Run the integration tests:

```bash
npm test -- --testPathPattern=forms-integration-test.js
```

### Building

To build the CSS files:

```bash
npm run build:css
```

## License

This project is licensed under the MIT License - see the LICENSE file for details.
