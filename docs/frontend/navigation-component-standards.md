# Navigation Component Standards

## Navigation Element Styling

### Top Navigation Bar
- **Height**: 56px standard, 48px compact
- **Background**: White or brand primary color
- **Content**: Logo, primary navigation items, utilities (search, notifications, profile)
- **Shadow**: Subtle shadow to elevate above content
- **Position**: Fixed or relative positioning

### Side Navigation
- **Width**: 240px expanded, 64px collapsed
- **Background**: Light neutral or brand color
- **Height**: Full height of viewport
- **Item Height**: 48px standard
- **Scrolling**: Auto overflow with fixed header if needed

### Tab Navigation
- **Height**: 48px standard
- **Tab Width**: Equal width or content-based
- **Indicator**: Bottom border or background highlight
- **Spacing**: 24px between tab centers or 0px for full-width
- **Alignment**: Left-aligned default, centered option

### Breadcrumb Navigation
- **Height**: 40px standard
- **Separator**: Icon (e.g., chevron) or text (e.g., slash)
- **Typography**: 14px normal
- **Current Item**: Bold or non-clickable styling
- **Truncation**: Ellipsis for long paths with tooltip

### Footer Navigation
- **Background**: Neutral background, slightly contrasting with body
- **Link Groups**: Organized by category
- **Typography**: 14px normal for links
- **Spacing**: 24px between groups, 16px between items

## Active/Inactive States

### Active State
- **Text**: Bold or semi-bold weight
- **Color**: Brand primary color
- **Indicator**: Highlight bar, background, or icon
- **Visual Weight**: Clearly distinguishable from inactive items

### Inactive State
- **Text**: Normal weight
- **Color**: Medium contrast neutral color
- **Hover**: Subtle background change, text color darkening

### Disabled State
- **Text**: Reduced opacity (50-60%)
- **Cursor**: Not-allowed
- **No hover effects**

### Current Section Indicator
- **Purpose**: Shows current location without requiring interaction
- **Visual**: Similar to active but distinct
- **Implementation**: Using aria-current="page" attribute

## Mobile Navigation Patterns

### Mobile Top Navigation
- **Height**: 56px
- **Content**: Logo, hamburger menu, key actions
- **Title**: Center-aligned page title
- **Back Button**: Left-aligned when in sub-page

### Mobile Menu (Expanded)
- **Position**: Full screen or partial slide-in
- **Animation**: Slide in from side
- **Close**: Clear X button or tap outside
- **Item Size**: Minimum 44px touch target height

### Bottom Navigation Bar
- **Purpose**: Primary actions on mobile
- **Item Count**: 3-5 items maximum
- **Content**: Icon + optional label
- **Height**: 56px standard

### Navigation Drawer
- **Purpose**: Full site navigation on mobile
- **Content**: User information, primary links, secondary links
- **Organization**: Section dividers, collapsible groups
- **Width**: 80-90% of screen width

## Dropdown & Submenu Styling

### Dropdown Menu
- **Background**: White or light neutral
- **Shadow**: Medium elevation shadow
- **Border**: Optional 1px border
- **Rounded Corners**: 4px border-radius
- **Width**: Minimum width matches trigger, may be wider

### Dropdown Items
- **Height**: 40px standard
- **Padding**: 12px 16px
- **States**: Hover, active, disabled
- **Indicator**: Checkbox or icon for selected items
- **Divider**: Subtle line between groups

### Submenu Indicators
- **Icon**: Chevron or arrow indicating children
- **Position**: Right-aligned in parent item
- **Animation**: Rotation for expanded state

### Mega Menu
- **Structure**: Multi-column layout
- **Width**: Full width or constrained width
- **Column Dividers**: Subtle vertical separators
- **Section Headers**: Bold or all-caps category titles
- **Padding**: 24px for container, standard padding for items

## Keyboard Navigation Support

### Focus Indicators
- **Style**: 2px outline in accessible color
- **Offset**: 2px from element edge
- **Visibility**: Only shown for keyboard focus (not mouse)

### Keyboard Shortcuts
- **Primary Navigation**: Tab key navigation
- **Arrow Keys**: Navigate within menus/tabs
- **Enter/Space**: Activate links/buttons
- **Escape**: Close dropdowns/submenus
- **Home/End**: First/last navigation item

### Focus Trapping
- **Modal Menus**: Focus trapped within when open
- **Tab Order**: Logical ordering matching visual layout

### Skip Navigation
- **Purpose**: Allow keyboard users to skip to main content
- **Implementation**: Hidden link revealed on focus
- **Position**: First focusable element on page

## Implementation Examples

### HTML Structure for Top Navigation
```html
<header class="nav-main">
  <div class="nav-container">
    <div class="nav-logo">
      <a href="/">
        <img src="/logo.svg" alt="Company Logo">
      </a>
    </div>
    
    <nav class="nav-items" aria-label="Main Navigation">
      <ul class="nav-list">
        <li class="nav-item">
          <a href="/dashboard" class="nav-link active" aria-current="page">Dashboard</a>
        </li>
        <li class="nav-item has-dropdown">
          <button class="nav-link" aria-expanded="false" aria-controls="products-menu">
            Products
            <span class="icon-chevron-down" aria-hidden="true"></span>
          </button>
          <ul class="dropdown-menu" id="products-menu">
            <li><a href="/products/new">New Products</a></li>
            <li><a href="/products/featured">Featured Products</a></li>
          </ul>
        </li>
        <!-- Other navigation items -->
      </ul>
    </nav>
    
    <div class="nav-actions">
      <button class="btn-icon" aria-label="Search">
        <span class="icon-search" aria-hidden="true"></span>
      </button>
      <button class="btn-icon" aria-label="Notifications">
        <span class="icon-bell" aria-hidden="true"></span>
      </button>
      <div class="profile-dropdown">
        <!-- Profile dropdown content -->
      </div>
    </div>
  </div>
</header>
```

### CSS Variables
```css
:root {
  --nav-height: 56px;
  --nav-bg-color: #FFFFFF;
  --nav-text-color: #2E3A59;
  --nav-active-color: #3366FF;
  --nav-hover-bg: #F7F9FC;
  --nav-item-spacing: 32px;
  --nav-font-size: 16px;
  
  --nav-mobile-height: 56px;
  --nav-drawer-width: 280px;
  --nav-bottom-height: 56px;
  
  --dropdown-shadow: 0 4px 16px rgba(0,0,0,0.1);
  --dropdown-border-color: #E4E9F2;
  --dropdown-bg: #FFFFFF;
}
```

## Mobile-Desktop Differences

### Responsive Transformations
| Navigation Type | Desktop | Mobile |
|-----------------|---------|--------|
| Primary Nav | Horizontal top bar | Hamburger menu |
| Secondary Nav | Sidebar or tabs | Bottom tabs or drawer |
| Utility Nav | Right-aligned in top bar | In dropdown menu |
| Breadcrumbs | Full path with separators | Back button or truncated |

### Touch Accommodations
- **Target Size**: Minimum 44×44px touch target
- **Spacing**: Increased spacing between touchable elements
- **Gestures**: Support for swipe navigation
- **Feedback**: Clear visual feedback for touch interactions

## Accessibility Considerations

- Use semantic HTML elements (`<nav>`, `<ul>`, `<li>`)
- Implement proper ARIA attributes (aria-expanded, aria-controls)
- Ensure keyboard navigability of all navigation items
- Provide skip links for keyboard users
- Test with screen readers for proper announcements
- Support reduced motion preferences
- Ensure proper focus management for dynamic menus
