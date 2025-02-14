<?php

namespace DocumentManager\Services;

use Exception;
use Psr\Log\LoggerInterface;

/**
 * Template Service
 *
 * Provides functionality for managing and rendering document templates.
 * Templates support placeholders for dynamic data injection.
 */
class TemplateService
{
    private string $templateDirectory;
    private LoggerInterface $logger;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger The logger instance.
     * @param string $templateDirectory The directory where templates are stored.
     * @throws Exception If the directory is invalid or not readable.
     */
    public function __construct(LoggerInterface $logger, string $templateDirectory)
    {
        if (!is_dir($templateDirectory) || !is_readable($templateDirectory)) {
            throw new \InvalidArgumentException("Invalid template directory: $templateDirectory");
        }

        $this->templateDirectory = rtrim($templateDirectory, DIRECTORY_SEPARATOR);
        $this->logger = $logger;
    }

    /**
     * List all available templates in the directory.
     *
     * @return array List of template filenames with '.html' extension.
     */
    public function listTemplates(): array
    {
        $files = scandir($this->templateDirectory);
        return array_values(array_filter($files, fn($file) => pathinfo($file, PATHINFO_EXTENSION) === 'html'));
    }

    /**
     * Load the content of a template.
     *
     * @param string $templateName The name of the template file.
     * @return string The template content.
     * @throws Exception If the template cannot be found or read.
     */
    public function loadTemplate(string $templateName): string
    {
        try {
            $filePath = $this->getTemplatePath($templateName);

            if (!file_exists($filePath) || !is_readable($filePath)) {
                throw new Exception("Template not found or unreadable: $templateName");
            }

            $content = file_get_contents($filePath);
            $this->logger->info("[TemplateService] Loaded template: {$templateName}");
            return $content;
        } catch (\Exception $e) {
            $this->logger->error("[TemplateService] Error loading template: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Render a template by replacing placeholders with data.
     *
     * @param string $templateName The name of the template file.
     * @param array $data Key-value pairs to replace placeholders.
     * @return string Rendered template with placeholders replaced.
     * @throws Exception If the template cannot be loaded.
     */
    public function renderTemplate(string $templateName, array $data): string
    {
        $template = $this->loadTemplate($templateName);

        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $template = str_replace($placeholder, htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'), $template);
        }

        $this->logger->info("[TemplateService] Rendered template: {$templateName}");
        return $template;
    }

    /**
     * Save a new or updated template file.
     *
     * @param string $templateName The name of the template file.
     * @param string $content The template content to save.
     * @return bool True if saved successfully, false otherwise.
     * @throws Exception If saving fails.
     */
    public function saveTemplate(string $templateName, string $content): bool
    {
        try {
            $filePath = $this->getTemplatePath($templateName);

            if (file_put_contents($filePath, $content) === false) {
                throw new Exception("Failed to save template: $templateName");
            }

            $this->logger->info("[TemplateService] Saved template: {$templateName}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("[TemplateService] Error saving template: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a template file.
     *
     * @param string $templateName The name of the template file to delete.
     * @return bool True if deleted successfully, false otherwise.
     * @throws Exception If the template cannot be found or deleted.
     */
    public function deleteTemplate(string $templateName): bool
    {
        try {
            $filePath = $this->getTemplatePath($templateName);

            if (!file_exists($filePath)) {
                throw new Exception("Template not found: $templateName");
            }

            if (!unlink($filePath)) {
                throw new Exception("Failed to delete template: $templateName");
            }

            $this->logger->info("[TemplateService] Deleted template: {$templateName}");
            return true;
        } catch (\Exception $e) {
            $this->logger->error("[TemplateService] Error deleting template: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate and sanitize template filename.
     *
     * @param string $templateName The name of the template file.
     * @return string Sanitized template file path.
     */
    private function getTemplatePath(string $templateName): string
    {
        $sanitizedFileName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $templateName);

        if (pathinfo($sanitizedFileName, PATHINFO_EXTENSION) !== 'html') {
            $sanitizedFileName .= '.html';
        }

        return $this->templateDirectory . DIRECTORY_SEPARATOR . $sanitizedFileName;
    }
}
