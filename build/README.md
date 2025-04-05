# CarFuse Build Process

This directory contains build scripts for CarFuse assets.

## HTMX Build Process

The HTMX build process concatenates the core HTMX library with custom extensions and modules, and produces:

1. A development version with source maps (`htmx.js`)
2. A minified production version (`htmx.min.js`)

### Files included in the build

- Vendor HTMX library
- CarFuse HTMX core
- Extensions:
  - Auth extension (for authentication handling)
  - Swap extension (for animation transitions)
  - Indicators extension (for loading indicators)
- Feature modules:
  - Booking module
  - Payment module
  - User module

### Build Instructions

To build HTMX assets:

```bash
# From project root
npm run build:htmx

# Or use the convenience script
./build-assets.sh
```

### Configuration

The build configuration is defined in `build/build-htmx.js`. You can modify this file to:

- Change input files
- Change output filenames
- Adjust minification settings
- Change source map configuration

### Production vs Development

The base.php template automatically selects the appropriate version based on the `DEBUG_MODE` constant:
- In development mode, it loads `htmx.js` with source maps
- In production mode, it loads the minified `htmx.min.js`
