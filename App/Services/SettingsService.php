<?php

namespace App\Services;

use App\Models\Setting;
use Psr\Log\LoggerInterface;

/**
 * Settings Service
 * 
 * Handles all system settings operations
 */
class SettingsService
{
    private LoggerInterface $logger;
    private Setting $settingModel;
    
    public function __construct(
        LoggerInterface $logger,
        Setting $settingModel
    ) {
        $this->logger = $logger;
        $this->settingModel = $settingModel;
    }
    
    /**
     * Get all system settings
     */
    public function getAllSettings(): array
    {
        try {
            $settings = $this->settingModel->getAll();
            
            // Convert settings array to key-value pairs
            $formattedSettings = [];
            foreach ($settings as $setting) {
                $formattedSettings[$setting['key']] = $this->decodeSettingValue($setting['value'], $setting['type']);
            }
            
            return $formattedSettings;
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Save all settings at once
     */
    public function saveSettings(array $settings): bool
    {
        try {
            foreach ($settings as $key => $value) {
                $this->updateSetting($key, $value);
            }
            
            $this->clearCache();
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to save settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Save tab-specific settings
     */
    public function saveTabSettings(string $tab, array $settings): bool
    {
        try {
            foreach ($settings as $key => $value) {
                $this->updateSetting($key, $value);
            }
            
            $this->clearCache();
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Failed to save {$tab} settings", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Test email connection
     */
    public function testEmailConnection(array $emailSettings): bool|string
    {
        try {
            $host = $emailSettings['smtp_host'] ?? '';
            $port = $emailSettings['smtp_port'] ?? 587;
            $encryption = $emailSettings['email_encryption'] ?? 'tls';
            $username = $emailSettings['smtp_username'] ?? '';
            $password = $emailSettings['smtp_password'] ?? '';
            $sender = $emailSettings['email_sender'] ?? '';
            
            if (empty($host) || empty($username) || empty($password) || empty($sender)) {
                return 'Missing required email settings';
            }
            
            // Create a test mail configuration
            $transport = (new \Swift_SmtpTransport($host, $port))
                ->setUsername($username)
                ->setPassword($password);
                
            if ($encryption !== 'none') {
                $transport->setEncryption($encryption);
            }
            
            // Create the mailer using the transport
            $mailer = new \Swift_Mailer($transport);
            
            // Create a test message
            $message = (new \Swift_Message('CarFuse: Test Email Connection'))
                ->setFrom([$sender])
                ->setTo([$sender])
                ->setBody('This is a test email to verify your SMTP connection settings are working correctly.');
                
            // Send the test message
            $result = $mailer->send($message);
            
            if ($result > 0) {
                return true;
            } else {
                return 'Failed to send test email';
            }
        } catch (\Exception $e) {
            $this->logger->error('Email connection test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $e->getMessage();
        }
    }
    
    /**
     * Get a specific setting value
     */
    public function getSetting(string $key, $default = null)
    {
        try {
            $setting = $this->settingModel->getByKey($key);
            
            if (!$setting) {
                return $default;
            }
            
            return $this->decodeSettingValue($setting['value'], $setting['type']);
        } catch (\Exception $e) {
            $this->logger->error("Failed to get setting '{$key}'", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $default;
        }
    }
    
    /**
     * Update a single setting
     */
    private function updateSetting(string $key, $value): bool
    {
        // Determine the type of the value for proper storage
        $type = $this->getValueType($value);
        
        // Encode the value based on its type
        $encodedValue = $this->encodeSettingValue($value, $type);
        
        return $this->settingModel->saveSetting($key, $encodedValue, $type);
    }
    
    /**
     * Clear settings cache
     */
    private function clearCache(): void
    {
        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }
        
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
    
    /**
     * Determine the type of a value
     */
    private function getValueType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        } elseif (is_int($value)) {
            return 'integer';
        } elseif (is_float($value)) {
            return 'float';
        } elseif (is_array($value)) {
            return 'array';
        } elseif (is_object($value)) {
            return 'object';
        } else {
            return 'string';
        }
    }
    
    /**
     * Encode setting value based on its type for storage
     */
    private function encodeSettingValue($value, string $type): string
    {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'array':
            case 'object':
                return json_encode($value);
            case 'integer':
            case 'float':
                return (string)$value;
            default:
                return (string)$value;
        }
    }
    
    /**
     * Decode setting value based on its type
     */
    private function decodeSettingValue(string $value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return $value === '1' || $value === 'true';
            case 'integer':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'array':
            case 'object':
                $decoded = json_decode($value, true);
                return $decoded !== null ? $decoded : [];
            default:
                return $value;
        }
    }
}
