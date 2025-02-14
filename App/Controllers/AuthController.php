<?php

namespace App\Controllers;

use App\Services\Auth\TokenService;
use App\Services\Auth\AuthService;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;
use App\Helpers\DatabaseHelper;
use Psr\Log\NullLogger; // added for logger

class AuthController extends Controller
{
    protected $authService;
    protected $tokenService;

    public function __construct()
    {
        startSecureSession();
        // Pass NullLogger to AuthService constructor
        $this->authService = new AuthService(new NullLogger());

        // Load the encryption configuration
        $configPath = __DIR__ . '/../../config/encryption.php';
        if (!file_exists($configPath)) {
            throw new Exception("Encryption configuration missing.");
        }

        $encryptionConfig = require $configPath;

        // Ensure required keys exist
        if (!isset($encryptionConfig['jwt_secret'], $encryptionConfig['jwt_refresh_secret'])) {
            throw new Exception("JWT configuration missing in encryption.php.");
        }

        // Instantiate TokenService with logger argument
        $this->tokenService = new TokenService(
            $encryptionConfig['jwt_secret'],
            $encryptionConfig['jwt_refresh_secret'],
            new NullLogger() // added logger argument
        );

        // Initialize Eloquent ORM
        DatabaseHelper::getInstance();
    }

    /**
     * Show the login page (GET /auth/login)
     */
    public function loginView()
    {
        view('auth/login');
    }

    /**
     * Show the register page (GET /auth/register)
     */
    public function registerView()
    {
        view('auth/register');
    }

    /**
     * Handle user login (POST /auth/login)
     */
    public function login($request)
    {
        header('Content-Type: application/json');
        try {
            // Use array as-is if provided, otherwise call getParsedBody()
            $data = is_array($request) ? $request : $request->getParsedBody(); // changed code

            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            // Delegate login logic to AuthService
            $result = $this->authService->login($email, $password);

            // Securely store JWT and refresh token in HTTP-only cookies
            setcookie("jwt", $result['token'], [
                "expires" => time() + 3600,
                "path" => "/",
                "secure" => true,
                "httponly" => true,
                "samesite" => "Strict"
            ]);
            setcookie("refresh_token", $result['refresh_token'], [
                "expires" => time() + 604800,
                "path" => "/",
                "secure" => true,
                "httponly" => true,
                "samesite" => "Strict"
            ]);

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'User logged in',
                'data' => [] // JWT not exposed in response
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * Handle user registration (POST /auth/register)
     */
    public function register($request)
    {
        header('Content-Type: application/json');
        try {
            // Use array as-is if provided, otherwise call getParsedBody()
            $data = is_array($request) ? $request : $request->getParsedBody(); // changed code

            $name = $data['name'] ?? '';
            $surname = $data['surname'] ?? '';
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';
            $phone = $data['phone'] ?? null;
            $address = $data['address'] ?? null;

            // Delegate registration logic to AuthService
            $result = $this->authService->registerUser([
                'name' => $name,
                'surname' => $surname,
                'email' => $email,
                'password' => $password,
                'phone' => $phone,
                'address' => $address
            ]);

            http_response_code(201);
            echo json_encode([
                'status' => 'success',
                'message' => 'User registered',
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * Handle password reset request (POST /auth/reset-password-request)
     */
    public function resetPasswordRequest($request)
    {
        header('Content-Type: application/json');
        try {
            // Use array as-is if provided, otherwise call getParsedBody()
            $data = is_array($request) ? $request : $request->getParsedBody(); // changed code

            $email = $data['email'] ?? '';

            // Delegate password reset request logic to AuthService
            $result = $this->authService->resetPasswordRequest($email);

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Password reset request processed',
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * Refresh access token (POST /auth/refresh)
     */
    public function refresh()
    {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method Not Allowed');
            }

            $refreshToken = $_COOKIE['refresh_token'] ?? null;

            if (!$refreshToken) {
                throw new Exception('Refresh token is required');
            }

            // Delegate token refresh logic to AuthService
            $newToken = $this->tokenService->refreshToken($refreshToken);

            if ($newToken) {
                // Set new access token in HTTP-only secure cookie
                setcookie("jwt", $newToken, [
                    "expires" => time() + 3600,
                    "path" => "/",
                    "secure" => true,
                    "httponly" => true,
                    "samesite" => "Strict"
                ]);

                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Token refreshed',
                    'data' => []
                ]);
            } else {
                throw new Exception('Invalid refresh token');
            }
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * Handle user logout (POST /auth/logout)
     */
    public function logout($request)
    {
        header('Content-Type: application/json');
        try {
            // Delegate logout logic to AuthService
            $this->authService->logout();

            // Clear the JWT and refresh token cookies
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

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'User logged out',
                'data' => []
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * Get user details endpoint, ensuring token is valid
     */
    public function userDetails($request)
    {
        header('Content-Type: application/json');
        try {
            $token = $_COOKIE['jwt'] ?? '';

            // Validate token before processing
            if (!$this->authService->validateToken($token)) {
                throw new Exception('Invalid token.');
            }

            $userData = $this->authService->getUserFromToken($token);
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'User details fetched',
                'data' => $userData
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    private function refreshToken()
    {
        // Logic to refresh JWT token
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
