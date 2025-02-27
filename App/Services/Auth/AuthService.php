<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Helpers\DatabaseHelper;
use Firebase\JWT\JWT;
use App\Helpers\ExceptionHandler;
use Firebase\JWT\Key;
use Exception;
use Psr\Log\LoggerInterface;
use App\Helpers\ApiHelper;
use App\Services\Validator;

class AuthService
{
    private $pdo;
    private TokenService $tokenService;
    private ExceptionHandler $exceptionHandler;
    private LoggerInterface $authLogger;
    private LoggerInterface $auditLogger;
    private array $encryptionConfig;
    private Validator $validator;

    public function __construct(
        DatabaseHelper $dbHelper,
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler,
        LoggerInterface $authLogger,
        LoggerInterface $auditLogger,
        array $encryptionConfig,
        Validator $validator
    ) {
        $this->pdo = $dbHelper->getPdo();
        $this->tokenService = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
        $this->authLogger = $authLogger;
        $this->auditLogger = $auditLogger;
        $this->encryptionConfig = $encryptionConfig;
        $this->validator = $validator;

        $this->authLogger->info("AuthService initialized with app_database connection");
    }

    public function login(array $data)
    {
        try {
            // Query the users table with correct column names from the existing schema
            $stmt = $this->pdo->prepare("SELECT id, name, surname, email, password_hash, phone, role, address, pesel_or_id, active, created_at FROM users WHERE email = ? AND active = 1");
            $this->authLogger->debug("Executing login query for user email: {$data['email']}");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($data['password'], $user['password_hash'])) {
                $this->authLogger->warning("Authentication failed", ['email' => $data['email']]);
                throw new Exception("Invalid credentials", 401);
            }

            // Cast user array to object for TokenService
            $userObject = (object)$user;
            $this->authLogger->debug("User data converted to object", ['type' => gettype($userObject)]);

            $token = $this->tokenService->generateToken($userObject);
            $refreshToken = $this->tokenService->generateRefreshToken($userObject);

            // Include minimal user information in the result
            return [
                'token'         => $token,
                'refresh_token' => $refreshToken,
                'user_id'       => $user['id'],
                'name'          => $user['name'],
                'email'         => $user['email']
            ];
        } catch (Exception $e) {
            $this->authLogger->error("[auth] ❌ Login error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function register(array $data)
    {
        try {
            $this->authLogger->info("Starting registration process", ['email' => $data['email'] ?? 'unknown']);
            
            $rules = [
                'email'           => 'required|email|unique:users,email',
                'password'        => 'required|min:8',
                'confirm_password'=> 'required|same:password',
                'name'            => 'required|string',
                'surname'         => 'required|string',
                'phone'           => 'string',
                'address'         => 'string',
                'pesel_or_id'     => 'string'
            ];

            // Log sanitized input data (without passwords)
            $logData = $data;
            if (isset($logData['password'])) unset($logData['password']);
            if (isset($logData['confirm_password'])) unset($logData['confirm_password']);
            $this->authLogger->debug("Registration input data", ['data' => $logData]);
            
            // Validate input data
            $this->validator->validate($data, $rules);
            
            // Check passwords match
            if (!isset($data['password']) || !isset($data['confirm_password']) || $data['password'] !== $data['confirm_password']) {
                $this->authLogger->warning("Passwords don't match during registration");
                throw new Exception("Passwords do not match", 400);
            }
            
            // Prepare user data for database insertion with valid columns from the existing schema
            $userData = [
                'name' => $data['name'],
                'surname' => $data['surname'],
                'email' => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'pesel_or_id' => $data['pesel_or_id'] ?? null,
                'role' => $data['role'] ?? 'user',
                'email_notifications' => $data['email_notifications'] ?? 0,
                'sms_notifications' => $data['sms_notifications'] ?? 0,
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Log prepared data (without password_hash)
            $logUserData = $userData;
            unset($logUserData['password_hash']);
            $this->authLogger->debug("Prepared user data for database", ['data' => $logUserData]);
            
            // Insert user into database
            $columns = implode(', ', array_keys($userData));
            $placeholders = implode(', ', array_fill(0, count($userData), '?'));
            
            $this->authLogger->debug("Executing register query with columns: {$columns}");
            $stmt = $this->pdo->prepare("INSERT INTO users ({$columns}) VALUES ({$placeholders})");
            $stmt->execute(array_values($userData));
            $userId = $this->pdo->lastInsertId();
            
            $this->authLogger->info("User registered successfully", ['user_id' => $userId, 'email' => $data['email']]);
            $this->auditLogger->info("New user registration", ['user_id' => $userId, 'email' => $data['email']]);
            
            return ['user_id' => $userId];
        } catch (\InvalidArgumentException $e) {
            $this->authLogger->warning("Validation error during registration", ['error' => $e->getMessage()]);
            throw $e;
        } catch (Exception $e) {
            $this->authLogger->error("Registration error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function refresh(array $data)
    {
        try {
            $decoded = JWT::decode($data['refresh_token'], new Key($this->tokenService->jwtSecret, 'HS256'));
            
            // Query the users table with correct column names
            $stmt = $this->pdo->prepare("SELECT id, name, surname, email, password_hash, phone, role, created_at FROM users WHERE id = ?");
            $this->authLogger->debug("Executing refresh query for user ID: {$decoded->sub}");
            $stmt->execute([$decoded->sub]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->authLogger->warning("Invalid refresh token", ['token_sub' => $decoded->sub]);
                throw new Exception("Invalid refresh token", 400);
            }

            // Cast user array to object for TokenService
            $userObject = (object)$user;
            $this->authLogger->debug("User data converted to object for token refresh", ['type' => gettype($userObject)]);

            $token = $this->tokenService->generateToken($userObject);
            $this->authLogger->info("Token refreshed successfully", ['user_id' => $user['id']]);
            
            return ['token' => $token];
        } catch (Exception $e) {
            $this->authLogger->error("Refresh token error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function logout(array $data)
    {
        $this->auditLogger->info("User logged out");
        return ["message" => "Logged out successfully"];
    }

    public function updateProfile($user, array $data)
    {
        // ...existing code...
    }

    /**
     * Initiates the password reset process
     */
    public function resetPasswordRequest(array $data): array
    {
        try {
            if (!isset($data['email'])) {
                throw new Exception("Email is required", 400);
            }
            
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format", 400);
            }
            
            // Check if user exists with correct column names
            $stmt = $this->pdo->prepare("SELECT id, email FROM users WHERE email = ?");
            $this->authLogger->debug("Executing password reset request query for email: {$data['email']}");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Don't reveal that the email doesn't exist (security best practice)
                $this->authLogger->info("Password reset requested for non-existent email", ['email' => $data['email']]);
                return ["message" => "If your email is registered, you will receive a password reset link"];
            }
            
            // Generate a secure reset token
            $resetToken = bin2hex(random_bytes(32));
            $tokenExpiry = date('Y-m-d H:i:s', time() + 3600); // Token valid for 1 hour
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            
            // Store the token in the password_resets table matching the schema
            $stmt = $this->pdo->prepare("
                INSERT INTO password_resets (email, token, ip_address, expires_at, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$user['email'], $resetToken, $ipAddress, $tokenExpiry]);
            
            // Log the action
            $this->authLogger->info("Password reset token generated", ['user_id' => $user['id']]);
            $this->auditLogger->info("Password reset requested", ['user_id' => $user['id']]);
            
            // In a real application, you would send an email here
            // For this example, we'll just return the token (not secure for production)
            return [
                "message" => "Password reset email sent",
                "debug_token" => $resetToken // Remove this in production!
            ];
        } catch (Exception $e) {
            $this->authLogger->error("Password reset request error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Completes the password reset process
     */
    public function resetPassword(array $data): array
    {
        try {
            // Validate required fields
            if (!isset($data['token']) || !isset($data['password']) || !isset($data['confirm_password'])) {
                throw new Exception("Token, password and confirmation are required", 400);
            }
            
            // Validate password
            if (strlen($data['password']) < 8) {
                throw new Exception("Password must be at least 8 characters", 400);
            }
            
            // Check passwords match
            if ($data['password'] !== $data['confirm_password']) {
                throw new Exception("Passwords do not match", 400);
            }
            
            // Verify token using correct table name and columns
            $stmt = $this->pdo->prepare("
                SELECT * FROM password_resets 
                WHERE token = ? AND expires_at > NOW()
                ORDER BY created_at DESC LIMIT 1
            ");
            $this->authLogger->debug("Verifying reset token: {$data['token']}");
            $stmt->execute([$data['token']]);
            $tokenRecord = $stmt->fetch();
            
            if (!$tokenRecord) {
                throw new Exception("Invalid or expired token", 400);
            }
            
            // Get user with correct column names
            $stmt = $this->pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
            $this->authLogger->debug("Retrieving user for password reset, email: {$tokenRecord['email']}");
            $stmt->execute([$tokenRecord['email']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                throw new Exception("User not found", 404);
            }
            
            // Update the password with correct column name (password_hash)
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $this->pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $this->authLogger->debug("Updating password for user ID: {$user['id']}");
            $stmt->execute([$hashedPassword, $user['id']]);
            
            // Mark token as used by removing it or expiring it (since we don't have a "used" column)
            // We'll expire it by setting expires_at to current time
            $stmt = $this->pdo->prepare("UPDATE password_resets SET expires_at = NOW() WHERE id = ?");
            $stmt->execute([$tokenRecord['id']]);
            
            // Log the action
            $this->authLogger->info("Password reset completed", ['user_id' => $user['id']]);
            $this->auditLogger->info("Password reset completed", ['user_id' => $user['id']]);
            
            return ["message" => "Password has been reset successfully"];
        } catch (Exception $e) {
            $this->authLogger->error("Password reset error: " . $e->getMessage());
            throw $e;
        }
    }
}
?>
