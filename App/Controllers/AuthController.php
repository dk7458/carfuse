<?php

namespace App\Controllers;

use App\Services\Auth\TokenService;
use App\Models\User;

class AuthController
{
    protected TokenService $tokenService;

    public function __construct()
    {
        // Load the encryption configuration into a variable
        $encryptionConfig = require __DIR__ . '/../../config/encryption.php';

        // Instantiate TokenService with the configuration values
        $this->tokenService = new TokenService(
            $encryptionConfig['jwt_secret'],
            $encryptionConfig['jwt_refresh_secret']
        );
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "405 Method Not Allowed";
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
        $pdo = require __DIR__ . '/../../bootstrap.php';
        $stmt = $pdo['pdo']->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "405 Method Not Allowed";
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "405 Method Not Allowed";
            return;
        }

        $refreshToken = $_POST['refresh_token'] ?? null;

        if ($refreshToken) {
            $this->tokenService->revokeToken($refreshToken);
        }

        session_destroy(); // Clear session-based authentication (if used)
        echo json_encode(['message' => 'Logged out successfully']);
    }
}