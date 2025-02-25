<?php

namespace App\Controllers;

use App\Services\Auth\TokenService;
use App\Services\Auth\AuthService;
use Exception;
use App\Helpers\ApiHelper;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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

    /**
     * Login a user with email and password, return JWT
     * 
     * @return array JSON response with token
     */
    public function login()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data || !isset($data['email']) || !isset($data['password'])) {
                return ApiHelper::sendJsonResponse('error', 'Email and password are required', [], 400);
            }
            
            $email = $data['email'];
            $password = $data['password'];
            
            // Authenticate user
            $result = $this->authService->login($email, $password);
            if (!$result || !isset($result['token'])) {
                return ApiHelper::sendJsonResponse('error', 'Authentication failed', [], 401);
            }
            
            $this->authLogger->info("User logged in successfully", ['email' => $email]);
            
            // Return token directly in response (no cookies)
            return ApiHelper::sendJsonResponse('success', 'Authentication successful', [
                'access_token' => $result['token'],
                'refresh_token' => $result['refresh_token'],
                'token_type' => 'bearer',
                'expires_in' => 3600 // 1 hour
            ], 200);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            $this->authLogger->error("Login failed", ['error' => $e->getMessage()]);
            return ApiHelper::sendJsonResponse('error', 'Authentication failed', [], 401);
        }
    }

    /**
     * Register a new user
     * 
     * @return array JSON response
     */
    public function register()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) {
                return ApiHelper::sendJsonResponse('error', 'Invalid input data', [], 400);
            }
            
            // Registration data validation happens in the service
            $result = $this->authService->registerUser($data);
            
            $this->auditLogger->info("User registered successfully", ['email' => $data['email'] ?? 'unknown']);
            
            return ApiHelper::sendJsonResponse('success', 'User registered successfully', $result, 201);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            $this->authLogger->error("Registration failed", ['error' => $e->getMessage()]);
            return ApiHelper::sendJsonResponse('error', $e->getMessage(), [], 400);
        }
    }

    /**
     * Refresh an access token using a refresh token
     * 
     * @return array JSON response with new token
     */
    public function refreshToken()
    {
        try {
            // Get refresh token from Authorization header
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                return ApiHelper::sendJsonResponse('error', 'No refresh token provided', [], 400);
            }
            
            $refreshToken = $matches[1];
            $newToken = $this->tokenService->refreshToken($refreshToken);
            
            if (!$newToken) {
                return ApiHelper::sendJsonResponse('error', 'Invalid refresh token', [], 401);
            }
            
            $this->authLogger->info("Token refreshed successfully");
            
            return ApiHelper::sendJsonResponse('success', 'Token refreshed successfully', [
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => 3600 // 1 hour
            ], 200);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Token refresh failed', [], 401);
        }
    }

    /**
     * Request a password reset
     * 
     * @return array JSON response
     */
    public function resetPasswordRequest()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data || !isset($data['email'])) {
                return ApiHelper::sendJsonResponse('error', 'Email is required', [], 400);
            }
            
            $result = $this->authService->resetPasswordRequest($data['email']);
            $this->authLogger->info("Password reset requested", ['email' => $data['email']]);
            
            // Don't expose actual token in response for security
            return ApiHelper::sendJsonResponse('success', 'Password reset instructions sent', [], 200);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Password reset request failed', [], 400);
        }
    }

    /**
     * Reset password with token
     * 
     * @return array JSON response
     */
    public function resetPassword()
    {
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data || !isset($data['email']) || !isset($data['token']) || !isset($data['password'])) {
                return ApiHelper::sendJsonResponse('error', 'Missing required fields', [], 400);
            }
            
            $result = $this->authService->resetPassword(
                $data['email'],
                $data['token'],
                $data['password']
            );
            
            $this->authLogger->info("Password reset successfully", ['email' => $data['email']]);
            return ApiHelper::sendJsonResponse('success', 'Password reset successfully', [], 200);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Password reset failed', [], 400);
        }
    }

    /**
     * Logout a user by revoking their refresh token
     * 
     * @return array JSON response
     */
    public function logout()
    {
        try {
            // Get refresh token from Authorization header
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                return ApiHelper::sendJsonResponse('error', 'No token provided', [], 400);
            }
            
            $token = $matches[1];
            
            // Revoke the token
            $this->tokenService->revokeToken($token);
            $this->auditLogger->info("User logged out");
            
            return ApiHelper::sendJsonResponse('success', 'Logout successful', [], 200);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Logout failed', [], 500);
        }
    }

    /**
     * Validate a token
     * 
     * @return array JSON response
     */
    public function validateToken()
    {
        try {
            // Get token from Authorization header
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                return ApiHelper::sendJsonResponse('error', 'No token provided', [], 401);
            }
            
            $token = $matches[1];
            $isValid = $this->tokenService->validateToken($token);
            
            if (!$isValid) {
                return ApiHelper::sendJsonResponse('error', 'Invalid token', [], 401);
            }
            
            return ApiHelper::sendJsonResponse('success', 'Valid token', [], 200);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Token validation failed', [], 401);
        }
    }
}
