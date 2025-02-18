<?php

namespace App\Helpers;

/**
 * API Helper Functions
 */
class ApiHelper
{
    /**
     * ✅ Log API Events for Debugging
     */
    public static function logApiEvent($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logFile = __DIR__ . '/../../logs/api.log';
        file_put_contents($logFile, "{$timestamp} - {$message}\n", FILE_APPEND);
    }

    /**
     * ✅ Standardized JSON Response Function
     */
    public static function sendJsonResponse($status, $message, $data = [], $httpCode = 200)
    {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
        exit();
    }

    /**
     * ✅ Extract JWT from Authorization Header or Cookie
     */
    public static function getJWT()
    {
        $headers = getallheaders();
        if (isset($headers['Authorization']) && preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
        return $_COOKIE['jwt'] ?? null;
    }
}
