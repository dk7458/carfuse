<?php

namespace App\Controllers;

use App\Services\Auth\TokenService;
use PDO;
use Exception;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';
require_once __DIR__ . '/../../App/Helpers/SecurityHelper.php';

class AuthController
{
    protected TokenService $tokenService;
    protected PDO $pdo;

    public function __construct()
    {
        startSecureSession();
        // Load the encryption configuration
        $configPath = BASE_PATH . '/config/encryption.php';
        if (!file_exists($configPath)) {
            throw new Exception("Encryption configuration missing.");
        }

        $encryptionConfig = require $configPath;

        // Ensure required keys exist
        if (!isset($encryptionConfig['jwt_secret'], $encryptionConfig['jwt_refresh_secret'])) {
            throw new Exception("JWT configuration missing in encryption.php.");
        }

        // Instantiate TokenService
        $this->tokenService = new TokenService(
            $encryptionConfig['jwt_secret'],
            $encryptionConfig['jwt_refresh_secret']
        );

        // Load the database connection
        $dbConfig = require BASE_PATH . '/config/database.php';
        try {
            $this->pdo = new PDO(
                "mysql:host={$dbConfig['app_database']['host']};dbname={$dbConfig['app_database']['database']};charset=utf8mb4",
                $dbConfig['app_database']['username'],
                $dbConfig['app_database']['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (Exception $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
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
    public function login()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            return;
        }

        if (!validateSessionIntegrity()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            $this->logAuthAttempt('failure', 'Unauthorized access attempt');
            return;
        }

        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!$email || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password are required']);
            return;
        }

        // Fetch user from the database
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            $this->logAuthAttempt('failure', 'Invalid credentials');
            return;
        }

        $token = $this->tokenService->generateToken((object) ['id' => $user['id']]);
        $refreshToken = $this->tokenService->generateRefreshToken((object) ['id' => $user['id']]);

        echo json_encode([
            'access_token' => $token,
            'refresh_token' => $refreshToken
        ]);

        $this->refreshToken();
        $this->updateSessionActivity();
        $this->logAuthAttempt('success', 'User logged in');
    }

    /**
     * Refresh access token (POST /auth/refresh)
     */
    public function refresh()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            return;
        }

        $refreshToken = $_POST['refresh_token'] ?? null;

        if (!$refreshToken) {
            http_response_code(400);
            echo json_encode(['error' => 'Refresh token is required']);
            return;
        }

        $newToken = $this->tokenService->refreshAccessToken($refreshToken);

        if ($newToken) {
            echo json_encode(['access_token' => $newToken]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid refresh token']);
        }
    }

    /**
     * Handle user logout (POST /auth/logout)
     */
    public function logout()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            return;
        }

        if (!validateSessionIntegrity()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            $this->logAuthAttempt('failure', 'Unauthorized access attempt');
            return;
        }

        $refreshToken = $_POST['refresh_token'] ?? null;

        if ($refreshToken) {
            $this->tokenService->revokeToken($refreshToken);
        }

        session_destroy();
        echo json_encode(['message' => 'Logged out successfully']);
        $this->logAuthAttempt('success', 'User logged out');
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
