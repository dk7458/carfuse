<?php

namespace App\Middleware;

use App\Services\Auth\TokenService;
use Exception;

require_once __DIR__ . '/../../App/Helpers/SecurityHelper.php';

class AuthMiddleware
{
    protected TokenService $tokenService;

    public function __construct()
    {
        $configPath = BASE_PATH . '/config/encryption.php';
        if (!file_exists($configPath)) {
            throw new Exception("Encryption configuration missing.");
        }

        $encryptionConfig = require $configPath;

        if (!isset($encryptionConfig['jwt_secret'], $encryptionConfig['jwt_refresh_secret'])) {
            throw new Exception("JWT configuration missing in encryption.php.");
        }

        $this->tokenService = new TokenService(
            $encryptionConfig['jwt_secret'],
            $encryptionConfig['jwt_refresh_secret']
        );
    }

    public function handle($request, $next)
    {
        $publicRoutes = ['/public', '/api/public']; // Define public routes

        if (in_array($request->getPathInfo(), $publicRoutes)) {
            return $next($request); // Allow guest access for public routes
        }

        if (!validateSessionIntegrity()) {
            http_response_code(401);
            echo json_encode(['error' => 'Session expired']);
            $this->logAuthAttempt('failure', 'Session expired');
            return;
        }

        $authHeader = $request->getHeader('Authorization');
        if (!$authHeader) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            $this->logAuthAttempt('failure', 'Missing Authorization header');
            return;
        }

        $token = str_replace('Bearer ', '', $authHeader);
        if (!$this->tokenService->validateToken($token)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token']);
            $this->logAuthAttempt('failure', 'Invalid token');
            return;
        }

        return $next($request);
    }

    private function logAuthAttempt($status, $message)
    {
        $logMessage = sprintf("[%s] %s: %s from IP: %s\n", date('Y-m-d H:i:s'), ucfirst($status), $message, $_SERVER['REMOTE_ADDR']);
        file_put_contents(__DIR__ . '/../../logs/auth.log', $logMessage, FILE_APPEND);
    }
}
