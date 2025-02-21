#!/bin/bash

# Directory to search (change this to your target directory)
SEARCH_DIR="/home/dorian/carfuse/App/Services"

# Output file
OUTPUT_FILE="svc_construct.txt"

# Clear the output file if it exists
> "$OUTPUT_FILE"

# Find all PHP files in the directory and extract the __construct snippet
find "$SEARCH_DIR" -type f -name "*.php" | while read -r file; do
    # Extract the __construct snippet using grep
    snippet=$(grep -A 3 '__construct' "$file" | grep -v '^--$')

    # If the snippet is found, write the filename and snippet to the output file
    if [ -n "$snippet" ]; then
        echo "File: $file" >> "$OUTPUT_FILE"
        echo "$snippet" >> "$OUTPUT_FILE"
        echo "" >> "$OUTPUT_FILE"  # Add a blank line for readability
    fi
done

echo "Extraction complete. Results saved to $OUTPUT_FILE."
