# Form Input Elements

*Last updated: 2023-11-15*

This document details the form input elements available in the CarFuse Forms System and how to use them effectively.

## Table of Contents
- [Text Inputs](#text-inputs)
- [Select Elements](#select-elements)
- [Checkbox and Radio Elements](#checkbox-and-radio-elements)
- [Textarea Elements](#textarea-elements)
- [File Inputs](#file-inputs)
- [Special Input Types](#special-input-types)
- [Input Groups](#input-groups)
- [Accessibility Features](#accessibility-features)
- [Related Documentation](#related-documentation)

## Text Inputs

### Basic Text Input

```html
<div class="form-group">
  <label for="name" class="form-label">Name</label>
  <input type="text" id="name" name="name" class="form-input">
</div>
```

### Enhanced Text Input

Text inputs can be enhanced with additional functionality:

```html
<div class="form-group">
  <label for="description" class="form-label">Description</label>
  <input type="text" id="description" name="description" 
         class="form-input" 
         data-cf-text-input
         data-cf-show-counter="true"
         data-cf-clearable="true"
         maxlength="100">
</div>
```

Available options:
- `data-cf-text-input` - Activates enhanced functionality
- `data-cf-show-counter` - Shows character counter for inputs with maxlength
- `data-cf-clearable` - Adds a clear button to reset the input
- `data-cf-mask` - Applies formatting mask (e.g., "999-99-9999" for SSN)

### Input States

Text inputs can have various states:

```html
<!-- Default state -->
<input type="text" class="form-input">

<!-- Focus state (applied automatically on focus) -->
<input type="text" class="form-input focus">

<!-- Error state -->
<input type="text" class="form-input error">

<!-- Success state -->
<input type="text" class="form-input success">

<!-- Disabled state -->
<input type="text" class="form-input" disabled>

<!-- Read-only state -->
<input type="text" class="form-input" readonly>
```

## Select Elements

### Basic Select

```html
<div class="form-group">
  <label for="country" class="form-label">Country</label>
  <select id="country" name="country" class="form-select">
    <option value="">Select a country</option>
    <option value="us">United States</option>
    <option value="ca">Canada</option>
    <option value="mx">Mexico</option>
  </select>
</div>
```

### Enhanced Select

Select elements can be enhanced with search functionality and other features:

```html
<div class="form-group">
  <label for="country" class="form-label">Country</label>
  <select id="country" name="country" 
          data-cf-select
          data-cf-searchable="true"
          data-cf-placeholder="Choose a country">
    <option value="">Select a country</option>
    <option value="us">United States</option>
    <option value="ca">Canada</option>
    <option value="mx">Mexico</option>
    <!-- More options -->
  </select>
</div>
```

Available options:
- `data-cf-select` - Activates enhanced functionality
- `data-cf-searchable` - Enables search functionality
- `data-cf-placeholder` - Sets the placeholder text
- `data-cf-allow-clear` - Allows clearing the selection
- `data-cf-max-items` - For multi-select, sets maximum number of selections

### Multiple Select

```html
<div class="form-group">
  <label for="skills" class="form-label">Skills</label>
  <select id="skills" name="skills[]" 
          multiple
          data-cf-select
          data-cf-tags="true"
          data-cf-placeholder="Select or type skills">
    <option value="html">HTML</option>
    <option value="css">CSS</option>
    <option value="js">JavaScript</option>
    <option value="php">PHP</option>
  </select>
</div>
```

## Checkbox and Radio Elements

### Basic Checkbox

```html
<div class="form-group">
  <div class="checkbox">
    <input type="checkbox" id="terms" name="terms" class="form-checkbox">
    <label for="terms">I agree to the terms and conditions</label>
  </div>
</div>
```

### Enhanced Checkbox

```html
<div class="form-group">
  <input type="checkbox" id="newsletter" name="newsletter" 
         data-cf-checkbox
         data-cf-custom="true"
         data-label="Subscribe to newsletter">
</div>
```

### Checkbox Group

```html
<div class="form-group">
  <fieldset>
    <legend class="form-label">Interests</legend>
    
    <div class="checkbox-group">
      <input type="checkbox" id="interest-tech" name="interests[]" 
             value="technology" data-cf-checkbox>
      <label for="interest-tech">Technology</label>
    </div>
    
    <div class="checkbox-group">
      <input type="checkbox" id="interest-sports" name="interests[]" 
             value="sports" data-cf-checkbox>
      <label for="interest-sports">Sports</label>
    </div>
    
    <div class="checkbox-group">
      <input type="checkbox" id="interest-music" name="interests[]" 
             value="music" data-cf-checkbox>
      <label for="interest-music">Music</label>
    </div>
  </fieldset>
</div>
```

### Radio Buttons

```html
<div class="form-group">
  <fieldset>
    <legend class="form-label">Gender</legend>
    
    <div class="radio-group">
      <input type="radio" id="gender-male" name="gender" 
             value="male" data-cf-radio>
      <label for="gender-male">Male</label>
    </div>
    
    <div class="radio-group">
      <input type="radio" id="gender-female" name="gender" 
             value="female" data-cf-radio>
      <label for="gender-female">Female</label>
    </div>
    
    <div class="radio-group">
      <input type="radio" id="gender-other" name="gender" 
             value="other" data-cf-radio>
      <label for="gender-other">Other</label>
    </div>
  </fieldset>
</div>
```

### Toggle Switch

```html
<div class="form-group">
  <div class="toggle-switch">
    <input type="checkbox" id="darkmode" class="toggle-input" data-cf-toggle>
    <label for="darkmode" class="toggle-label">
      <span class="toggle-text">Dark Mode</span>
    </label>
  </div>
</div>
```

## Textarea Elements

### Basic Textarea

```html
<div class="form-group">
  <label for="message" class="form-label">Message</label>
  <textarea id="message" name="message" class="form-textarea" rows="4"></textarea>
</div>
```

### Auto-resizing Textarea

```html
<div class="form-group">
  <label for="bio" class="form-label">Bio</label>
  <textarea id="bio" name="bio" 
            data-cf-textarea
            data-cf-auto-resize="true"
            rows="3"></textarea>
</div>
```

### Rich Text Editor

```html
<div class="form-group">
  <label for="content" class="form-label">Content</label>
  <textarea id="content" name="content" 
            data-cf-rich-editor
            data-cf-toolbar="basic"></textarea>
</div>
```

## File Inputs

### Basic File Input

```html
<div class="form-group">
  <label for="document" class="form-label">Document</label>
  <input type="file" id="document" name="document" class="form-file">
</div>
```

### Enhanced File Upload

```html
<div class="form-group">
  <label for="avatar" class="form-label">Profile Picture</label>
  <input type="file" id="avatar" name="avatar" 
         data-cf-upload
         data-cf-preview="true"
         data-cf-max-size="2"
         data-cf-allowed-types="image/jpeg,image/png">
</div>
```

### Multiple File Upload

```html
<div class="form-group">
  <label for="gallery" class="form-label">Photo Gallery</label>
  <input type="file" id="gallery" name="gallery[]" 
         multiple
         data-cf-upload
         data-cf-multiple="true"
         data-cf-preview="true"
         data-cf-drop-zone="true"
         data-cf-max-files="5">
  <div class="form-help">Upload up to 5 images (JPG, PNG). Max 2MB each.</div>
</div>
```

## Special Input Types

### Date Picker

```html
<div class="form-group">
  <label for="birthdate" class="form-label">Birth Date</label>
  <input type="text" id="birthdate" name="birthdate" 
         data-cf-datepicker
         data-cf-format="YYYY-MM-DD">
</div>
```

### Time Picker

```html
<div class="form-group">
  <label for="meeting-time" class="form-label">Meeting Time</label>
  <input type="text" id="meeting-time" name="meeting_time" 
         data-cf-timepicker
         data-cf-format="HH:mm">
</div>
```

### Color Picker

```html
<div class="form-group">
  <label for="theme-color" class="form-label">Theme Color</label>
  <input type="text" id="theme-color" name="theme_color" 
         data-cf-colorpicker
         data-cf-format="hex">
</div>
```

### Range Slider

```html
<div class="form-group">
  <label for="budget" class="form-label">Budget</label>
  <input type="range" id="budget" name="budget" 
         min="1000" max="10000" step="100" 
         data-cf-range
         data-cf-show-value="true">
</div>
```

## Input Groups

### Text with Button

```html
<div class="form-group">
  <label for="coupon" class="form-label">Coupon Code</label>
  <div class="input-group">
    <input type="text" id="coupon" name="coupon" class="form-input">
    <button type="button" class="btn btn-primary">Apply</button>
  </div>
</div>
```

### Prepended/Appended Text

```html
<div class="form-group">
  <label for="price" class="form-label">Price</label>
  <div class="input-group">
    <span class="input-group-text">$</span>
    <input type="number" id="price" name="price" class="form-input">
    <span class="input-group-text">.00</span>
  </div>
</div>
```

### Input with Icon

```html
<div class="form-group">
  <label for="search" class="form-label">Search</label>
  <div class="input-group">
    <span class="input-group-icon">
      <i class="icon-search"></i>
    </span>
    <input type="text" id="search" name="search" class="form-input">
  </div>
</div>
```

## Accessibility Features

The CarFuse form system includes several accessibility features:

### ARIA Attributes

```html
<div class="form-group">
  <label id="email-label" for="email" class="form-label">Email</label>
  <input type="email" id="email" name="email" 
         aria-labelledby="email-label"
         aria-describedby="email-help email-error"
         aria-required="true"
         class="form-input">
  <div id="email-help" class="form-help">Enter your personal email address</div>
  <div id="email-error" class="form-error" role="alert"></div>
</div>
```

### Error Announcements

When validation errors occur, they are announced to screen readers:

```html
<div class="form-group">
  <label for="password" class="form-label">Password</label>
  <input type="password" id="password" name="password" 
         class="form-input error"
         aria-invalid="true"
         aria-describedby="password-error">
  <div id="password-error" class="form-error" role="alert">
    Password must be at least 8 characters long
  </div>
</div>
```

### Keyboard Navigation

All enhanced form components support full keyboard navigation:

- `Tab`/`Shift+Tab`: Move between form controls
- `Space`: Toggle checkboxes and radio buttons
- `Enter`: Activate buttons
- `Arrow keys`: Navigate select options and datepickers
- `Escape`: Close dropdowns and pickers

## Related Documentation

- [Form System Overview](overview.md)
- [Form Validation](validation.md)
- [UI Components](../ui/overview.md)
- [Accessibility Guidelines](../../development/standards/accessibility.md)
