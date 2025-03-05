<?php

namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use App\Helpers\DatabaseHelper;
use App\Services\AuditService;
use App\Models\RefreshToken;
use App\Models\User;
use Exception;

class TokenService
{
    public const DEBUG_MODE = true;

    private string $jwtSecret;
    private string $jwtRefreshSecret;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private DatabaseHelper $db;
    private AuditService $auditService;
    private array $encryptionConfig;
    private RefreshToken $refreshTokenModel;
    private User $userModel;

    public function __construct(
        string $jwtSecret,
        string $jwtRefreshSecret,
        LoggerInterface $logger,
        ExceptionHandler $exceptionHandler,
        DatabaseHelper $appDb,
        AuditService $auditService,
        array $encryptionConfig,
        RefreshToken $refreshTokenModel,
        User $userModel
    ) {
        $this->jwtSecret = $jwtSecret;
        $this->jwtRefreshSecret = $jwtRefreshSecret;
        if (empty($this->jwtSecret) || empty($this->jwtRefreshSecret)) {
            throw new \RuntimeException('❌ JWT secrets are missing.');
        }
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;
        $this->db = $appDb;
        $this->auditService = $auditService;
        $this->encryptionConfig = $encryptionConfig;
        $this->refreshTokenModel = $refreshTokenModel;
        $this->userModel = $userModel;
        
        if (self::DEBUG_MODE) {
            $this->logger->info("[auth] TokenService initialized.");
        }
    }

