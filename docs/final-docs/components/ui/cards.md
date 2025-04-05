# Card Components

*Last updated: 2023-11-15*

This document provides detailed information on using and customizing card components in the CarFuse UI system.

## Table of Contents
- [Card Variants](#card-variants)
- [Card Structure](#card-structure)
- [Interactive States](#interactive-states)
- [Card Content Patterns](#card-content-patterns)
- [Responsive Behavior](#responsive-behavior)
- [Card Collections](#card-collections)
- [Accessibility](#accessibility)
- [Related Documentation](#related-documentation)

## Card Variants

CarFuse provides several card variants for different purposes:

### Basic Card
- **Purpose**: Simple content containers
- **Visual Style**: Light background, subtle border or shadow
- **Structure**: Flexible content, minimal styling
- **Usage**: General content grouping

```html
<div class="card">
  <div class="card-body">
    <h3 class="text-lg font-medium mb-2">Card Title</h3>
    <p class="text-gray-600">This is a basic card with only body content.</p>
  </div>
</div>
```

### Interactive Card
- **Purpose**: Clickable/selectable content blocks
- **Visual Style**: Hover and active states, cursor pointer
- **Structure**: Often includes action indicators (chevron, etc.)
- **Usage**: Navigation, selection interfaces

```html
<div class="card card-interactive" onclick="handleCardClick()">
  <div class="card-body">
    <h3 class="card-title">Interactive Card</h3>
    <p>Click this card to perform an action</p>
    <div class="card-action-indicator">
      <i class="icon-chevron-right"></i>
    </div>
  </div>
</div>
```

### Informational Card
- **Purpose**: Display structured information
- **Visual Style**: Clear hierarchy, may include accent colors
- **Structure**: Header, body, footer sections
- **Usage**: Dashboards, data display

```html
<div class="card card-info">
  <div class="card-header">
    <h3 class="card-title">Monthly Statistics</h3>
    <div class="card-tools">
      <button class="btn-icon"><i class="icon-refresh"></i></button>
    </div>
  </div>
  <div class="card-body">
    <div class="card-data-point">
      <span class="data-label">Total Users</span>
      <span class="data-value">1,254</span>
      <span class="data-change increase">+12%</span>
    </div>
    <!-- More data points -->
  </div>
  <div class="card-footer">
    <a href="/details">View Details</a>
    <span class="card-timestamp">Updated 2 hours ago</span>
  </div>
</div>
```

### Media Card
- **Purpose**: Display content with visual elements
- **Visual Style**: Prominent media area with supporting text
- **Structure**: Media container with text overlay or adjacent text
- **Usage**: Article previews, gallery items

```html
<div class="card card-media">
  <div class="card-media-top">
    <img src="/images/sample.jpg" alt="Card image">
  </div>
  <div class="card-body">
    <h3 class="card-title">Media Title</h3>
    <p>This card has an image at the top.</p>
  </div>
</div>
```

## Card Structure

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

```html
<div class="card-header">
  <h3 class="card-title">Card Title</h3>
  <div class="card-subtitle">Supporting information</div>
  <div class="card-actions">
    <button class="btn-icon">⋮</button>
  </div>
</div>
```

#### Card Body
- **Typography**: 14px normal (body text)
- **Content Types**: Text, lists, form elements, data visualizations
- **Scrolling**: Optional scrolling for constrained height cards

```html
<div class="card-body">
  <!-- Basic content -->
  <p>Regular card content.</p>
  
  <!-- Rich content -->
  <div class="card-section">
    <h4 class="card-section-title">Section Title</h4>
    <p>Section content.</p>
  </div>
  
  <!-- Scrollable content -->
  <div class="card-scrollable">
    <!-- Long content that will scroll -->
  </div>
</div>
```

#### Card Footer
- **Typography**: 14px normal or 12px for secondary information
- **Common Elements**: Actions, timestamps, metadata
- **Border**: Optional 1px top border
- **Background**: Optional subtle background differentiation

```html
<div class="card-footer">
  <span class="card-metadata">Last updated: 2 days ago</span>
  <div class="card-actions">
    <button class="btn btn-text">Action</button>
  </div>
</div>
```

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

```html
<div class="card card-selectable" data-selected="true">
  <!-- Card with selected state active -->
  <div class="card-selection-indicator">
    <i class="icon-check"></i>
  </div>
  <div class="card-body">
    <h3 class="card-title">Selected Card</h3>
    <p>This card is in the selected state.</p>
  </div>
</div>
```

### Disabled State
- **Opacity**: 60%
- **Cursor**: default or not-allowed
- **No hover effects**

```html
<div class="card disabled">
  <div class="card-body">
    <h3 class="card-title">Disabled Card</h3>
    <p>This card is currently unavailable.</p>
  </div>
</div>
```

## Card Content Patterns

### List Card

For displaying collections of similar items:

```html
<div class="card card-list">
  <div class="card-header">
    <h3 class="card-title">Recent Activities</h3>
  </div>
  <ul class="card-list-items">
    <li class="card-list-item">
      <div class="item-icon"><i class="icon-document"></i></div>
      <div class="item-content">
        <div class="item-title">Document Updated</div>
        <div class="item-subtitle">project-plan.docx</div>
      </div>
      <div class="item-meta">12 min ago</div>
    </li>
    <!-- More list items -->
  </ul>
</div>
```

### Stat Card

For displaying key metrics:

```html
<div class="card card-stat">
  <div class="card-body">
    <div class="stat-icon"><i class="icon-users"></i></div>
    <div class="stat-content">
      <div class="stat-value">1,254</div>
      <div class="stat-label">Total Users</div>
    </div>
    <div class="stat-change increase">+12%</div>
  </div>
</div>
```

### Form Card

For containing form elements:

```html
<div class="card card-form">
  <div class="card-header">
    <h3 class="card-title">Contact Information</h3>
  </div>
  <div class="card-body">
    <form>
      <div class="form-group">
        <label for="name">Name</label>
        <input type="text" id="name" class="form-input">
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" class="form-input">
      </div>
    </form>
  </div>
  <div class="card-footer">
    <button type="submit" class="btn btn-primary">Save</button>
    <button type="button" class="btn btn-text">Cancel</button>
  </div>
</div>
```

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

```html
<!-- Responsive card grid -->
<div class="card-grid">
  <div class="card"><!-- Card 1 --></div>
  <div class="card"><!-- Card 2 --></div>
  <div class="card"><!-- Card 3 --></div>
</div>

<style>
  .card-grid {
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  }
  
  @media (max-width: 640px) {
    .card-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
```

## Card Collections

### Card Groups
- **Spacing**: 16px between cards in a group
- **Alignment**: Equal height within row
- **Consistency**: Same style for cards in group

```html
<div class="card-group">
  <div class="card"><!-- Card 1 --></div>
  <div class="card"><!-- Card 2 --></div>
  <div class="card"><!-- Card 3 --></div>
</div>
```

### Card Carousel

For horizontally scrolling card collections:

```html
<div class="card-carousel" data-component="carousel">
  <div class="carousel-container">
    <div class="carousel-slide">
      <div class="card"><!-- Card 1 --></div>
    </div>
    <div class="carousel-slide">
      <div class="card"><!-- Card 2 --></div>
    </div>
    <!-- More slides -->
  </div>
  <button class="carousel-prev"><i class="icon-chevron-left"></i></button>
  <button class="carousel-next"><i class="icon-chevron-right"></i></button>
</div>
```

### Masonry Layout

For variable height cards:

```html
<div class="card-masonry" data-component="masonry">
  <div class="masonry-item">
    <div class="card"><!-- Card with variable height --></div>
  </div>
  <!-- More masonry items -->
</div>
```

## Accessibility

Cards should follow these accessibility practices:

- Use appropriate heading levels for card titles
- Ensure interactive cards are keyboard accessible
- Include proper ARIA roles for non-standard UI patterns
- Maintain sufficient color contrast for all elements
- Ensure touch targets are at least 44px × 44px

### Interactive Card Example with Accessibility

```html
<div class="card card-interactive"
     tabindex="0"
     role="button"
     aria-pressed="false"
     onclick="selectCard(this)"
     onkeydown="handleKeydown(event, this)">
  <div class="card-body">
    <h3 id="card-title-1" class="card-title">Accessible Card</h3>
    <p>This card can be selected using keyboard or mouse.</p>
  </div>
</div>

<script>
  function selectCard(card) {
    const isSelected = card.getAttribute('aria-pressed') === 'true';
    card.setAttribute('aria-pressed', !isSelected);
    card.classList.toggle('selected');
  }
  
  function handleKeydown(event, card) {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      selectCard(card);
    }
  }
</script>
```

## Related Documentation

- [UI Component Overview](overview.md)
- [Button Components](buttons.md)
- [Form Components](../forms/overview.md)
- [Dashboard Elements](dashboard.md)
- [Color System](../theme/colors.md)
