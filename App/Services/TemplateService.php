<?php

namespace App\Services;

use Exception;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use App\Helpers\LoggingHelper;
use App\Models\DocumentTemplate;
use App\Services\AuditService;

/**
 * Template Service
 *
 * Provides functionality for managing and rendering document templates.
 * Templates support placeholders for dynamic data injection.
 */
class TemplateService
{
    public const DEBUG_MODE = true;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private AuditService $auditService;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger The logger instance.
     * @param ExceptionHandler $exceptionHandler The exception handler instance.
     * @param AuditService $auditService The audit service instance.
     */
    public function __construct(
        LoggerInterface $logger, 
        ExceptionHandler $exceptionHandler,
        AuditService $auditService
    ) {
        $this->logger = LoggingHelper::getLoggerByCategory('template');
        $this->exceptionHandler = $exceptionHandler;
        $this->auditService = $auditService;
    }

    /**
     * List all available templates.
     *
     * @return array List of templates.
     */
    public function listTemplates(): array
    {
        return DocumentTemplate::all()->toArray();
    }

    /**
     * Load the content of a template.
     *
     * @param int|string $templateId The ID or name of the template.
     * @return DocumentTemplate The template.
     * @throws Exception If the template cannot be found.
     */
    public function loadTemplate($templateId): DocumentTemplate
    {
        try {
            $template = is_numeric($templateId) 
                ? DocumentTemplate::findOrFail($templateId)
                : DocumentTemplate::where('name', $templateId)->firstOrFail();
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[system] Loaded template", ['template' => $template->name]);
            }
            
            $this->auditService->logEvent('template_loaded', [
                'template_id' => $template->id,
                'template_name' => $template->name
            ]);
            
            return $template;
        } catch (\Exception $e) {
            $this->logger->error("[system] ❌ Error loading template: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Render a template by replacing placeholders with data.
     *
     * @param int|string $templateId The ID or name of the template.
     * @param array $data Key-value pairs to replace placeholders.
     * @return string Rendered template with placeholders replaced.
     * @throws Exception If the template cannot be loaded.
     */
    public function renderTemplate($templateId, array $data): string
    {
        $template = $this->loadTemplate($templateId);
        $content = $template->content;

        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'), $content);
        }

        $this->auditService->logEvent('template_rendered', [
            'template_id' => $template->id,
            'template_name' => $template->name
        ]);
        
        return $content;
    }

    /**
     * Save a new or updated template.
     *
     * @param string $templateName The name of the template.
     * @param string $content The template content to save.
     * @param int|null $templateId The template ID for updates (null for new templates).
     * @return DocumentTemplate The saved template.
     * @throws Exception If saving fails.
     */
    public function saveTemplate(string $templateName, string $content, ?int $templateId = null): DocumentTemplate
    {
        try {
            if ($templateId) {
                $template = DocumentTemplate::findOrFail($templateId);
                $template->name = $templateName;
                $template->content = $content;
                $template->save();
            } else {
                $template = DocumentTemplate::create([
                    'name' => $templateName,
                    'content' => $content
                ]);
            }
            
            return $template;
        } catch (\Exception $e) {
            $this->logger->error("Error saving template", ['template' => $templateName, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Delete a template.
     *
     * @param int $templateId The ID of the template to delete.
     * @return bool True if deleted successfully.
     * @throws Exception If the template cannot be found or deleted.
     */
    public function deleteTemplate(int $templateId): bool
    {
        try {
            $template = DocumentTemplate::findOrFail($templateId);
            $template->delete();
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Error deleting template", ['template_id' => $templateId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
