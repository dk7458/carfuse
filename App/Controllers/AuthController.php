<?php

namespace App\Controllers;

use App\Services\Auth\AuthService;
use App\Services\Auth\TokenService;
use Exception;
use App\Helpers\ApiHelper;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;

class AuthController extends Controller
{
    private AuthService $authService;
    private TokenService $tokenService;
    private ExceptionHandler $exceptionHandler;
    private LoggerInterface $authLogger;
    private LoggerInterface $auditLogger;

    public function __construct(
        AuthService $authService,
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler,
        LoggerInterface $authLogger,
        LoggerInterface $auditLogger
    ) {
        $this->authService = $authService;
        $this->tokenService = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
        $this->authLogger = $authLogger;
        $this->auditLogger = $auditLogger;
    }

    public function login()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data || !isset($data['email']) || !isset($data['password'])) {
                return ApiHelper::sendJsonResponse('error', 'Invalid credentials', [], 400);
            }
            
            $email = $data['email'];
            $password = $data['password'];
            
            $result = $this->authService->login($email, $password);
            if (!isset($result['token']) || !isset($result['refresh_token'])) {
                return ApiHelper::sendJsonResponse('error', 'Authentication failed', [], 401);
            }
            
            // Set secure cookies
            setcookie("jwt", $result['token'], [
                "expires"  => time() + 3600,
                "path"     => "/",
                "secure"   => true,
                "httponly" => true,
                "samesite" => "Strict"
            ]);
            
            setcookie("refresh_token", $result['refresh_token'], [
                "expires"  => time() + 604800, // 7 days
                "path"     => "/",
                "secure"   => true,
                "httponly" => true,
                "samesite" => "Strict"
            ]);
            
            $this->authLogger->info("User logged in successfully", ['email' => $email]);
            return ApiHelper::sendJsonResponse('success', 'User logged in successfully', [], 200);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Authentication failed', [], 401);
        }
    }

    public function register()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) {
                return ApiHelper::sendJsonResponse('error', 'Invalid input data', [], 400);
            }
            
            // Registration data validation happens in the service
            $result = $this->authService->registerUser($data);
            
            // Log successful registration
            $this->auditLogger->info("User registered successfully", ['email' => $data['email'] ?? 'unknown']);
            
            return ApiHelper::sendJsonResponse('success', 'User registered successfully', $result, 201);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', $e->getMessage(), [], 400);
        }
    }

    public function logout()
    {
        try {
            // Clear auth cookies
            setcookie("jwt", "", [
                "expires"  => time() - 3600,
                "path"     => "/",
                "secure"   => true,
                "httponly" => true,
                "samesite" => "Strict"
            ]);
            
            setcookie("refresh_token", "", [
                "expires"  => time() - 3600,
                "path"     => "/",
                "secure"   => true,
                "httponly" => true,
                "samesite" => "Strict"
            ]);
            
            $this->auditLogger->info("User logged out");
            return ApiHelper::sendJsonResponse('success', 'User logged out successfully', [], 200);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Logout failed', [], 500);
        }
    }

    public function refresh()
    {
        try {
            $refreshToken = $_COOKIE['refresh_token'] ?? null;
            if (!$refreshToken) {
                return ApiHelper::sendJsonResponse('error', 'Refresh token missing', [], 400);
            }
            
            $newToken = $this->tokenService->refreshToken($refreshToken);
            if (!$newToken) {
                return ApiHelper::sendJsonResponse('error', 'Invalid refresh token', [], 401);
            }
            
            // Set new JWT cookie
            setcookie("jwt", $newToken, [
                "expires"  => time() + 3600,
                "path"     => "/",
                "secure"   => true,
                "httponly" => true,
                "samesite" => "Strict"
            ]);
            
            return ApiHelper::sendJsonResponse('success', 'Token refreshed successfully', [], 200);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Token refresh failed', [], 401);
        }
    }

    public function resetPasswordRequest()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data || !isset($data['email'])) {
                return ApiHelper::sendJsonResponse('error', 'Email is required', [], 400);
            }
            
            $result = $this->authService->resetPasswordRequest($data['email']);
            $this->authLogger->info("Password reset requested", ['email' => $data['email']]);
            
            // Don't expose token in response for security
            return ApiHelper::sendJsonResponse('success', 'Password reset instructions sent', [], 200);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Password reset request failed', [], 400);
        }
    }

    public function validateToken($token = null)
    {
        try {
            $token = $token ?? ($_COOKIE['jwt'] ?? null);
            if (!$token) {
                return ApiHelper::sendJsonResponse('error', 'No token provided', [], 401);
            }
            
            $isValid = $this->tokenService->validateToken($token);
            if (!$isValid) {
                return ApiHelper::sendJsonResponse('error', 'Invalid token', [], 401);
            }
            
            return ApiHelper::sendJsonResponse('success', 'Token is valid', [], 200);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Token validation failed', [], 401);
        }
    }
}
