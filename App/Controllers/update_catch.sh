#!/bin/bash

# Define the ExceptionHandler include statement
EXCEPTION_HANDLER_INCLUDE="require_once '/../Helpers/ExceptionHandler.php';"

# Define the output file for constructor exports
CONSTRUCTS_EXPORT="constructs_export.txt"

# Clear the export file before starting
> "$CONSTRUCTS_EXPORT"

# Find all PHP files and process them
find . -type f -name "*.php" | while read -r file; do
    echo "Processing: $file"

    # Check if the file contains a try-catch block
    if grep -q "try\s*{" "$file"; then
        # Replace all try-catch blocks with ExceptionHandler usage
        sed -i -E 's|try\s*\{([^}]*)\}\s*catch\s*\((Exception\s+\$\w+)\)\s*\{([^}]*)\}|try {\1} catch (\2) {\n    ExceptionHandler::handle(\2);\n}|g' "$file"
        
        echo "Updated try-catch blocks in: $file"
    fi

    # Check if the ExceptionHandler is already included
    if ! grep -q "require_once 'path/to/ExceptionHandler.php';" "$file"; then
        # Add ExceptionHandler inclusion at the top of the file
        sed -i "1s|^|$EXCEPTION_HANDLER_INCLUDE\n|" "$file"
        echo "Added ExceptionHandler inclusion to: $file"
    fi

    # Extract and export all constructor methods
    if grep -q "function __construct" "$file"; then
        echo "Extracting constructor from: $file"
        echo -e "\n--- Constructor from $file ---" >> "$CONSTRUCTS_EXPORT"
        awk '/function __construct/,/}/{print}' "$file" >> "$CONSTRUCTS_EXPORT"
    fi
done

echo "✅ All files processed. Constructor exports saved to $CONSTRUCTS_EXPORT."
