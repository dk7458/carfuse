<?php

namespace App\Controllers;

use App\Helpers\ApiHelper;
use App\Helpers\ExceptionHandler;
use App\Services\SettingsService;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Admin Settings Controller
 *
 * Handles all system settings management functionality for the admin panel
 */
class AdminSettingsController extends Controller
{
    private SettingsService $settingsService;
    private AuditService $auditService;
    protected ExceptionHandler $exceptionHandler;
    protected LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        ExceptionHandler $exceptionHandler,
        SettingsService $settingsService,
        AuditService $auditService
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->settingsService = $settingsService;
        $this->auditService = $auditService;
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * Display the settings page
     */
    public function showSettingsPage(Request $request, Response $response): Response
    {
        try {
            // Security check - verify admin role
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
                return $response->withHeader('Location', '/auth/login')->withStatus(302);
            }
            
            // Log the settings page view
            $this->auditService->logEvent(
                'admin_settings_viewed',
                'Admin accessed system settings page',
                [],
                $_SESSION['user_id'],
                null,
                'admin'
            );
            
            // Include the settings view
            include BASE_PATH . '/public/views/admin/settings.php';
            return $response;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $response->withHeader('Location', '/admin/error')->withStatus(302);
        }
    }

    /**
     * Get all settings for the admin panel
     */
    public function getAllSettings(Request $request, Response $response): Response
    {
        try {
            // Verify admin permissions
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
                return ApiHelper::sendJsonResponse('error', 'Unauthorized access', [], 401);
            }

            // Get all system settings
            $settings = $this->settingsService->getAllSettings();
            
            return ApiHelper::sendJsonResponse('success', 'Settings retrieved successfully', $settings);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Failed to retrieve settings', [], 500);
        }
    }

    /**
     * Save all settings at once
     */
    public function saveAllSettings(Request $request, Response $response): Response
    {
        try {
            // Verify admin permissions
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
                return ApiHelper::sendJsonResponse('error', 'Unauthorized access', [], 401);
            }
            
            $data = json_decode($request->getBody()->getContents(), true);
            if (!is_array($data)) {
                return ApiHelper::sendJsonResponse('error', 'Invalid settings data format', [], 400);
            }
            
            // Save all settings
            $result = $this->settingsService->saveSettings($data);
            
            if ($result) {
                // Log the settings update
                $this->auditService->logEvent(
                    'settings_updated',
                    'Admin updated system settings',
                    ['updated_keys' => array_keys($data)],
                    $_SESSION['user_id'],
                    null,
                    'admin'
                );
                
                return ApiHelper::sendJsonResponse('success', 'Settings saved successfully');
            } else {
                return ApiHelper::sendJsonResponse('error', 'Failed to save settings', [], 500);
            }
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'An error occurred while saving settings', [], 500);
        }
    }
    
    /**
     * Save specific tab settings
     */
    public function saveTabSettings(Request $request, Response $response, array $args): Response
    {
        try {
            // Verify admin permissions
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
                return ApiHelper::sendJsonResponse('error', 'Unauthorized access', [], 401);
            }
            
            $tabName = $args['tab'] ?? '';
            if (empty($tabName)) {
                return ApiHelper::sendJsonResponse('error', 'No tab specified', [], 400);
            }
            
            $data = json_decode($request->getBody()->getContents(), true);
            if (!is_array($data)) {
                return ApiHelper::sendJsonResponse('error', 'Invalid settings data format', [], 400);
            }
            
            // Save tab specific settings
            $result = $this->settingsService->saveTabSettings($tabName, $data);
            
            if ($result) {
                // Log the settings update
                $this->auditService->logEvent(
                    'settings_tab_updated',
                    'Admin updated ' . $tabName . ' settings',
                    ['tab' => $tabName, 'updated_keys' => array_keys($data)],
                    $_SESSION['user_id'],
                    null,
                    'admin'
                );
                
                return ApiHelper::sendJsonResponse('success', $tabName . ' settings saved successfully');
            } else {
                return ApiHelper::sendJsonResponse('error', 'Failed to save ' . $tabName . ' settings', [], 500);
            }
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'An error occurred while saving settings', [], 500);
        }
    }
    
    /**
     * Test email connection
     */
    public function testEmailConnection(Request $request, Response $response): Response
    {
        try {
            // Verify admin permissions
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
                return ApiHelper::sendJsonResponse('error', 'Unauthorized access', [], 401);
            }
            
            $data = json_decode($request->getBody()->getContents(), true);
            if (!is_array($data)) {
                return ApiHelper::sendJsonResponse('error', 'Invalid email settings data', [], 400);
            }
            
            // Test email connection with provided settings
            $result = $this->settingsService->testEmailConnection($data);
            
            if ($result === true) {
                return ApiHelper::sendJsonResponse('success', 'Email connection test successful');
            } else {
                return ApiHelper::sendJsonResponse('error', 'Email connection test failed: ' . $result, [], 400);
            }
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'An error occurred while testing email connection', [], 500);
        }
    }

    /**
     * GET /admin/settings/{category} - Retrieve settings for a given category
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function getSettingsByCategory(Request $request, Response $response, array $args): Response
    {
        try {
            // Verify admin permissions
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
                return ApiHelper::sendJsonResponse('error', 'Unauthorized access', [], 401);
            }

            $category = $args['category'] ?? '';
            if (empty($category)) {
                return ApiHelper::sendJsonResponse('error', 'No category specified', [], 400);
            }
            
            // Validate that the category is one of the allowed values
            $allowedCategories = ['general', 'security', 'notifications'];
            if (!in_array($category, $allowedCategories)) {
                return ApiHelper::sendJsonResponse('error', 'Invalid category specified', [], 400);
            }

            // Get settings for the specified category
            $settings = $this->settingsService->getCategorySettings($category);
            
            // Log the settings retrieval
            $this->auditService->logEvent(
                'settings_category_viewed',
                'Admin retrieved ' . $category . ' settings',
                ['category' => $category],
                $_SESSION['user_id'],
                null,
                'admin'
            );
            
            return ApiHelper::sendJsonResponse('success', $category . ' settings retrieved successfully', $settings);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Failed to retrieve settings', [], 500);
        }
    }

    /**
     * PUT /admin/settings/{category} - Update settings for a given category
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function updateSettingsByCategory(Request $request, Response $response, array $args): Response
    {
        try {
            // Verify admin permissions
            if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
                return ApiHelper::sendJsonResponse('error', 'Unauthorized access', [], 401);
            }
            
            $category = $args['category'] ?? '';
            if (empty($category)) {
                return ApiHelper::sendJsonResponse('error', 'No category specified', [], 400);
            }
            
            // Validate that the category is one of the allowed values
            $allowedCategories = ['general', 'security', 'notifications'];
            if (!in_array($category, $allowedCategories)) {
                return ApiHelper::sendJsonResponse('error', 'Invalid category specified', [], 400);
            }
            
            $data = json_decode($request->getBody()->getContents(), true);
            if (!is_array($data)) {
                return ApiHelper::sendJsonResponse('error', 'Invalid settings data format', [], 400);
            }
            
            // Validate settings based on category
            $validationErrors = $this->validateCategorySettings($category, $data);
            if (!empty($validationErrors)) {
                return ApiHelper::sendJsonResponse('error', 'Validation errors', ['errors' => $validationErrors], 400);
            }
            
            // Save category specific settings
            $result = $this->settingsService->saveTabSettings($category, $data);
            
            if ($result) {
                // Log the settings update
                $this->auditService->logEvent(
                    'settings_category_updated',
                    'Admin updated ' . $category . ' settings',
                    ['category' => $category, 'updated_keys' => array_keys($data)],
                    $_SESSION['user_id'],
                    null,
                    'admin'
                );
                
                return ApiHelper::sendJsonResponse('success', $category . ' settings saved successfully');
            } else {
                return ApiHelper::sendJsonResponse('error', 'Failed to save ' . $category . ' settings', [], 500);
            }
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'An error occurred while saving settings', [], 500);
        }
    }

    /**
     * Validate settings based on category
     */
    private function validateCategorySettings(string $category, array $data): array
    {
        $errors = [];
        
        switch ($category) {
            case 'general':
                // Validate site_name
                if (isset($data['site_name'])) {
                    if (empty($data['site_name'])) {
                        $errors['site_name'] = 'Site name is required';
                    } elseif (strlen($data['site_name']) > 255) {
                        $errors['site_name'] = 'Site name cannot exceed 255 characters';
                    }
                }
                
                // Validate site_url
                if (isset($data['site_url'])) {
                    if (empty($data['site_url'])) {
                        $errors['site_url'] = 'Site URL is required';
                    } elseif (!filter_var($data['site_url'], FILTER_VALIDATE_URL)) {
                        $errors['site_url'] = 'Site URL must be a valid URL';
                    }
                }
                
                // Validate contact_email
                if (isset($data['contact_email'])) {
                    if (empty($data['contact_email'])) {
                        $errors['contact_email'] = 'Contact email is required';
                    } elseif (!filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
                        $errors['contact_email'] = 'Contact email must be a valid email address';
                    }
                }
                break;
                
            case 'security':
                // Validate password_min_length
                if (isset($data['password_min_length'])) {
                    if (!is_numeric($data['password_min_length'])) {
                        $errors['password_min_length'] = 'Password minimum length must be a number';
                    } elseif ((int)$data['password_min_length'] < 6) {
                        $errors['password_min_length'] = 'Password minimum length cannot be less than 6';
                    }
                }
                
                // Validate enable_2fa
                if (isset($data['enable_2fa']) && !is_bool($data['enable_2fa'])) {
                    $errors['enable_2fa'] = 'Enable 2FA must be a boolean value';
                }
                
                // Validate session_timeout
                if (isset($data['session_timeout'])) {
                    if (!is_numeric($data['session_timeout'])) {
                        $errors['session_timeout'] = 'Session timeout must be a number';
                    } elseif ((int)$data['session_timeout'] < 10) {
                        $errors['session_timeout'] = 'Session timeout cannot be less than 10 minutes';
                    }
                }
                break;
                
            case 'notifications':
                // Validate notify_on_comment
                if (isset($data['notify_on_comment']) && !is_bool($data['notify_on_comment'])) {
                    $errors['notify_on_comment'] = 'Notify on comment must be a boolean value';
                }
                
                // Validate smtp_server
                if (isset($data['smtp_server']) && empty($data['smtp_server'])) {
                    $errors['smtp_server'] = 'SMTP server is required';
                }
                
                // Validate smtp_port
                if (isset($data['smtp_port'])) {
                    if (!is_numeric($data['smtp_port'])) {
                        $errors['smtp_port'] = 'SMTP port must be a number';
                    } elseif ((int)$data['smtp_port'] <= 0 || (int)$data['smtp_port'] > 65535) {
                        $errors['smtp_port'] = 'SMTP port must be a valid port number (1-65535)';
                    }
                }
                break;
        }
        
        return $errors;
    }
}
