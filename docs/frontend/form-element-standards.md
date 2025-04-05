# Form Element Standards

## Input Field Styling

### Text Inputs

#### Default Text Input
- **Height**: 40px (md), 32px (sm), 48px (lg)
- **Border**: 1px solid border color
- **Border Radius**: 4px
- **Padding**: 12px 16px (md)
- **Typography**: 16px, normal weight
- **Background**: White or very light gray

#### Specialized Text Inputs
- **Textarea**: Min-height 80px, resizable
- **Search**: Left-aligned search icon, optional clear button (×)
- **Password**: Optional toggle for password visibility
- **Number**: Optional increment/decrement buttons

### Input Variants

#### Filled Input
- **Visual Style**: Light fill background with minimal border
- **Usage**: Modern interfaces, material design influence

#### Outlined Input
- **Visual Style**: White background with visible border
- **Usage**: Traditional forms, high contrast needs

#### Underlined Input
- **Visual Style**: No border except bottom border
- **Usage**: Minimalist interfaces, reduced visual noise

### Non-Text Inputs

#### Select/Dropdown
- Follow text input styling with added dropdown indicator
- Support for option groups
- Custom styling for dropdown menu

#### Checkbox
- **Size**: 18px × 18px (default)
- **Border Radius**: 3px
- **States**: Unchecked, checked, indeterminate
- Custom styling for checked state

#### Radio Button
- **Size**: 18px × 18px (default)
- **Border Radius**: Full circle (50%)
- Inner circle for selected state

#### Toggle/Switch
- **Size**: 32px × 18px (default)
- **Visual**: Sliding circle within track
- Clear on/off states with optional labels

## Label Positioning & Styling

### Label Positions
1. **Top Position (Default)**
   - Label above input
   - 8px margin between label and input

2. **Left Position**
   - Label to left of input
   - Right-aligned for visual connection
   - 16px margin between label and input

3. **Floating Label**
   - Label starts inside input
   - Animates to top on focus or when filled
   - Reduces vertical space requirements

4. **Inside Label (Placeholder as Label)**
   - Only for non-critical forms
   - Not recommended as primary pattern due to accessibility concerns

### Label Styling
- **Typography**: 14px, medium weight (500)
- **Color**: Slightly darker than body text
- **Optional Indicator**: Subtle "Optional" text or "(optional)"
- **Required Indicator**: Asterisk (*) with appropriate aria-required attribute

## Validation State Visuals

### Error State
- **Border Color**: Red (#D42F2F)
- **Icon**: Error icon before message
- **Message Color**: Red (#D42F2F)
- **Background**: Optional light red background for severe errors

### Success State
- **Border Color**: Green (#2E7D32)
- **Icon**: Checkmark icon before message
- **Message Color**: Green (#2E7D32)
- **Application**: Only show when confirmation is valuable

### Warning State
- **Border Color**: Amber (#FFA000)
- **Icon**: Warning icon before message
- **Message Color**: Dark amber (#F57C00)
- **Usage**: For cautionary guidance, not blocking errors

## Helper Text & Error Messages

### Helper Text
- **Positioning**: Below input
- **Typography**: 12px, normal weight
- **Color**: Lighter than body text
- **Spacing**: 4px below input field

### Error Messages
- **Positioning**: Replace helper text when error occurs
- **Typography**: 12px, normal weight
- **Animation**: Subtle fade in or height transition
- **Icon**: Optional error icon before text

### Character Counter
- **Positioning**: Below input, right aligned
- **Format**: "12/50" or "12 characters remaining"
- **State Change**: Color change when approaching/exceeding limit

## Accessibility Considerations

### Focus Indicators
- **Visible Focus**: 2px outline in accessible color
- **Focus-within**: Applied to container for composite inputs
- **Keyboard Navigation**: Logical tab order

### Field Grouping
- **Fieldsets**: Group related inputs
- **Legends**: Describe the field group
- **ARIA Roles**: Appropriate roles for custom inputs

### Assistive Technology
- **Labels**: Always use `<label>` with `for` attribute
- **ARIA**: Use aria-describedby for error/helper text
- **Live Regions**: For dynamic error messages

## Form Layout Patterns

### Single Column Layout
- **Usage**: Simple forms, mobile optimization
- **Label Position**: Top
- **Advantages**: Easy scannable, clear progression

### Two Column Layout
- **Usage**: Dense forms, related field pairs
- **Considerations**: Ensure logical grouping
- **Responsive**: Collapse to single column on mobile

### Input Sizing
- **Width**: Match expected content length
- **Consistency**: Group similar fields with same width
- **Full Width**: Use on mobile by default
