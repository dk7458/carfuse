# Shared Utilities (`shared/utils.js`)

## Overview
`utils.js` centralizes reusable logic to avoid duplication and improve maintainability. The following utilities are included:

### Fetch Utility
- **`fetchData(endpoint, config)`**
  - **Parameters**:
    - `endpoint`: API endpoint (string).
    - `config`: Configuration object for the fetch request (optional).
  - **Returns**: Parsed JSON response.
  - **Usage**:
    ```javascript
    import { fetchData } from './shared/utils.js';

    fetchData('/api/example', { method: 'POST', body: JSON.stringify({ key: 'value' }) })
        .then(response => console.log(response))
        .catch(error => console.error(error));
    ```

### Modal Handling Helpers
- **`showModal(modalId)`**: Open a modal by ID.
- **`hideModal(modalId)`**: Close a modal by ID.
- **`populateModal(modalId, data)`**: Populate a modal's content dynamically.

### Input Validation
- **`validateEmail(email)`**: Validates email format.
- **`validateNotEmpty(value)`**: Ensures the value is not empty.
- **`validateDateRange(startDate, endDate)`**: Ensures a valid date range.

### Date Formatting
- **`formatDate(date, format)`**: Formats a Date object into a string (default format: `YYYY-MM-DD`).
- **`getToday()`**: Returns today's date as a formatted string.

## Integration
To use utilities in any JavaScript file, import the required functions:
```javascript
import { fetchData, validateEmail } from './shared/utils.js';
```

## Frontend Architecture

### Directory Structure
The frontend codebase is organized into the following directories:
- `css`: Contains all the CSS stylesheets.
- `js`: Contains JavaScript files for various functionalities.
- `components`: Contains reusable UI components.
- `shared`: Contains shared utilities and helper functions.

### Purpose of Each Directory
- **`css`**: Centralizes all styling rules to ensure a consistent look and feel across the application.
- **`js`**: Houses JavaScript files that implement specific features or functionalities.
- **`components`**: Encapsulates reusable UI elements to promote modularity and reusability.
- **`shared`**: Contains utility functions and helpers that can be used across different parts of the application to avoid code duplication.

### Key Utilities in `utils.js`
- **Fetch Utility**: Simplifies API requests.
- **Modal Handling Helpers**: Manages modal dialogs.
- **Input Validation**: Validates user input.
- **Date Formatting**: Formats dates for display.

### Guidelines for Adding New Features or Components
1. **Create a new component**: Place reusable UI elements in the `components` directory.
2. **Add new styles**: Add CSS rules in the appropriate stylesheet within the `css` directory.
3. **Implement new functionality**: Add JavaScript files in the `js` directory.
4. **Use shared utilities**: Leverage functions from `shared/utils.js` to avoid duplicating code.

## Examples

### Using `fetchData` for API Requests
```javascript
import { fetchData } from './shared/utils.js';

fetchData('/api/example', { method: 'POST', body: JSON.stringify({ key: 'value' }) })
    .then(response => console.log(response))
    .catch(error => console.error(error));
```

### Validating Forms with Shared Helpers
```javascript
import { validateEmail, validateNotEmpty } from './shared/utils.js';

const email = 'example@example.com';
const isValidEmail = validateEmail(email);
const isNotEmpty = validateNotEmpty(email);

console.log(`Email valid: ${isValidEmail}, Not empty: ${isNotEmpty}`);
```

### Formatting Dates Using Utilities
```javascript
import { formatDate, getToday } from './shared/utils.js';

const today = getToday();
const formattedDate = formatDate(new Date(), 'MM/DD/YYYY');

console.log(`Today: ${today}, Formatted Date: ${formattedDate}`);
```

## Changelog

### Recent Updates
- **Refactoring of JavaScript Files**: Improved structure and readability of `dynamic_pricing.js`, `log_manager.js`, and other scripts.
- **Consolidation of CSS Styles**: Unified stylesheets to ensure consistency across the application.
- **Introduction of Reusable Utilities**: Added `utils.js` to centralize common functions and reduce code duplication.
