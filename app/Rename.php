<?php

/**
 * Script to rename files and directories to comply with PSR-4 naming standards.
 * 
 * - Converts file names (e.g., my_file.php -> MyFile.php).
 * - Converts directory names (e.g., my_folder -> MyFolder).
 */

$baseDir = __DIR__; // Change this to the root directory of your project if needed

/**
 * Recursively renames files and directories.
 *
 * @param string $directory The directory to start renaming.
 */
function renameToPascalCase($directory)
{
    $items = scandir($directory);

    foreach ($items as $item) {
        // Skip "." and ".."
        if ($item === '.' || $item === '..') {
            continue;
        }

        $currentPath = $directory . DIRECTORY_SEPARATOR . $item;

        // If it's a directory, recurse into it
        if (is_dir($currentPath)) {
            $newDirName = convertToPascalCase($item);
            $newDirPath = $directory . DIRECTORY_SEPARATOR . $newDirName;

            if ($newDirName !== $item) {
                rename($currentPath, $newDirPath);
                echo "Renamed directory: $item -> $newDirName\n";
            }

            renameToPascalCase($newDirPath);
        }

        // If it's a file, rename it
        if (is_file($currentPath)) {
            $newFileName = convertToPascalCase(pathinfo($item, PATHINFO_FILENAME)) . '.' . pathinfo($item, PATHINFO_EXTENSION);
            $newFilePath = $directory . DIRECTORY_SEPARATOR . $newFileName;

            if ($newFileName !== $item) {
                rename($currentPath, $newFilePath);
                echo "Renamed file: $item -> $newFileName\n";
            }
        }
    }
}

/**
 * Converts a string to PascalCase.
 *
 * @param string $name The input string to convert.
 * @return string The converted PascalCase string.
 */
function convertToPascalCase($name)
{
    // Split by underscores, hyphens, and camelCase
    $parts = preg_split('/_|-/', $name);

    // Capitalize each part
    $parts = array_map('ucfirst', $parts);

    // Handle camelCase to PascalCase
    $result = implode('', $parts);

    // Capitalize the first character in case of camelCase input
    return ucfirst($result);
}

// Start renaming from the base directory
renameToPascalCase($baseDir);

echo "Renaming complete.\n";
