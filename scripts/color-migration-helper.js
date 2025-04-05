/**
 * Color Migration Helper
 * 
 * This script helps find and replace legacy color references with semantic ones.
 * Run with: node color-migration-helper.js [directory]
 */

const fs = require('fs');
const path = require('path');

// Mapping of legacy color references to semantic ones
const colorMappings = {
  // Gray scale to surface
  'gray-50': 'surface-lightest',
  'gray-100': 'surface-lighter',
  'gray-200': 'surface-light',
  'gray-300': 'surface',
  'gray-400': 'surface-dark',
  'gray-500': 'surface-dark',
  'gray-600': 'surface-darker',
  'gray-700': 'surface-darker',
  'gray-800': 'surface-darkest',
  'gray-900': 'surface-black',
  
  // Background colors
  'bg-primary': 'surface-white',
  'bg-secondary': 'surface-lightest',
  'bg-tertiary': 'surface-lighter',
  
  // Text colors
  'text-gray-900': 'text-primary',
  'text-gray-800': 'text-primary',
  'text-gray-700': 'text-secondary',
  'text-gray-600': 'text-secondary',
  'text-gray-500': 'text-tertiary',
  'text-gray-400': 'text-disabled',
  
  // Border colors
  'border-gray-200': 'border-light',
  'border-gray-300': 'border',
  'border-gray-400': 'border-dark',
};

// File extensions to check
const extensions = ['.js', '.jsx', '.ts', '.tsx', '.vue', '.css', '.scss'];

// Function to recursively search directory
function searchDirectory(dir) {
  const files = fs.readdirSync(dir);
  
  files.forEach(file => {
    const filePath = path.join(dir, file);
    const stat = fs.statSync(filePath);
    
    if (stat.isDirectory() && !filePath.includes('node_modules')) {
      searchDirectory(filePath);
    } else if (extensions.includes(path.extname(filePath))) {
      checkFile(filePath);
    }
  });
}

// Function to check file for legacy color references
function checkFile(filePath) {
  const content = fs.readFileSync(filePath, 'utf8');
  let found = false;
  
  for (const [legacy, semantic] of Object.entries(colorMappings)) {
    const regex = new RegExp(`\\b${legacy}\\b`, 'g');
    if (regex.test(content)) {
      if (!found) {
        console.log(`\nFile: ${filePath}`);
        found = true;
      }
      console.log(`  - Replace "${legacy}" with "${semantic}"`);
    }
  }
}

// Main function
function main() {
  const directory = process.argv[2] || '.';
  console.log(`Scanning ${directory} for legacy color references...`);
  searchDirectory(directory);
  console.log('\nDone! Review the findings above and update your code accordingly.');
  console.log('Use semantic color variables to ensure consistency across themes.');
}

main();
