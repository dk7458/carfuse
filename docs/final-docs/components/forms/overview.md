# Form System Overview

*Last updated: 2023-11-15*

This document provides an overview of the CarFuse Forms System, which manages form creation, validation, submission, and file uploads.

## Table of Contents
- [Form System Architecture](#form-system-architecture)
- [Core Features](#core-features)
- [Basic Usage](#basic-usage)
- [Form Components](#form-components)
- [Advanced Features](#advanced-features)
- [Framework Integration](#framework-integration)
- [Related Documentation](#related-documentation)

## Form System Architecture

The CarFuse Forms System follows a modular architecture:

1. **Core Layer**: Base form functionality and utilities
2. **Validation Layer**: Input validation and error handling
3. **UI Layer**: Form input components and styling
4. **Integration Layer**: Framework-specific adaptors (Alpine.js, HTMX, etc.)

This layered approach ensures consistent behavior while allowing for customization.

## Core Features

The Forms System provides the following features:

| Feature | Description | Documentation |
|---------|-------------|---------------|
| Form Creation | Create and configure forms declaratively or programmatically | This document |
| Validation | Client-side validation with extensive rule support | [Validation](validation.md) |
| AJAX Submission | Asynchronous form submission with proper feedback | This document |
| File Uploads | Enhanced file upload with preview and validation | This document |
| Input Components | Enhanced form controls with consistency and accessibility | [Input Elements](input-elements.md) |
| Error Handling | Comprehensive error display and management | [Validation](validation.md) |

## Basic Usage

### Creating a Simple Form

```html
<form data-cf-form>
  <div class="form-group">
    <label for="name" class="form-label">Name</label>
    <input type="text" id="name" name="name" class="form-input" 
           data-validate="required|min:2|max:50">
  </div>
  
  <div class="form-group">
    <label for="email" class="form-label">Email</label>
    <input type="email" id="email" name="email" class="form-input" 
           data-validate="required|email">
    <div class="form-help">We'll never share your email with anyone else.</div>
  </div>
  
  <button type="submit" class="btn btn-primary">Submit</button>
</form>
```

The `data-cf-form` attribute automatically enables form validation and AJAX submission.

### Form Submission

Forms with `data-cf-form` are automatically submitted via AJAX. You can configure submission behavior:

```html
<form data-cf-form data-cf-submission='{"resetOnSuccess": true, "redirectOnSuccess": "/success"}'>
  <!-- Form fields -->
</form>
```

Available submission options:

- `resetOnSuccess` - Reset form after successful submission (default: true)
- `redirectOnSuccess` - Redirect to URL after successful submission
- `showLoadingIndicator` - Show loading indicator during submission (default: true)
- `showSuccessMessage` - Show success toast message (default: true)
- `showErrorMessage` - Show error toast message (default: true)

### Event Handling

Listen for form events:

```javascript
document.querySelector('#myForm').addEventListener('cf:form:success', event => {
  console.log('Form submitted successfully', event.detail.data);
});

document.querySelector('#myForm').addEventListener('cf:form:error', event => {
  console.log('Form submission failed', event.detail.error);
});
```

## Form Components

The form system includes several enhanced input components:

### Text Inputs

```html
<div class="form-group">
  <label for="username" class="form-label">Username</label>
  <input type="text" id="username" name="username" 
         class="form-input"
         data-cf-text-input
         data-cf-clearable="true"
         data-cf-show-counter="true"
         maxlength="20">
</div>
```

### Select Inputs

```html
<div class="form-group">
  <label for="country" class="form-label">Country</label>
  <select id="country" name="country" 
          data-cf-select 
          data-cf-searchable="true">
    <option value="">Select a country</option>
    <option value="us">United States</option>
    <option value="ca">Canada</option>
    <option value="mx">Mexico</option>
    <!-- More options -->
  </select>
</div>
```

### Checkbox and Radio Groups

```html
<div class="form-group">
  <fieldset>
    <legend class="form-label">Notification Preferences</legend>
    
    <div class="checkbox-group">
      <input type="checkbox" id="email-notifications" name="notifications[]" 
             value="email" data-cf-checkbox>
      <label for="email-notifications">Email Notifications</label>
    </div>
    
    <div class="checkbox-group">
      <input type="checkbox" id="sms-notifications" name="notifications[]" 
             value="sms" data-cf-checkbox>
      <label for="sms-notifications">SMS Notifications</label>
    </div>
  </fieldset>
</div>
```

### File Uploads

```html
<div class="form-group">
  <label for="avatar" class="form-label">Profile Picture</label>
  <input type="file" id="avatar" name="avatar" 
         data-cf-upload
         data-cf-max-size="2"
         data-cf-allowed-types="image/jpeg,image/png">
</div>
```

## Advanced Features

### File Uploads

The file upload component provides enhanced functionality:

```html
<input type="file" name="documents" 
       data-cf-upload
       data-cf-multiple="true"
       data-cf-allowed-types="image/jpeg,image/png,application/pdf"
       data-cf-max-size="10"
       data-cf-auto-upload="true">
```

Available options:

- `data-cf-multiple` - Allow multiple file selection (default: false)
- `data-cf-allowed-types` - Comma-separated list of allowed MIME types
- `data-cf-max-size` - Maximum file size in MB (default: 5)
- `data-cf-auto-upload` - Automatically upload files when selected (default: false)
- `data-cf-upload-url` - URL to upload files to (default: "/api/upload")

Listen for file upload events:

```javascript
document.querySelector('#fileInput').addEventListener('cf:upload:complete', event => {
  console.log('Upload completed', event.detail.response);
});

document.querySelector('#fileInput').addEventListener('cf:upload:error', event => {
  console.log('Upload failed', event.detail.error);
});
```

### Dynamic Form Generation

Forms can be dynamically generated and controlled using the Forms API:

```javascript
// Create a form instance
const form = CarFuse.forms.create('#dynamicForm', {
  fields: [
    {
      type: 'text',
      name: 'username',
      label: 'Username',
      validation: 'required|min:3'
    },
    {
      type: 'email',
      name: 'email',
      label: 'Email Address',
      validation: 'required|email'
    },
    {
      type: 'password',
      name: 'password',
      label: 'Password',
      validation: 'required|min:8'
    }
  ],
  submission: {
    url: '/api/register',
    method: 'POST'
  }
});

// Add a new field
form.addField({
  type: 'checkbox',
  name: 'terms',
  label: 'I agree to the terms',
  validation: 'required'
});

// Submit the form programmatically
form.submit().then(response => {
  console.log('Form submitted successfully', response);
}).catch(error => {
  console.error('Form submission failed', error);
});
```

## Framework Integration

### Alpine.js Integration

The form system integrates with Alpine.js:

```html
<form x-data="form({
    endpoint: '/api/submit',
    method: 'POST',
    rules: {
      name: 'required|min:2',
      email: 'required|email'
    }
  })" @submit.prevent="submit">
  
  <div class="form-group">
    <label for="name">Name</label>
    <input 
      id="name" 
      type="text" 
      x-model="formData.name"
      @blur="handleBlur('name')"
      :class="{'border-red-500': errors.name && touched.name}"
    >
    <p x-show="errors.name && touched.name" x-text="errors.name" class="text-red-500"></p>
  </div>
  
  <div class="form-group">
    <label for="email">Email</label>
    <input 
      id="email" 
      type="email" 
      x-model="formData.email"
      @blur="handleBlur('email')"
      :class="{'border-red-500': errors.email && touched.email}"
    >
    <p x-show="errors.email && touched.email" x-text="errors.email" class="text-red-500"></p>
  </div>
  
  <button type="submit" :disabled="loading">
    <span x-show="loading">Submitting...</span>
    <span x-show="!loading">Submit</span>
  </button>
  
  <div x-show="success" class="text-green-500">Form submitted successfully!</div>
  <div x-show="error" class="text-red-500" x-text="error"></div>
</form>
```

### HTMX Integration

The form system integrates with HTMX:

```html
<form hx-post="/api/submit" 
      hx-target="#result" 
      hx-indicator="#spinner"
      data-cf-validate>
      
  <div class="form-group">
    <label for="name">Name</label>
    <input type="text" id="name" name="name" data-validate="required">
  </div>
  
  <div class="form-group">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" data-validate="required|email">
  </div>
  
  <button type="submit">Submit</button>
  <div id="spinner" class="htmx-indicator">Loading...</div>
</form>

<div id="result"></div>
```

## Related Documentation

- [Form Validation](validation.md)
- [Input Elements](input-elements.md)
- [UI Components](../ui/overview.md)
- [Security CSRF Protection](../../security/csrf-protection.md)
