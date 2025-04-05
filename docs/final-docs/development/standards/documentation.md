# Documentation Standards

*Last updated: 2023-11-15*

This guide establishes the standards for all CarFuse documentation. Following these guidelines ensures consistency across our documentation system and makes information easier to find and understand.

## Table of Contents
- [Document Structure](#document-structure)
- [Formatting Guidelines](#formatting-guidelines)
- [Code Examples](#code-examples)
- [Cross-References](#cross-references)
- [Special Elements](#special-elements)
- [Images and Diagrams](#images-and-diagrams)
- [Writing Style](#writing-style)

## Document Structure

### Header Structure

Each document should include:

1. **Title (H1)** - Single main title using `# Title`
2. **Last Updated Date** - In italics below the title: *Last updated: YYYY-MM-DD*
3. **Brief Description** - 1-2 sentences explaining the document's purpose
4. **Table of Contents** - For documents longer than 3 sections
5. **Main Content** - Organized in logical sections

Example:

```markdown
# Component Title

*Last updated: 2023-11-15*

Brief description of the component and its purpose.

## Table of Contents
- [Section One](#section-one)
- [Section Two](#section-two)
```

### Section Headers

- **H1 (`#`)** - Document title only (one per document)
- **H2 (`##`)** - Main sections
- **H3 (`###`)** - Subsections
- **H4 (`####`)** - Minor subsections
- **H5 (`#####`)** - Rarely used, only for deep hierarchies

## Formatting Guidelines

### Text Formatting

- **Bold** (`**text**`) - Use for emphasis and UI elements
- *Italic* (`*text*`) - Use for:
  - Document dates
  - Introducing new terms
  - Light emphasis
- `Code` (`` `code` ``) - Use for:
  - Code snippets within text
  - File names
  - Function names
  - Variable names

### Lists

- Use ordered lists (1. 2. 3.) for sequential instructions
- Use unordered lists (- or *) for non-sequential items
- Maintain consistent indentation for nested lists (4 spaces)

### Tables

Use tables for structured data that needs comparison:

```markdown
| Header 1 | Header 2 | Header 3 |
|----------|----------|----------|
| Value 1  | Value 2  | Value 3  |
| Value 4  | Value 5  | Value 6  |
```

## Code Examples

### Code Blocks

Always specify the language for syntax highlighting:

