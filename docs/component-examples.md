# Component Usage Examples

## Button Component

```jsx
import { Button } from '../components/Button';

// Primary button (accessible)
<Button variant="primary" onClick={handleClick}>
  Save
</Button>

// With icon and accessibility label
<Button 
  variant="icon" 
  ariaLabel="Delete item"
  onClick={handleDelete}
>
  <TrashIcon className="w-5 h-5" />
</Button>

// Disabled state
<Button variant="primary" disabled>
  Processing...
</Button>
```

## Form Components

```jsx
import { Input, Select, Checkbox } from '../components/Form';

// Text input with label and error
<Input
  id="email"
  label="Email Address"
  type="email"
  value={email}
  onChange={handleEmailChange}
  error={emailError}
  required
/>

// Select with accessibility enhancements
<Select
  id="country"
  label="Country"
  value={country}
  onChange={handleCountryChange}
  options={countryOptions}
  aria-required="true"
/>

// Accessible checkbox
<Checkbox
  id="terms"
  label="I accept the terms and conditions"
  checked={acceptTerms}
  onChange={handleTermsChange}
  required
/>
```

## Theme Integration

```jsx
import { useTheme } from '../components/ThemeProvider';

function MyComponent() {
  const { theme, setTheme } = useTheme();
  
  return (
    <div>
      <button 
        onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}
        aria-pressed={theme === 'dark'}
        className="focus:outline-none focus:ring-2"
      >
        {theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode'}
      </button>
    </div>
  );
}
```

## Working with Backend Content

```jsx
import { DynamicContent } from '../components/DynamicContent';

// Backend-generated content with frontend styling
<DynamicContent
  backendClasses={data.classes}
  frontendClasses="p-4 rounded-lg shadow-sm"
  htmlContent={data.content}
/>

// With children instead of HTML string
<DynamicContent
  backendClasses={data.classes}
  frontendClasses="flex items-center"
>
  <Icon name={data.icon} />
  <span>{data.label}</span>
</DynamicContent>
```
