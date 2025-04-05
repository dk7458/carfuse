#!/bin/bash

# CarFuse asset build script
echo "Building CarFuse assets..."

# Ensure we're in the project root
cd "$(dirname "$0")"

# Install dependencies if node_modules doesn't exist
if [ ! -d "node_modules" ]; then
    echo "Installing dependencies..."
    npm install
fi

# Build HTMX bundle
echo "Building HTMX bundle..."
npm run build:htmx

# Build completed
echo "Build completed successfully!"
