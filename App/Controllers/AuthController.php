<?php

namespace App\Controllers;

use App\Services\Auth\TokenService;
use PDO;
use Exception;

class AuthController
{
    protected TokenService $tokenService;
    protected PDO $pdo;

    public function __construct()
    {
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

        // Instantiate TokenService
        $this->tokenService = new TokenService(
            $encryptionConfig['jwt_secret'],
            $encryptionConfig['jwt_refresh_secret']
        );

        // Load the database connection
        $dbConfig = require __DIR__ . '/../../config/database.php';
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
     * Show the login page (GET /login)
     */
    public function loginView()
    {
        require __DIR__ . '/../Views/auth/login.php';
    }

    /**
     * Show the register page (GET /register)
     */
    public function registerView()
    {
        require __DIR__ . '/../Views/auth/register.php';
    }

    /**
     * Handle user login (POST /login)
     */
    public function login()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
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
            return;
        }

        $token = $this->tokenService->generateToken((object) ['id' => $user['id']]);
        $refreshToken = $this->tokenService->generateRefreshToken((object) ['id' => $user['id']]);

        echo json_encode([
            'access_token' => $token,
            'refresh_token' => $refreshToken
        ]);
    }

    /**
     * Refresh access token (POST /refresh)
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
     * Handle user logout (POST /logout)
     */
    public function logout()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            return;
        }

        $refreshToken = $_POST['refresh_token'] ?? null;

        if ($refreshToken) {
            $this->tokenService->revokeToken($refreshToken);
        }

        session_destroy();
        echo json_encode(['message' => 'Logged out successfully']);
    }
}
