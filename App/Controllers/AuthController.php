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
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['email']) || !isset($data['password'])) {
                throw new Exception("Email and password are required.");
            }

            $result = $this->authService->login($data['email'], $data['password']);

            setcookie('jwt', $result['token'], [
                'expires' => time() + 3600,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);

            setcookie('refresh_token', $result['refresh_token'], [
                'expires' => time() + 86400,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);

            ApiHelper::sendJsonResponse('success', 'Login successful', $result);
        } catch (Exception $e) {
            ApiHelper::sendJsonResponse('error', $e->getMessage(), [], 401);
        }
    }

    public function register($request = null)
    {

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
                throw new Exception("Email, password, and name are required.");
            }

            $result = $this->authService->registerUser($data);
            ApiHelper::sendJsonResponse('success', 'Registration successful', $result);
        } catch (Exception $e) {
            ApiHelper::sendJsonResponse('error', $e->getMessage(), [], 400);
        }
    }

    public function resetPasswordRequest($request = null)
    {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !is_array($data)) {
            ApiHelper::sendJsonResponse('error', 'Invalid JSON input', ['errors' => (object)[]], 400);
        }
        $email = $data['email'] ?? '';
        $result = $this->authService->resetPasswordRequest($email);
        ApiHelper::sendJsonResponse('success', 'Password reset request processed', $result, 200);
    }

    public function refresh()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['refresh_token'])) {
                throw new Exception("Refresh token is required.");
            }

            $result = $this->authService->refreshToken($data['refresh_token']);

            setcookie('jwt', $result['token'], [
                'expires' => time() + 3600,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);

            ApiHelper::sendJsonResponse('success', 'Token refreshed', $result);
        } catch (Exception $e) {
            ApiHelper::sendJsonResponse('error', $e->getMessage(), [], 401);
        }
    }

    public function logout($request = null)
    {
        try {
            $this->authService->logout();

            setcookie('jwt', '', time() - 3600, '/');
            setcookie('refresh_token', '', time() - 3600, '/');

            ApiHelper::sendJsonResponse('success', 'Logout successful');
        } catch (Exception $e) {
            ApiHelper::sendJsonResponse('error', $e->getMessage(), [], 400);
        }
    }

    public function userDetails($request = null)
    {
        $token = $_COOKIE['jwt'] ?? '';
        if (!$this->authService->validateToken($token)) {
            ApiHelper::sendJsonResponse('error', 'Invalid token', ['errors' => (object)[]], 400);
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
