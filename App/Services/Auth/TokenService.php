<?php

namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use App\Helpers\DatabaseHelper;
use App\Services\AuditService;

class TokenService
{
    public const DEBUG_MODE = true;

    private string $jwtSecret;
    private string $jwtRefreshSecret;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private DatabaseHelper $db;
    private DatabaseHelper $secureDb;
    private AuditService $auditService;

    public function __construct(
        string $jwtSecret,
        string $jwtRefreshSecret,
        LoggerInterface $logger,
        ExceptionHandler $exceptionHandler,
        DatabaseHelper $appDb,
        DatabaseHelper $secureDb,
        AuditService $auditService
    ) {
        $this->jwtSecret = $jwtSecret;
        $this->jwtRefreshSecret = $jwtRefreshSecret;
        if (empty($this->jwtSecret) || empty($this->jwtRefreshSecret)) {
            throw new \RuntimeException('❌ JWT secrets are missing.');
        }
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;
        $this->db = $appDb;
        $this->secureDb = $secureDb;
        $this->auditService = $auditService;
        
        if (self::DEBUG_MODE) {
            $this->logger->info("[auth] TokenService initialized.");
        }
    }

    public function generateToken($user): string
    {
        // Extract user ID safely from either array or object
        $userId = is_array($user) ? $user['id'] : $user->id;

        $payload = [
            'iss' => "your-issuer",
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + 3600
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
        } catch (\Exception $e) {
            $this->logger->error("[auth] ❌ Token generation failed: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function verifyToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            if ($decoded->exp < time()) {
                throw new \Exception("Token has expired.");
            }
            $this->logger->info("✅ Token verified.", ['userId' => $decoded->sub]);
            return (array)$decoded;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function generateRefreshToken($user): string
    {
        // Extract user ID safely from either array or object
        $userId = is_array($user) ? $user['id'] : $user->id;

        $payload = [
            'iss' => "your-issuer",
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + 604800
        ];
        try {
            $refreshToken = JWT::encode($payload, $this->jwtRefreshSecret, 'HS256');
            
            // Store the refresh token in database using DatabaseHelper
            $this->storeRefreshToken($userId, $refreshToken);
            
            return $refreshToken;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }
    
    /**
     * Store refresh token in database
     */
    private function storeRefreshToken(int $userId, string $refreshToken): void
    {
        try {
            // Store the token in the refresh_tokens table using secure db helper
            DatabaseHelper::insert('refresh_tokens', [
                'user_id' => $userId,
                'token' => hash('sha256', $refreshToken), // Store hashed token for security
                'expires_at' => date('Y-m-d H:i:s', time() + 604800),
                'created_at' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ], true); // Set useSecureDb to true
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[auth] Refresh token stored in secure database", ['user_id' => $userId]);
            }
        } catch (\Exception $e) {
            $this->logger->error("[auth] Failed to store refresh token: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            // Continue without failing - JWT will still work even if storage fails
        }
    }

    /**
     * Decode and validate a refresh token
     *
     * @param string $refreshToken The refresh token to decode
     * @return object The decoded token payload
     * @throws \Exception If token is invalid or expired
     */
    public function decodeRefreshToken(string $refreshToken)
    {
        try {
            // Check if token has been revoked
            if ($this->isTokenRevoked($refreshToken)) {
                throw new \Exception("Refresh token has been revoked.");
            }
            
            $decoded = JWT::decode($refreshToken, new Key($this->jwtRefreshSecret, 'HS256'));
            
            if ($decoded->exp < time()) {
                throw new \Exception("Refresh token has expired.");
            }
            
            $this->logger->debug("Refresh token decoded successfully", ['sub' => $decoded->sub]);
            return $decoded;
        } catch (\Exception $e) {
            $this->logger->error("Failed to decode refresh token: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }
    
    /**
     * Check if a token has been revoked
     */
    private function isTokenRevoked(string $refreshToken): bool
    {
        try {
            // Check cache first for performance
            if (apcu_exists("revoked_refresh_token_$refreshToken")) {
                return true;
            }
            
            // If not in cache, check secure database
            $hashedToken = hash('sha256', $refreshToken);
            $query = "SELECT 1 FROM refresh_tokens WHERE token = :token AND revoked = 1 LIMIT 1";
            $revoked = DatabaseHelper::select($query, [':token' => $hashedToken], true); // Set useSecureDb to true
                
            // If revoked in database, store in cache for next time
            if ($revoked) {
                apcu_store("revoked_refresh_token_$refreshToken", true, 604800);
            }
            
            return !empty($revoked);
        } catch (\Exception $e) {
            $this->logger->warning("Error checking if token is revoked: " . $e->getMessage());
            // Default to not revoked if there's an error checking, but log it
            return false;
        }
    }

    public function refreshToken(string $refreshToken): string
    {
        try {
            $decoded = $this->decodeRefreshToken($refreshToken);
            
            // Generate a new access token
            $newToken = $this->generateToken((object)['id' => $decoded->sub]);
            
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
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function revokeToken(string $token): void
    {
        try {
            // Store in cache for quick lookups
            apcu_store("revoked_refresh_token_$token", true, 604800);
            
            // Store in secure database for persistence
            $hashedToken = hash('sha256', $token);
            
            // Update the token status in secure database using db helper
            DatabaseHelper::update('refresh_tokens', [
                'revoked' => 1,
                'revoked_at' => date('Y-m-d H:i:s')
            ], ['token' => $hashedToken], true); // Set useSecureDb to true
                
            // Try to get the user ID for audit logging
            $query = "SELECT user_id FROM refresh_tokens WHERE token = :token LIMIT 1";
            $tokenData = DatabaseHelper::select($query, [':token' => $hashedToken], true); // Set useSecureDb to true
            
            $userId = $tokenData[0]['user_id'] ?? null;
            
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
        } catch (\Exception $e) {
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
            $query = "DELETE FROM refresh_tokens WHERE expires_at < :now";
            $count = DatabaseHelper::delete('refresh_tokens', ['expires_at < ' => date('Y-m-d H:i:s')], false, true); // Set useSecureDb to true
                
            $this->logger->info("[TokenService] Purged {$count} expired tokens");
            return $count;
        } catch (\Exception $e) {
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
            $query = "SELECT * FROM refresh_tokens WHERE user_id = :user_id AND revoked = 0 AND expires_at > :now";
            $tokens = DatabaseHelper::select($query, [
                ':user_id' => $userId,
                ':now' => date('Y-m-d H:i:s')
            ], true); // Set useSecureDb to true
                
            return $tokens ?: [];
        } catch (\Exception $e) {
            $this->logger->error("Failed to get active tokens: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return [];
        }
    }

    /**
     * Validate a token and return user data if valid.
     * This replaces TokenValidator::validateToken
     *
     * @param string|null $tokenHeader The Authorization header value
     * @return array|null User data if valid, null if invalid
     */
    public function validateTokenFromHeader(?string $tokenHeader): ?array
    {
        try {
            if (!$tokenHeader) {
                return null;
            }

            // Extract the token from the Authorization header
            $token = preg_replace('/^Bearer\s+/', '', $tokenHeader);
            if (empty($token)) {
                return null;
            }

            // Decode and verify the token
            $decoded = $this->verifyToken($token);
            
            // Get the user ID from the token
            $userId = $decoded['sub'] ?? null;
            if (!$userId) {
                $this->logger->warning('Token missing user ID claim', ['token' => substr($token, 0, 10) . '...']);
                return null;
            }

            // Fetch user data from the database
            $user = $this->getUserById($userId);
            if (!$user) {
                $this->logger->warning('User from token not found in database', ['user_id' => $userId]);
                return null;
            }

            if (self::DEBUG_MODE) {
                $this->logger->info('Token validation successful', ['user_id' => $userId]);
            }
            
            // Log token validation in audit trail
            $this->auditService->logEvent(
                'auth',
                'token_validated',
                ['user_id' => $userId],
                $userId,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            return $user;
        } catch (\Exception $e) {
            $this->logger->warning('Token validation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Extract token from Authorization header or cookie
     * 
     * @param mixed $request The request object or authorization header
     * @return string|null The token or null if not found
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
    
    /**
     * Get user data by ID from the database
     * 
     * @param int $userId The user ID
     * @return array|null User data or null if not found
     */
    private function getUserById(int $userId): ?array
    {
        try {
            $sql = "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1";
            $users = DatabaseHelper::select($sql, [$userId]);
            
            if (empty($users)) {
                return null;
            }
            
            // Remove sensitive data
            unset($users[0]['password']);
            
            return $users[0];
        } catch (\Exception $e) {
            $this->logger->error("Error fetching user data: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }
    
    /**
     * Validate token and get user data in a single operation
     * 
     * @param mixed $request The request object or authorization header
     * @return array|null User data if token valid, null otherwise
     */
    public function validateRequest($request): ?array
    {
        $token = $this->extractToken($request);
        if (!$token) {
            return null;
        }
        
        try {
            $decoded = $this->verifyToken($token);
            return $this->getUserById($decoded['sub']);
        } catch (\Exception $e) {
            $this->logger->warning('Token validation failed during request', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