    /**
     * Generate a JWT token for a user
     */
    public function generateToken($user): string
    {
        // Extract user ID safely from either array or object
        $userId = is_array($user) ? $user['id'] : $user->id;

        $payload = [
            'iss' => $this->encryptionConfig['issuer'],
            'aud' => $this->encryptionConfig['audience'],
            'iat' => time(),
            'exp' => time() + 3600, // 1 hour
            'sub' => $userId,
            'data' => [
                'id' => $userId,
                'email' => is_array($user) ? $user['email'] : $user->email,
                'role' => is_array($user) ? ($user['role'] ?? 'user') : ($user->role ?? 'user')
            ]
        ];
        
        try {
            $token = JWT::encode($payload, $this->jwtSecret, 'HS256');
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[auth] ✅ Token generated.", ['userId' => $userId]);
            }
            
            // Log JWT creation as a business-level event in audit trail
            $this->auditService->logEvent(
                'auth',
                'jwt_created',
                ['user_id' => $userId],
                $userId,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            return $token;
        } catch (Exception $e) {
            $this->logger->error("[auth] ❌ Token generation failed: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Generate a refresh token for a user
     */
    public function generateRefreshToken($user): string
    {
        // Extract user ID safely from either array or object
        $userId = is_array($user) ? $user['id'] : $user->id;

        $payload = [
            'iss' => $this->encryptionConfig['issuer'],
            'aud' => $this->encryptionConfig['audience'],
            'iat' => time(),
            'exp' => time() + 604800, // 7 days
            'sub' => $userId,
        ];
        
        try {
            $refreshToken = JWT::encode($payload, $this->jwtRefreshSecret, 'HS256');
            
            // Store the refresh token using the RefreshToken model
            $this->refreshTokenModel->store($userId, $refreshToken, 604800);
            
            // Log refresh token creation
            $this->auditService->logEvent(
                'auth',
                'refresh_token_created',
                ['user_id' => $userId],
                $userId,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            return $refreshToken;
        } catch (Exception $e) {
            $this->logger->error("[auth] ❌ Refresh token generation failed: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Verify/decode a JWT token
     */
    public function verifyToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            if ($decoded->exp < time()) {
                // Log token expiration
                $this->auditService->logEvent(
                    'auth',
                    'token_expired',
                    ['sub' => $decoded->sub],
                    $decoded->sub,
                    null,
                    $_SERVER['REMOTE_ADDR'] ?? null
                );
                
                throw new Exception("Token has expired.");
            }
            
            $this->logger->info("✅ Token verified.", ['userId' => $decoded->sub]);
            
            // Log successful verification
            $this->auditService->logEvent(
                'auth',
                'token_verified',
                ['user_id' => $decoded->sub],
                $decoded->sub,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            return (array)$decoded;
        } catch (Exception $e) {
            $this->logger->warning("Token verification failed: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Decode and validate a refresh token
     */
    public function decodeRefreshToken(string $refreshToken)
    {
        try {
            // Check if token has been revoked using RefreshToken model
            if ($this->refreshTokenModel->isRevoked($refreshToken)) {
                // Log revoked token attempt
                $userId = $this->refreshTokenModel->getUserId($refreshToken);
                if ($userId) {
                    $this->auditService->logEvent(
                        'auth',
                        'revoked_token_use_attempt',
                        [],
                        $userId,
                        null,
                        $_SERVER['REMOTE_ADDR'] ?? null
                    );
                }
                
                throw new Exception("Refresh token has been revoked.");
            }
            
            $decoded = JWT::decode($refreshToken, new Key($this->jwtRefreshSecret, 'HS256'));
            
            if ($decoded->exp < time()) {
                // Log expired token attempt
                $this->auditService->logEvent(
                    'auth',
                    'expired_token_use_attempt',
                    ['sub' => $decoded->sub],
                    $decoded->sub,
                    null,
                    $_SERVER['REMOTE_ADDR'] ?? null
                );
                
                throw new Exception("Refresh token has expired.");
            }
            
            $this->logger->debug("Refresh token decoded successfully", ['sub' => $decoded->sub]);
            return $decoded;
        } catch (Exception $e) {
            $this->logger->error("Failed to decode refresh token: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Refresh an access token using a refresh token
     */
    public function refreshToken(string $refreshToken): string
    {
        try {
            $decoded = $this->decodeRefreshToken($refreshToken);
            
            // Generate a new access token
            $user = $this->userModel->find($decoded->sub);
            if (!$user) {
                throw new Exception("User not found for token");
            }
            
            $newToken = $this->generateToken((object)$user);
            
            // Log token refresh as a business event
            $this->auditService->logEvent(
                'auth',
                'jwt_refreshed',
                ['user_id' => $decoded->sub],
                $decoded->sub,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            return $newToken;
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Revoke a refresh token
     */
    public function revokeToken(string $token): void
    {
        try {
            // Get user ID for audit logging before revocation
            $userId = $this->refreshTokenModel->getUserId($token);
            
            // Revoke the token using RefreshToken model
            $this->refreshTokenModel->revoke($token);
            
            // Log token revocation as a business event
            if ($userId) {
                $this->auditService->logEvent(
                    'auth',
                    'token_revoked',
                    [],
                    $userId,
                    null,
                    $_SERVER['REMOTE_ADDR'] ?? null
                );
            }
            
            $this->logger->info("✅ [TokenService] Revoked refresh token.");
        } catch (Exception $e) {
            $this->logger->error("Failed to revoke token: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
        }
    }
    
    /**
     * Remove expired tokens from the database
     */
    public function purgeExpiredTokens(): int
    {
        try {
            // Use RefreshToken model to purge expired tokens
            $count = $this->refreshTokenModel->purgeExpired();
            
            // Log the maintenance action
            $this->auditService->logEvent(
                'system',
                'expired_tokens_purged',
                ['count' => $count],
                null,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            return $count;
        } catch (Exception $e) {
            $this->logger->error("Failed to purge expired tokens: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return 0;
        }
    }
    
    /**
     * Get all active tokens for a user
     */
    public function getActiveTokensForUser(int $userId): array
    {
        try {
            // Use RefreshToken model to get active tokens
            return $this->refreshTokenModel->getActiveForUser($userId);
        } catch (Exception $e) {
            $this->logger->error("Failed to get active tokens: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return [];
        }
    }

    /**
     * Validate token and get user data in a single operation
     */
    public function validateRequest($request): ?array
    {
        $token = $this->extractToken($request);
        if (!$token) {
            return null;
        }
        
        try {
            $decoded = $this->verifyToken($token);
            return $this->userModel->find($decoded['sub']);
        } catch (Exception $e) {
            $this->logger->warning('Token validation failed during request', ['error' => $e->getMessage()]);
            
            // Log invalid token attempt
            $this->auditService->logEvent(
                'auth',
                'invalid_token_use',
                ['error' => $e->getMessage()],
                null,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            return null;
        }
    }
    
    /**
     * Extract token from Authorization header or cookie
     */
    public function extractToken($request): ?string
    {
        // Handle different request formats
        if (is_string($request)) {
            // Assume $request is directly the Authorization header
            $authHeader = $request;
        } elseif (is_array($request) && isset($request['Authorization'])) {
            // Handle array format (e.g. from getHeader)
            $authHeader = $request['Authorization'];
        } elseif (is_object($request) && method_exists($request, 'getHeaderLine')) {
            // Handle PSR-7 request object
            $authHeader = $request->getHeaderLine('Authorization');
        } elseif (is_object($request) && method_exists($request, 'headers')) {
            // Handle Laravel/Symfony style request
            $authHeader = $request->headers->get('Authorization');
        } else {
            $authHeader = null;
        }
        
        // Extract token from Bearer format
        $token = null;
        if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
        }
        
        // If not found in Authorization header, check cookies
        if (!$token && isset($_COOKIE['jwt'])) {
            $token = $_COOKIE['jwt'];
        }
        
        return $token;
    }
}
