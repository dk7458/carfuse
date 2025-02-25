<?php

namespace App\Controllers;

use App\Services\Auth\TokenService;
use App\Services\Auth\AuthService;
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

    public function login($request = null)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !is_array($data)) {
            return ApiHelper::sendJsonResponse('error', 'Invalid JSON input', ['errors' => (object)[]], 400);
        }
        
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        
        $result = $this->authService->login($email, $password);
        setcookie("jwt", $result['token'], [
            "expires"  => time() + 3600,
            "path"     => "/",
            "secure"   => true,
            "httponly" => true,
            "samesite" => "Strict"
        ]);
        setcookie("refresh_token", $result['refresh_token'], [
            "expires"  => time() + 604800,
            "path"     => "/",
            "secure"   => true,
            "httponly" => true,
            "samesite" => "Strict"
        ]);
        $this->authLogger->info("User logged in", ['email' => $email]);
        return ApiHelper::sendJsonResponse('success', 'User logged in', ['errors' => (object)[]], 200);
    }

    public function register($request = null)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !is_array($data)) {
            return ApiHelper::sendJsonResponse('error', 'Invalid JSON input', ['errors' => (object)[]], 400);
        }
        
        $rules = [
            'name'             => 'required',
            'email'            => 'required|email',
            'password'         => 'required',
            'confirm_password' => 'required'
        ];
        if (!$this->authService->getValidator()->validate($data, $rules)) {
            return ApiHelper::sendJsonResponse('error', 'Validation failed', ['errors' => $this->authService->getValidator()->errors()], 400);
        }
        if ($data['password'] !== $data['confirm_password']) {
            return ApiHelper::sendJsonResponse('error', 'Password and confirm password do not match', ['errors' => (object)[]], 400);
        }
        
        try {
            $result = $this->authService->registerUser($data);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            $this->auditLogger->error("User registration failed", [
                'error' => $e->getMessage(),
                'email' => $data['email']
            ]);
            return; // ExceptionHandler is assumed to handle the response
        }
        $this->auditLogger->info("User registered", ['email' => $data['email']]);
        return ApiHelper::sendJsonResponse('success', 'User registered', $result, 201);
    }

    public function resetPasswordRequest($request = null)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !is_array($data)) {
            return ApiHelper::sendJsonResponse('error', 'Invalid JSON input', ['errors' => (object)[]], 400);
        }
        $email = $data['email'] ?? '';
        $result = $this->authService->resetPasswordRequest($email);
        return ApiHelper::sendJsonResponse('success', 'Password reset request processed', $result, 200);
    }

    public function refresh()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ApiHelper::sendJsonResponse('error', 'Method Not Allowed', ['errors' => (object)[]], 405);
        }
        $refreshToken = $_COOKIE['refresh_token'] ?? null;
        if (!$refreshToken) {
            return ApiHelper::sendJsonResponse('error', 'Refresh token is required', ['errors' => (object)[]], 400);
        }
        $newToken = $this->tokenService->refreshToken($refreshToken);
        if ($newToken) {
            setcookie("jwt", $newToken, [
                "expires"  => time() + 3600,
                "path"     => "/",
                "secure"   => true,
                "httponly" => true,
                "samesite" => "Strict"
            ]);
            return ApiHelper::sendJsonResponse('success', 'Token refreshed', ['errors' => (object)[]], 200);
        } else {
            return ApiHelper::sendJsonResponse('error', 'Invalid refresh token', ['errors' => (object)[]], 401);
        }
    }

    public function logout($request = null)
    {
        setcookie("jwt", "", [
            "expires" => time() - 3600,
            "path" => "/",
            "secure" => true,
            "httponly" => true,
            "samesite" => "Strict"
        ]);
        setcookie("refresh_token", "", [
            "expires" => time() - 3600,
            "path" => "/",
            "secure" => true,
            "httponly" => true,
            "samesite" => "Strict"
        ]);
        $this->authService->logout();
        $this->auditLogger->info("User logged out");
        return ApiHelper::sendJsonResponse('success', 'User logged out', ['errors' => (object)[]], 200);
    }
}
