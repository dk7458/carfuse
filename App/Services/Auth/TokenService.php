<?php

namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use App\Helpers\DatabaseHelper;

class TokenService
{
    private string $jwtSecret;
    private int $jwtTtl;
    private int $refreshTokenTtl;
    private LoggerInterface $authLogger;
    private ExceptionHandler $exceptionHandler;
    private $db;

    public function __construct(
        string $jwtSecret,
        int $jwtTtl = 3600,
        int $refreshTokenTtl = 604800,
        LoggerInterface $authLogger,
        ExceptionHandler $exceptionHandler,
        DatabaseHelper $dbHelper
    ) {
        $this->jwtSecret = $jwtSecret;
        $this->jwtTtl = $jwtTtl;
        $this->refreshTokenTtl = $refreshTokenTtl;
        $this->authLogger = $authLogger;
        $this->exceptionHandler = $exceptionHandler;
        $this->db = $dbHelper->getAppDatabaseConnection();
    }

    public function generateToken(array $userData): string
    {
        try {
            $payload = [
                'iss' => 'carfuse_api',  // issuer
                'iat' => time(),         // issued at
                'exp' => time() + $this->jwtTtl, // expiration
                'sub' => $userData['sub'] ?? null,  // subject (user ID)
                'email' => $userData['email'] ?? null,
                'role' => $userData['role'] ?? 'user'
            ];
            
            return JWT::encode($payload, $this->jwtSecret, 'HS256');
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw new Exception("Token generation failed");
        }
    }

    public function generateRefreshToken(array $userData): string
    {
        try {
            $token = bin2hex(random_bytes(32));
            
            // Store refresh token in app database for validation
            $this->db->table('refresh_tokens')->insert([
                'user_id' => $userData['sub'],
                'token' => password_hash($token, PASSWORD_BCRYPT),
                'expires_at' => date('Y-m-d H:i:s', time() + $this->refreshTokenTtl)
            ]);
            
            return $token;
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw new Exception("Refresh token generation failed");
        }
    }

    public function refreshToken(string $refreshToken): ?string
    {
        try {
            // Find valid refresh token in app database
            $storedToken = $this->db->table('refresh_tokens')
                ->where('expires_at', '>', date('Y-m-d H:i:s'))
                ->get();
                
            // Find token that matches the provided one (has to loop because we need password_verify)
            $userId = null;
            foreach ($storedToken as $token) {
                if (password_verify($refreshToken, $token->token)) {
                    $userId = $token->user_id;
                    break;
                }
            }
            
            if (!$userId) {
                return null;
            }
            
            // Get user details
            $user = $this->db->table('users')->find($userId);
            if (!$user) {
                return null;
            }
            
            // Generate new token
            return $this->generateToken([
                'sub' => $userId,
                'email' => $user->email,
                'role' => $user->role ?? 'user'
            ]);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }

    public function validateToken(string $token): bool
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            return !empty($decoded) && isset($decoded->sub);
        } catch (Exception $e) {
            $this->authLogger->warning("Token validation failed: {$e->getMessage()}");
            return false;
        }
    }

    public function decodeToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            return (array)$decoded;
        } catch (Exception $e) {
            $this->authLogger->warning("Token decode failed: {$e->getMessage()}");
            return null;
        }
    }
}
