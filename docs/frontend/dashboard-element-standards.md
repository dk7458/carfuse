# Dashboard Element Standards

## Data Table Styling

### Table Structure
- **Header Row**: Sticky header option for long tables
- **Column Widths**: Mix of fixed and flexible widths
- **Row Height**: 48px standard, 40px compact, 56px relaxed
- **Border Style**: Horizontal borders only (default), full grid optional
- **Zebra Striping**: Optional alternate row styling

### Table Cell Styling
- **Padding**: 12px 16px (default)
- **Text Alignment**: Left for text, right for numbers
- **Typography**: 14px normal for content, 14px medium for headers
- **Truncation**: Ellipsis for overflowing content with tooltip on hover

### Table Interaction
- **Row Hover**: Subtle background change
- **Row Selection**: Checkbox in first column, selected row styling
- **Sorting**: Clear indicators for sortable columns and sort direction
- **Actions**: Inline actions with icon buttons or dropdown menu

### Responsive Behavior
- **Horizontal Scrolling**: For tables wider than viewport
- **Column Priority**: Hide lower priority columns progressively
- **Card View**: Transform to cards on mobile with data pairs
- **Expandable Rows**: Show details on expand for mobile view

## Chart & Graph Containers

### Container Styling
- **Dimensions**: 1:1, 4:3, or 16:9 aspect ratios
- **Padding**: 16px around chart area
- **Background**: White or very subtle fill
- **Border**: Optional 1px border or subtle shadow

### Chart Elements
- **Titles**: Aligned with card header patterns
- **Legends**: Consistent symbol sizes and text styles
- **Axes**: Clear labels with sufficient spacing
- **Data Points**: Consistent styling across chart types

### Chart Types Standards
- **Line Charts**: 2px line width, dots at data points
- **Bar/Column Charts**: 60% bar width (of available space)
- **Pie/Donut Charts**: Max 7 segments, 50% donut hole
- **Area Charts**: Subtle gradient fill, 20-30% opacity

### Interaction States
- **Hover**: Data point highlight with tooltip
- **Selection**: Persist highlight state
- **Loading**: Skeleton screen animation
- **Empty State**: Clear no-data message

## Statistics Card Layouts

### Metric Card
- **Structure**: Large number, supporting label, optional trend
- **Typography**: 28px bold for metrics, 14px normal for labels
- **Icon**: Optional supporting icon
- **Trend Indicator**: Direction arrow + percentage

### KPI Card
- **Structure**: Metric, comparison value, chart/sparkline
- **Comparison**: vs. previous period, target, or benchmark
- **Color Coding**: Green positive, red negative (configurable)
- **Time Period Selector**: Optional dropdown

### Multi-Metric Card
- **Structure**: 2-4 related metrics
- **Layout**: Grid or row layout
- **Hierarchy**: Primary metric emphasized
- **Consistency**: Same decimal precision, units

### Dimensions
- **Height**: 100px minimum
- **Width**: 240px minimum
- **Spacing**: Equal padding around metrics (16px)
- **Alignment**: Numbers aligned (right or decimal)

## Grid & Flex Layouts

### Grid System
- **Columns**: 12-column system
- **Gutters**: 24px default (16px compact, 32px relaxed)
- **Breakpoints**:
  - XS: < 576px (1-2 cards per row)
  - SM: 576-767px (2-3 cards per row)
  - MD: 768-991px (3-4 cards per row)
  - LG: 992-1199px (4-6 cards per row)
  - XL: ≥ 1200px (6+ cards per row)

### Dashboard Grid Patterns
- **Full-Width Header**: 12 columns
- **Main Metrics Row**: 3-4 cards equally sized
- **Content Zones**: 2/3 + 1/3 split common
- **Detail Areas**: Full width or 2-column layout

### Flex Layout Patterns
- **Card Groups**: Row with wrap, equal height
- **Filter Bars**: Row without wrap, overflow handling
- **Detail Panels**: Column layout with auto height

### Responsive Behaviors
- **Reordering**: Priority content first on small screens
- **Stacking**: Convert side-by-side to vertical layout
- **Collapsing**: Optional collapse/expand sections

## Dashboard Spacing Standards

### Element Spacing
- **Card Gap**: 24px between cards
- **Section Gap**: 32px between dashboard sections
- **Internal Spacing**: 16px within cards
- **Control Groups**: 8px between related controls

### Section Organization
- **Visual Hierarchy**: Clear headings for sections
- **Grouping**: Related content visually grouped
- **White Space**: Deliberate use of white space for separation
- **Alignment**: Strong alignment across elements

### Dashboard Page Structure
- **Header Area**: Title, period selectors, global filters
- **Overview Section**: Key metrics and KPIs
- **Detail Sections**: Tables, detailed charts
- **Action Area**: Common tasks, quick links

### Scrolling Behavior
- **Page Scrolling**: Vertical scroll for full dashboard
- **Container Scrolling**: Individual scrollable regions
- **Sticky Elements**: Keep important headers/filters visible
- **Scroll Affordance**: Clear indicators for scrollable content

## Implementation Examples

### Grid Layout Example
```html
<div class="dashboard-container">
  <div class="dashboard-header">
    <h1>Sales Dashboard</h1>
    <div class="dashboard-controls"><!-- Filters --></div>
  </div>
  
  <div class="metrics-row">
    <div class="metric-card"><!-- Revenue --></div>
    <div class="metric-card"><!-- Orders --></div>
    <div class="metric-card"><!-- Customers --></div>
    <div class="metric-card"><!-- Conversion --></div>
  </div>
  
  <div class="dashboard-main">
    <div class="content-primary">
      <div class="chart-card large"><!-- Main chart --></div>
      <div class="data-table-card"><!-- Data table --></div>
    </div>
    <div class="content-sidebar">
      <div class="chart-card small"><!-- Side chart 1 --></div>
      <div class="chart-card small"><!-- Side chart 2 --></div>
    </div>
  </div>
</div>
```

### CSS Variables
```css
:root {
  --dashboard-grid-columns: 12;
  --dashboard-gutter: 24px;
  --dashboard-section-spacing: 32px;
  --dashboard-card-spacing: 24px;
  
  --metric-text-large: 28px;
  --metric-text-medium: 24px;
  --metric-label: 14px;
  
  --chart-primary-color: #3366FF;
  --chart-secondary-colors: #FF6384, #36A2EB, #FFCE56, #4BC0C0;
  --chart-grid-color: #E0E0E0;
  --chart-axis-color: #9E9E9E;
}
```

## Accessibility Considerations

- **Color Meaning**: Never rely solely on color for conveying information
- **Chart Alternatives**: Provide data table alternatives for screen readers
- **Focus Management**: Ensure proper keyboard navigation
- **Text Contrast**: Maintain 4.5:1 contrast ratio for all text
- **Responsive Design**: Ensure usability at 200% zoom
- **ARIA Roles**: Use appropriate landmarks for dashboard sections
- **Screen Reader Support**: Add descriptive alt text for charts
