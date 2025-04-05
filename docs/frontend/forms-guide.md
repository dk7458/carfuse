# CarFuse Forms System Guide

This guide explains how to use the CarFuse Forms System for validation, submission, and file uploads.

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

### Validation Rules

Use the `data-validate` attribute to specify validation rules:

```html
<input type="text" name="username" data-validate="required|min:3|max:20">
```

Multiple rules are separated by the pipe (`|`) character. Parameters are specified after a colon (`:`).

#### Available Validation Rules

- `required` - Field must not be empty
- `email` - Field must be a valid email address
- `min:n` - Minimum length of n characters
- `max:n` - Maximum length of n characters
- `numeric` - Field must contain only numbers
- `integer` - Field must be an integer
- `minValue:n` - Minimum numeric value of n
- `maxValue:n` - Maximum numeric value of n
- `between:min,max` - Value must be between min and max
- `url` - Field must be a valid URL
- `confirmed:fieldName` - Field must match another field
- `date` - Field must be a valid date
- `phone` - Field must be a valid phone number (Polish format)
- `postalCode` - Field must be a valid Polish postal code (XX-XXX)
- `pesel` - Field must be a valid PESEL number
- `nip` - Field must be a valid NIP number

### Form Submission

Forms with `data-cf-form` are automatically submitted via AJAX. You can configure submission behavior:

```html
<form data-cf-form data-cf-submission='{"resetOnSuccess": true, "redirectOnSuccess": "/success"}'>
  <!-- Form fields -->
</form>
```

#### Available Submission Options

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

## File Uploads

### Basic File Upload

```html
<div class="form-group">
  <label for="avatar" class="form-label">Profile Picture</label>
  <input type="file" id="avatar" name="avatar" data-cf-upload>
</div>
```

Adding the `data-cf-upload` attribute automatically enhances the file input with drag-and-drop and preview functionality.

### Advanced File Upload Options

```html
<input type="file" name="documents" 
       data-cf-upload
       data-cf-multiple="true"
       data-cf-allowed-types="image/jpeg,image/png,application/pdf"
       data-cf-max-size="10"
       data-cf-auto-upload="true">
```

#### Available File Upload Options

- `data-cf-multiple` - Allow multiple file selection (default: false)
- `data-cf-allowed-types` - Comma-separated list of allowed MIME types
- `data-cf-max-size` - Maximum file size in MB (default: 5)
- `data-cf-auto-upload` - Automatically upload files when selected (default: false)
- `data-cf-upload-url` - URL to upload files to (default: "/api/upload")

### File Upload Events

```javascript
document.querySelector('#fileInput').addEventListener('cf:upload:complete', event => {
  console.log('Upload completed', event.detail.response);
});

document.querySelector('#fileInput').addEventListener('cf:upload:error', event => {
  console.log('Upload failed', event.detail.error);
});

document.querySelector('#fileInput').addEventListener('cf:files:added', event => {
  console.log('Files added', event.detail.files);
});
```

## Custom Form Components

### Text Input with Character Counter

```html
<div class="form-group">
  <label for="description" class="form-label">Description</label>
  <input type="text" id="description" name="description" 
         class="form-input" maxlength="100"
         data-cf-text-input data-cf-show-counter="true">
</div>
```

### Clearable Input

```html
<div class="form-group">
  <label for="search" class="form-label">Search</label>
  <input type="text" id="search" name="search" 
         class="form-input"
         data-cf-text-input data-cf-clearable="true">
</div>
```

### Custom Select with Search

```html
<div class="form-group">
  <label for="country" class="form-label">Country</label>
  <select id="country" name="country" 
          data-cf-select data-cf-searchable="true">
    <option value="">Select a country</option>
    <option value="pl">Poland</option>
    <option value="de">Germany</option>
    <option value="fr">France</option>
    <!-- More options -->
  </select>
</div>
```

### Auto-resizing Textarea

```html
<div class="form-group">
  <label for="message" class="form-label">Message</label>
  <textarea id="message" name="message" 
            data-cf-textarea data-cf-auto-resize="true"></textarea>
</div>
```

### Custom Checkbox and Radio Buttons

```html
<div class="form-group">
  <input type="checkbox" id="terms" name="terms" 
         data-cf-checkbox data-cf-custom="true" data-label="I agree to the terms">
</div>

<div class="form-group">
  <input type="radio" id="option1" name="option" value="1" 
         data-cf-radio data-cf-custom="true" data-label="Option 1">
</div>
```

## Programmatic Usage

### Creating a Form Instance

```javascript
const formElement = document.querySelector('#myForm');
const form = CarFuse.forms.create(formElement, {
  validation: {
    validateOnBlur: true,
    validateOnInput: false
  },
  submission: {
    resetOnSuccess: true,
    showSuccessMessage: true
  }
});

// Validate the form
form.validate().then(isValid => {
  if (isValid) {
    console.log('Form is valid');
  }
});

// Submit the form
form.submit().then(response => {
  console.log('Form submitted successfully', response);
}).catch(error => {
  console.error('Form submission failed', error);
});
```

### Creating a File Uploader

```javascript
const fileInput = document.querySelector('#fileInput');
const uploader = CarFuse.forms.uploads.createUploader(fileInput, {
  multipleFiles: true,
  maxFileSize: 10 * 1024 * 1024, // 10MB
  allowedTypes: ['image/*', 'application/pdf'],
  autoUpload: false,
  uploadUrl: '/api/custom-upload'
});

// Listen for file selection
fileInput.addEventListener('cf:files:added', event => {
  console.log('Files added', event.detail.files);
});

// Upload files
uploader.upload().then(response => {
  console.log('Upload completed', response);
}).catch(error => {
  console.error('Upload failed', error);
});

// Remove a file
uploader.removeFile(fileId);

// Clear all files
uploader.clear();
```

## Accessibility Features

The CarFuse Forms System includes several accessibility features:

- Proper label/input associations with `for`/`id` attributes
- Error messages are announced to screen readers
- Required fields are properly marked
- Focus states for all interactive elements
- ARIA attributes for form validation
- Keyboard navigation support for custom components
