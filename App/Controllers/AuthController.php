<?php

namespace App\Controllers;

use App\Services\Auth\TokenService;
use App\Services\Auth\AuthService;
use Exception;
use App\Helpers\DatabaseHelper;
use Psr\Log\NullLogger;
use App\Services\Validator;
use App\Helpers\SecurityHelper;
use App\Helpers\ApiHelper;

class AuthController extends Controller
{
    protected $authService;
    protected $tokenService;

    public function __construct()
    {
        SecurityHelper::startSecureSession();
        $this->authService = new AuthService(new NullLogger());

        $configPath = __DIR__ . '/../../config/encryption.php';
        if (!file_exists($configPath)) {
            throw new Exception("Encryption configuration missing.");
        }
        $encryptionConfig = require $configPath;
        if (!isset($encryptionConfig['jwt_secret'], $encryptionConfig['jwt_refresh_secret'])) {
            throw new Exception("JWT configuration missing in encryption.php.");
        }
        $this->tokenService = new TokenService(
            $encryptionConfig['jwt_secret'],
            $encryptionConfig['jwt_refresh_secret'],
            new NullLogger()
        );
        // Initialize DatabaseHelper (handles DB setup via safeQuery)
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

    public function login($request)
    {
        if (!is_array($request)) {
            ApiHelper::sendJsonResponse('error', 'Invalid JSON input', [], 400);
        }
        
        $data = $request;
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
        ApiHelper::sendJsonResponse('success', 'User logged in', [], 200);
    }

    public function register($request)
    {
        if (!is_array($request)) {
            ApiHelper::sendJsonResponse('error', 'Invalid JSON input', [], 400);
        }
        
        $data = $request;
        $validator = new Validator(new NullLogger());
        $rules = [
            'name'             => 'required',
            'email'            => 'required|email',
            'password'         => 'required',
            'confirm_password' => 'required'
        ];
        if (!$validator->validate($data, $rules)) {
            ApiHelper::sendJsonResponse('error', 'Validation failed', $validator->errors(), 400);
        }
        if ($data['password'] !== $data['confirm_password']) {
            ApiHelper::sendJsonResponse('error', 'Password and confirm password do not match', [], 400);
        }
        
        $registrationData = [
            'name'     => $data['name'],
            'surname'  => $data['surname']  ?? '',
            'email'    => $data['email'],
            'password' => $data['password'],
            'phone'    => $data['phone']    ?? null,
            'address'  => $data['address']  ?? null
        ];

        $result = $this->authService->registerUser($registrationData);
        ApiHelper::sendJsonResponse('success', 'User registered', $result, 201);
    }

    public function resetPasswordRequest($request)
    {
        $data = $_POST;
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

    public function logout($request)
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

    public function userDetails($request)
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

    private function logAuthAttempt($status, $message)
    {
        $logMessage = sprintf("[%s] %s: %s from IP: %s\n", date('Y-m-d H:i:s'), ucfirst($status), $message, $_SERVER['REMOTE_ADDR']);
        file_put_contents(__DIR__ . '/../../logs/auth.log', $logMessage, FILE_APPEND);
    }
}
