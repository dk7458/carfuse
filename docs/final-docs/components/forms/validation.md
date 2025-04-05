# Form Validation

*Last updated: 2023-11-15*

This document explains the form validation system within CarFuse, which provides comprehensive client-side validation for forms.

## Table of Contents
- [Validation Architecture](#validation-architecture)
- [Basic Usage](#basic-usage)
- [Validation Rules](#validation-rules)
- [Custom Validation Rules](#custom-validation-rules)
- [Validation UI](#validation-ui)
- [Advanced Validation](#advanced-validation)
- [Related Documentation](#related-documentation)

## Validation Architecture

The CarFuse validation system consists of:

1. **Rule Engine**: Core validation logic and rule processing
2. **Rule Registry**: Collection of validation rules
3. **Field Validator**: Validates individual form fields
4. **Form Validator**: Orchestrates validation across an entire form
5. **UI Integration**: Visual feedback for validation state

## Basic Usage

### Field-Level Validation

Add the `data-validate` attribute to form fields with pipe-separated validation rules:

```html
<input type="text" name="username" data-validate="required|min:3|max:20">
```

### Form-Level Validation

Add the `data-cf-form` attribute to forms to enable validation:

```html
<form data-cf-form>
  <!-- Form fields with validation -->
  <input type="text" name="name" data-validate="required">
  <input type="email" name="email" data-validate="required|email">
  
  <button type="submit">Submit</button>
</form>
```

### Custom Error Messages

Add custom error messages for specific validation rules:

```html
<input type="text" name="username" 
       data-validate="required|min:3|max:20"
       data-error-required="Please enter a username"
       data-error-min="Username must be at least 3 characters"
       data-error-max="Username cannot exceed 20 characters">
```

Or set a single custom error message:

```html
<input type="text" name="username" 
       data-validate="required|min:3|max:20"
       data-error-message="Please enter a valid username (3-20 characters)">
```

## Validation Rules

The validation system includes many built-in rules:

### Text Rules

- `required` - Field must not be empty
- `min:n` - Minimum length of n characters
- `max:n` - Maximum length of n characters
- `between:min,max` - Length must be between min and max characters
- `alpha` - Only alphabetical characters
- `alphanumeric` - Only alphanumeric characters
- `regex:pattern` - Match a regular expression pattern

### Numeric Rules

- `numeric` - Field must contain only numbers
- `integer` - Field must be an integer
- `decimal` - Field must be a decimal number
- `minValue:n` - Minimum numeric value of n
- `maxValue:n` - Maximum numeric value of n
- `betweenValue:min,max` - Value must be between min and max

### Format Rules

- `email` - Field must be a valid email address
- `url` - Field must be a valid URL
- `date` - Field must be a valid date
- `dateFormat:format` - Field must match the date format
- `phone` - Field must be a valid phone number
- `postalCode` - Field must be a valid postal code
- `creditCard` - Field must be a valid credit card number

### File Rules

- `fileRequired` - File must be selected
- `fileType:types` - File must be of specified type(s)
- `fileSize:maxSize` - File must not exceed maximum size in KB
- `imageMinDimensions:width,height` - Image must have minimum dimensions
- `imageMaxDimensions:width,height` - Image must not exceed maximum dimensions

### Comparison Rules

- `same:fieldName` - Must match another field
- `different:fieldName` - Must differ from another field
- `confirmed:fieldName` - Field must be confirmed (typically for passwords)

### Special Rules

- `accepted` - Field must be accepted (checkbox)
- `inList:value1,value2,...` - Value must be in the provided list
- `notInList:value1,value2,...` - Value must not be in the provided list

## Custom Validation Rules

You can add custom validation rules to the system:

```javascript
// Add a custom validation rule
CarFuse.forms.validation.addRule('customRule', (value, params) => {
  // Rule implementation - return true if valid, false if invalid
  return value === params[0];
}, 'The :attribute must match the expected value.');

// Usage in HTML
<input type="text" name="code" data-validate="required|customRule:secret123">
```

### Custom Rule with Dynamic Error Message

```javascript
CarFuse.forms.validation.addRule('divisibleBy', (value, params) => {
  const divisor = parseInt(params[0], 10);
  return parseInt(value, 10) % divisor === 0;
}, (value, params) => {
  return `The value must be divisible by ${params[0]}.`;
});

// Usage
<input type="number" name="quantity" data-validate="required|divisibleBy:5">
```

## Validation UI

The validation system provides visual feedback:

### Success State

Fields that pass validation get the `success` class:

```html
<input type="text" class="form-input success" value="Valid input">
```

### Error State

Fields that fail validation get the `error` class and display error messages:

```html
<div class="form-group">
  <label for="password">Password</label>
  <input type="password" id="password" class="form-input error" value="123">
  <div class="form-error">Password must be at least 8 characters long</div>
</div>
```

### Real-time Validation

By default, validation occurs on:
- Form submission
- Field blur (when focusing away from a field)
- Field input (for some validation types)

You can customize this behavior:

```html
<form data-cf-form data-cf-validate-options='{"validateOnInput": true, "validateOnBlur": true}'>
  <!-- Form fields -->
</form>
```

### Form-level Error Summary

Display a summary of errors at the form level:

```html
<form data-cf-form>
  <!-- Form fields -->
  
  <!-- Error summary - shows all validation errors -->
  <div data-cf-error-summary class="form-error-summary"></div>
  
  <button type="submit">Submit</button>
</form>
```

## Advanced Validation

### Conditional Validation

Apply validation rules conditionally:

```html
<input type="checkbox" id="has-company" name="has_company">
<label for="has-company">I represent a company</label>

<div class="form-group">
  <label for="company">Company Name</label>
  <input type="text" id="company" name="company" 
         data-validate="requiredIf:has_company">
</div>
```

Available conditional rules:
- `requiredIf:fieldName` - Required only if the field exists and has a truthy value
- `requiredUnless:fieldName` - Required only if the field doesn't exist or has a falsy value
- `requiredWith:fieldName` - Required only if any of the other specified fields are present
- `requiredWithAll:fieldName1,fieldName2` - Required only if all of the other specified fields are present

### Dependent Fields Validation

Validate fields that depend on other fields:

```html
<div class="form-group">
  <label for="password">Password</label>
  <input type="password" id="password" name="password" 
         data-validate="required|min:8">
</div>

<div class="form-group">
  <label for="password-confirmation">Confirm Password</label>
  <input type="password" id="password-confirmation" name="password_confirmation" 
         data-validate="required|same:password">
</div>
```

### Programmatic Validation

Use the validation system programmatically:

```javascript
// Create a validator
const validator = new CarFuse.forms.Validator({
  username: 'john_doe',
  email: 'invalid-email',
  password: '123'
});

// Define rules
validator.rules({
  username: 'required|min:3|max:20',
  email: 'required|email',
  password: 'required|min:8'
});

// Check validation
if (validator.passes()) {
  // All validations passed
  console.log('Valid data!');
} else {
  // Get validation errors
  const errors = validator.getErrors();
  console.log('Validation errors:', errors);
}
```

### Field-specific Validation Methods

You can programmatically validate specific fields:

```javascript
// Validate a specific field
CarFuse.forms.validateField(document.querySelector('#email'), 'required|email')
  .then(isValid => {
    if (isValid) {
      console.log('Email is valid!');
    } else {
      console.log('Email is invalid!');
    }
  });
```

## Related Documentation

- [Form System Overview](overview.md)
- [Input Elements](input-elements.md)
- [UI Components](../ui/overview.md)
