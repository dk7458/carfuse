<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use App\Helpers\DatabaseHelper; // added for database operations

class UserService
{
    private LoggerInterface $logger;
    private string $jwtSecret;
    private $db; // DatabaseHelper instance

    // Modified constructor to use DatabaseHelper exclusively
    public function __construct(LoggerInterface $logger, string $jwtSecret)
    {
        $this->logger = $logger;
        $this->jwtSecret = $jwtSecret;
        $this->db = DatabaseHelper::getInstance();
        $this->logger->info("[UserService] Initialized.");
    }

    /**
     * ✅ Create a new user
     */
    public function createUser(array $data): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
        ];

        $validator = new Validator();
        if (!$validator->validate($data, $rules)) {
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()];
        }

        try {
            // Replace Eloquent create with DatabaseHelper insert
            $userId = $this->db->table('users')->insertGetId($data);
            $this->logger->info("[UserService] User created successfully with id: {$userId}");
            // ✅ Log the creation using returned user id
            $this->logAction($userId, 'user_created', ['email' => $data['email']]);
            return ['status' => 'success', 'message' => 'User created successfully'];
        } catch (Exception $e) {
            $this->logger->error("[UserService] User creation failed: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'User creation failed'];
        }
    }

    /**
     * ✅ Update an existing user
     */
    public function updateUser(int $id, array $data): array
    {
        try {
            // Replace User::findOrFail with DatabaseHelper query
            $user = $this->db->table('users')->where('id', $id)->first();
            if (!$user) {
                $this->logger->error("[UserService] User not found with id: {$id}");
                throw new ModelNotFoundException();
            }
            $this->db->table('users')->where('id', $id)->update($data);
            $this->logger->info("[UserService] User updated successfully with id: {$id}");
            $this->logAction($id, 'user_updated', $data);
            return ['status' => 'success', 'message' => 'User updated successfully'];
        } catch (ModelNotFoundException $e) {
            return ['status' => 'error', 'message' => 'User not found'];
        } catch (Exception $e) {
            $this->logger->error("[UserService] User update failed: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'User update failed'];
        }
    }

    /**
     * ✅ Authenticate a user
     */
    public function authenticate(string $email, string $password): ?string
    {
        try {
            // Replace direct query with DatabaseHelper table method
            $user = $this->db->table('users')->where('email', $email)->first();

            if (!$user || !Hash::check($password, $user->password_hash)) {
                $this->logger->error("[UserService] Authentication failed for email: {$email}");
                $this->logAction(null, 'authentication_failed', ['email' => $email]);
                return null;
            }
            $this->logger->info("[UserService] Authentication successful for user id: {$user->id}");
            $this->logAction($user->id, 'authentication_successful');
            return $this->generateJWT($user);
        } catch (Exception $e) {
            $this->logger->error("[UserService] Authentication error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ Generate a JWT token
     */
    private function generateJWT($user): string
    {
        $payload = [
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    /**
     * ✅ Request password reset
     */
    public function requestPasswordReset(string $email): bool
    {
        try {
            $user = $this->db->table('users')->where('email', $email)->first();
            if (!$user) {
                $this->logger->error("[UserService] Password reset request failed: user not found for {$email}");
                return false;
            }
            $token = bin2hex(random_bytes(32));
            $expiresAt = now()->addHour();
            // Replace PasswordReset::create with DatabaseHelper insert
            $this->db->table('password_resets')->insert([
                'email' => $email,
                'token' => $token,
                'expires_at' => $expiresAt
            ]);
            $this->logger->info("[UserService] Password reset requested for user id: {$user->id}");
            $this->logAction($user->id, 'password_reset_requested', ['email' => $email]);
            return true;
        } catch (Exception $e) {
            $this->logger->error("[UserService] Password reset request error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ Log an action for auditing
     */
    private function logAction(?int $userId, string $action, array $details = []): void
    {
        try {
            // Replace AuditLog::create with DatabaseHelper insert for audit logging
            $this->db->table('audit_logs')->insert([
                'user_id' => $userId,
                'action'  => $action,
                'details' => json_encode($details)
            ]);
            $this->logger->info("[UserService] Logged action '{$action}' for user id: " . ($userId ?? 'N/A'));
        } catch (Exception $e) {
            $this->logger->error("[UserService] Failed to log action '{$action}': " . $e->getMessage());
        }
    }
}
