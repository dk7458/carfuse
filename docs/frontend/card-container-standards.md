# Card & Container Standards

## Card Variants

### Basic Card
- **Purpose**: Simple content containers
- **Visual Style**: Light background, subtle border or shadow
- **Structure**: Flexible content, minimal styling
- **Usage**: General content grouping

### Interactive Card
- **Purpose**: Clickable/selectable content blocks
- **Visual Style**: Hover and active states, cursor pointer
- **Structure**: Often includes action indicators (chevron, etc.)
- **Usage**: Navigation, selection interfaces

### Informational Card
- **Purpose**: Display structured information
- **Visual Style**: Clear hierarchy, may include accent colors
- **Structure**: Header, body, footer sections
- **Usage**: Dashboards, data display

### Media Card
- **Purpose**: Display content with visual elements
- **Visual Style**: Prominent media area with supporting text
- **Structure**: Media container with text overlay or adjacent text
- **Usage**: Article previews, gallery items

## Card Structure & Spacing

### Internal Spacing
- **Padding**: 16px default (sm: 12px, lg: 24px)
- **Content Margins**: 12px between content blocks
- **Header Spacing**: 12px below header
- **Footer Spacing**: 12px above footer

### Content Alignment
- **Text**: Left-aligned by default
- **Actions**: Right-aligned or full-width
- **Media**: Centered or cover positioning
- **Icons**: Vertically centered with accompanying text

### Header/Body/Footer Patterns

#### Card Header
- **Typography**: 18px semi-bold (header text)
- **Optional Elements**: Avatar, metadata, actions
- **Border**: Optional 1px bottom border
- **Padding**: 16px (consistent with card padding)

#### Card Body
- **Typography**: 14px normal (body text)
- **Content Types**: Text, lists, form elements, data visualizations
- **Scrolling**: Optional scrolling for constrained height cards

#### Card Footer
- **Typography**: 14px normal or 12px for secondary information
- **Common Elements**: Actions, timestamps, metadata
- **Border**: Optional 1px top border
- **Background**: Optional subtle background differentiation

## Interactive States

### Hover State
- **Shadow**: Slightly elevated shadow (e.g., from 2dp to 4dp)
- **Border**: Optional highlight border color
- **Background**: Optional subtle background color change
- **Transition**: Smooth 150ms transition

### Active/Selected State
- **Border**: Accent color border (2px)
- **Background**: Light accent background color
- **Indicator**: Optional selection indicator icon
- **Clear Target**: Entire card clickable with appropriate cursor

### Disabled State
- **Opacity**: 60%
- **Cursor**: default or not-allowed
- **No hover effects**

## Card Nesting & Relationships

### Nesting Capabilities
- Maximum recommended nesting: 2 levels
- Inner cards should have:
  - Reduced shadow elevation
  - Differentiated background
  - Smaller or no border-radius

### Card Groups
- **Spacing**: 16px between cards in a group
- **Alignment**: Equal height within row
- **Consistency**: Same style for cards in group

### Card Collections
- **Grid Layout**: 16px-24px gap in grid layout
- **Masonry**: Variable height with equal width
- **Carousel**: Equal card dimensions, visible next/prev indicators

## Responsive Behavior

### Mobile Adaptations
- **Width**: Full-width or minimal margins
- **Padding**: Reduce to 12px
- **Content**: Simplify or stack horizontal elements
- **Height**: Consider fixed heights carefully

### Tablet/Desktop Adaptations
- **Width**: Responsive grid placement
- **Height**: Equal height within row when feasible
- **Layout**: Grid or masonry layouts common

## Implementation Examples

### HTML Structure Example
```html
<div class="card card-interactive">
  <div class="card-header">
    <h3 class="card-title">Card Title</h3>
    <div class="card-actions">
      <button class="btn-icon">⋮</button>
    </div>
  </div>
  <div class="card-body">
    <p>Card content goes here.</p>
  </div>
  <div class="card-footer">
    <span class="card-metadata">Last updated: 2 days ago</span>
    <button class="btn btn-text">Action</button>
  </div>
</div>
```

### CSS Variables
```css
:root {
  --card-bg: #FFFFFF;
  --card-border-radius: 8px;
  --card-shadow: 0 2px 4px rgba(0,0,0,0.1);
  --card-hover-shadow: 0 4px 8px rgba(0,0,0,0.15);
  --card-border-color: #E0E0E0;
  --card-selected-border: #3366FF;
  --card-padding: 16px;
  --card-header-border: #F0F0F0;
  --card-footer-bg: #F9F9F9;
}
```

## Accessibility Considerations

- Ensure sufficient color contrast (4.5:1 minimum)
- For interactive cards, use appropriate semantic elements (button, a)
- Include focus states for keyboard navigation
- Consider ARIA roles for custom card interactions
- Ensure actionable elements are keyboard accessible
