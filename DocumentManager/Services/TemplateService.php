<?php

namespace DocumentManager\Services;

use Exception;

/**
 * Template Service
 *
 * Provides functionality for managing and rendering document templates.
 * Templates support placeholders for dynamic data injection.
 */
class TemplateService
{
    private string $templateDirectory;

    public function __construct(string $templateDirectory)
    {
        if (!is_dir($templateDirectory) || !is_readable($templateDirectory)) {
            throw new \InvalidArgumentException("Invalid template directory: $templateDirectory");
        }

        $this->templateDirectory = rtrim($templateDirectory, DIRECTORY_SEPARATOR);
    }

    /**
     * Get a list of available templates.
     *
     * @return array List of template filenames.
     */
    public function listTemplates(): array
    {
        $files = scandir($this->templateDirectory);
        return array_values(array_filter($files, fn($file) => pathinfo($file, PATHINFO_EXTENSION) === 'html'));
    }

    /**
     * Load a template by filename.
     *
     * @param string $templateName The name of the template file.
     * @return string The template content.
     * @throws Exception If the template does not exist or cannot be read.
     */
    public function loadTemplate(string $templateName): string
    {
        $filePath = $this->templateDirectory . DIRECTORY_SEPARATOR . $templateName;

        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception("Template not found or unreadable: $templateName");
        }

        return file_get_contents($filePath);
    }

    /**
     * Render a template with dynamic data.
     *
     * @param string $templateName The name of the template file.
     * @param array $data Key-value pairs for placeholders and their replacements.
     * @return string Rendered content with placeholders replaced by data.
     * @throws Exception If the template cannot be loaded.
     */
    public function renderTemplate(string $templateName, array $data): string
    {
        $template = $this->loadTemplate($templateName);

        // Replace placeholders in the format {{key}} with their values
        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $template = str_replace($placeholder, htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'), $template);
        }

        return $template;
    }

    /**
     * Save a new or updated template.
     *
     * @param string $templateName The name of the template file.
     * @param string $content The content of the template.
     * @return bool True if the file is saved successfully, false otherwise.
     * @throws Exception If the file cannot be saved.
     */
    public function saveTemplate(string $templateName, string $content): bool
    {
        $filePath = $this->templateDirectory . DIRECTORY_SEPARATOR . $templateName;

        if (file_put_contents($filePath, $content) === false) {
            throw new Exception("Failed to save template: $templateName");
        }

        return true;
    }

    /**
     * Delete a template by filename.
     *
     * @param string $templateName The name of the template file to delete.
     * @return bool True if the file is deleted successfully, false otherwise.
     * @throws Exception If the file cannot be deleted.
     */
    public function deleteTemplate(string $templateName): bool
    {
        $filePath = $this->templateDirectory . DIRECTORY_SEPARATOR . $templateName;

        if (!file_exists($filePath)) {
            throw new Exception("Template not found: $templateName");
        }

        if (!unlink($filePath)) {
            throw new Exception("Failed to delete template: $templateName");
        }

        return true;
    }
}
