#!/usr/bin/env node

/**
 * CarFuse HTMX Build Script
 * 
 * This script concatenates HTMX core and extensions, and creates:
 * - Development version with source maps (htmx.js)
 * - Minified production version (htmx.min.js)
 */

const fs = require('fs');
const path = require('path');
const { minify } = require('terser');
const concat = require('concat');

// Configuration
const config = {
    // Base directories
    baseDir: path.resolve(__dirname, '..'),
    sourceDir: path.resolve(__dirname, '../public/js/htmx'),
    vendorDir: path.resolve(__dirname, '../vendor/htmx'),
    outputDir: path.resolve(__dirname, '../public/js'),
    
    // Input files in order of inclusion
    files: [
        // Vendor HTMX library
        '../vendor/htmx/htmx.js',
        
        // Core and extensions
        'core.js',
        'extensions/auth.js',
        'extensions/swap.js',
        'extensions/indicators.js',
        
        // Feature modules
        'modules/booking.js',
        'modules/payment.js',
        'modules/user.js'
    ],
    
    // Output files
    outputDev: 'htmx.js',
    outputProd: 'htmx.min.js'
};

// Ensure output directory exists
if (!fs.existsSync(config.outputDir)) {
    fs.mkdirSync(config.outputDir, { recursive: true });
}

// Function to resolve file paths
function resolveFilePaths(files) {
    return files.map(file => {
        if (file.startsWith('../vendor')) {
            return path.resolve(config.baseDir, file);
        } else {
            return path.resolve(config.sourceDir, file);
        }
    });
}

// Main build function
async function build() {
    try {
        console.log('Building CarFuse HTMX files...');
        
        // Resolve full paths to input files
        const inputFiles = resolveFilePaths(config.files);
        
        // Check if all files exist
        inputFiles.forEach(file => {
            if (!fs.existsSync(file)) {
                throw new Error(`File not found: ${file}`);
            }
        });
        
        // Development build (concatenation with source maps)
        console.log('Creating development build...');
        
        // Read all file contents
        const fileContents = inputFiles.map(file => fs.readFileSync(file, 'utf8'));
        
        // Add version stamp and file header
        const version = new Date().toISOString();
        
        const header = `/**
 * CarFuse HTMX Bundle - Development Version
 * Generated: ${new Date().toLocaleString()}
 * 
 * This file combines htmx.js with CarFuse custom extensions and modules
 */\n\n`;
        
        // Create the development bundle with source maps
        console.log('Concatenating files...');
        await concat(inputFiles, path.join(config.outputDir, config.outputDev));
        
        // Read the concatenated file and add the header
        const devBundle = fs.readFileSync(path.join(config.outputDir, config.outputDev), 'utf8');
        fs.writeFileSync(path.join(config.outputDir, config.outputDev), header + devBundle);
        
        // Production build (minification)
        console.log('Creating production build...');
        
        const prodHeader = `/**
 * CarFuse HTMX Bundle - Production Version
 * Generated: ${new Date().toLocaleString()}
 * Minified and optimized for production use
 */\n`;
        
        // Minify the code
        const minified = await minify(devBundle, {
            compress: {
                drop_console: true,
                dead_code: true,
            },
            format: {
                comments: false,
                preamble: prodHeader
            },
            sourceMap: {
                filename: config.outputProd,
                url: `${config.outputProd}.map`
            }
        });
        
        // Write the minified file
        fs.writeFileSync(path.join(config.outputDir, config.outputProd), minified.code);
        
        // Write the source map for the minified file
        fs.writeFileSync(path.join(config.outputDir, `${config.outputProd}.map`), minified.map);
        
        console.log('Build completed successfully!');
    } catch (error) {
        console.error('Build failed:', error);
        process.exit(1);
    }
}

// Execute the build
build();
