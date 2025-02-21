<?php

namespace App\Controllers;

use App\Services\Auth\TokenService;
use App\Services\Auth\AuthService;
use Exception;
use App\Helpers\DatabaseHelper;
use App\Services\Validator;
use App\Helpers\SecurityHelper;
use App\Helpers\ApiHelper;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler; // New dependency

class AuthController extends Controller
{
    private AuthService $authService;
    private Validator $validator;
    private TokenService $tokenService;
    private ExceptionHandler $exceptionHandler;
    private LoggerInterface $authLogger;
    private LoggerInterface $auditLogger;

    public function __construct(
        AuthService $authService,
        Validator $validator,
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler,
        LoggerInterface $authLogger,
        LoggerInterface $auditLogger
    ) {
        $this->authService = $authService;
        $this->validator = $validator;
        $this->tokenService = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
        $this->authLogger = $authLogger;
        $this->auditLogger = $auditLogger;

        DatabaseHelper::getInstance();
    }

    public function loginView()
    {
        view('auth/login');
    }

    public function registerView()
    {
        view('auth/register');
    }

    public function login($request = null)
    {
        // ...existing code...
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !is_array($data)) {
            return ApiHelper::sendJsonResponse('error', 'Invalid JSON input', [], 400);
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
        // Improved logging with context
        $this->authLogger->info("User logged in", ['email' => $email]);
        return ApiHelper::sendJsonResponse('success', 'User logged in', [], 200);
    }

    public function register($request = null)
    {
        // ...existing code...
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !is_array($data)) {
            return ApiHelper::sendJsonResponse('error', 'Invalid JSON input', [], 400);
        }
        
        $rules = [
            'name'             => 'required',
            'email'            => 'required|email',
            'password'         => 'required',
            'confirm_password' => 'required'
        ];
        if (!$this->validator->validate($data, $rules)) {
            return ApiHelper::sendJsonResponse('error', 'Validation failed', $this->validator->errors(), 400);
        }
        if ($data['password'] !== $data['confirm_password']) {
            return ApiHelper::sendJsonResponse('error', 'Password and confirm password do not match', [], 400);
        }
        
        $registrationData = [
            'name'     => $data['name'],
            'surname'  => $data['surname']  ?? '',
            'email'    => $data['email'],
            'password' => $data['password'],
            'phone'    => $data['phone']    ?? null,
            'address'  => $data['address']  ?? null
        ];

        try {
            $result = $this->authService->registerUser($registrationData);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            // Additional audit logging if needed
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
            ApiHelper::sendJsonResponse('error', 'Invalid JSON input', [], 400);
        }
        $email = $data['email'] ?? '';
        $result = $this->authService->resetPasswordRequest($email);
        ApiHelper::sendJsonResponse('success', 'Password reset request processed', $result, 200);
    }

    public function refresh()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ApiHelper::sendJsonResponse('error', 'Method Not Allowed', [], 405);
        }
        $refreshToken = $_COOKIE['refresh_token'] ?? null;
        if (!$refreshToken) {
            ApiHelper::sendJsonResponse('error', 'Refresh token is required', [], 400);
        }
        $newToken = $this->tokenService->refreshToken($refreshToken);
        if ($newToken) {
            setcookie("jwt", $newToken, [
                "expires" => time() + 3600,
                "path" => "/",
                "secure" => true,
                "httponly" => true,
                "samesite" => "Strict"
            ]);
            ApiHelper::sendJsonResponse('success', 'Token refreshed', [], 200);
        } else {
            ApiHelper::sendJsonResponse('error', 'Invalid refresh token', [], 401);
        }
    }

    public function logout($request = null)
    {
        $this->authService->logout();
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
        ApiHelper::sendJsonResponse('success', 'User logged out', [], 200);
    }

    public function userDetails($request = null)
    {
        $token = $_COOKIE['jwt'] ?? '';
        if (!$this->authService->validateToken($token)) {
            ApiHelper::sendJsonResponse('error', 'Invalid token', [], 400);
        }
        $userData = $this->authService->getUserFromToken($token);
        ApiHelper::sendJsonResponse('success', 'User details fetched', $userData, 200);
    }

    private function refreshToken()
    {
        // ...existing code...
    }

    private function updateSessionActivity()
    {
        $_SESSION['last_activity'] = time();
    }

    // Updated logging method with context details
    private function logAuthAttempt($status, $message)
    {
        $context = ['ip' => $_SERVER['REMOTE_ADDR'], 'time' => date('Y-m-d H:i:s')];
        $logMessage = sprintf("%s: %s", ucfirst($status), $message);
        $this->authLogger->info($logMessage, $context);
    }
}
