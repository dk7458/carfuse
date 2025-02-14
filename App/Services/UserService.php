<?php

namespace App\Services;

use App\Models\User;
use App\Models\PasswordReset;
use App\Models\AuditLog; // added for audit logging
use Psr\Log\LoggerInterface;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

/**
 * UserService
 * 
 * Handles user-related operations such as registration, authentication, profile management,
 * role management, and password resets.
 */
class UserService
{
    private LoggerInterface $logger;
    private string $jwtSecret;

    public function __construct(LoggerInterface $logger, string $jwtSecret)
    {
        $this->logger = $logger;
        $this->jwtSecret = $jwtSecret;
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
            // Removed manual password hashing; rely on the User model mutator for "password"
            // $data['password_hash'] = Hash::make($data['password']);
            // unset($data['password']);
            $user = User::create($data);

            $this->logAction($user->id, 'user_created', ['email' => $data['email']]);
            return ['status' => 'success', 'message' => 'User created successfully'];
        } catch (Exception $e) {
            $this->logger->error('User creation failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'User creation failed'];
        }
    }

    /**
     * ✅ Update an existing user
     */
    public function updateUser(int $id, array $data): array
    {
        try {
            $user = User::findOrFail($id);

            // Removed manual password hashing; assume mutator will handle updated "password" if supplied
            // if (!empty($data['password'])) {
            //     $data['password_hash'] = Hash::make($data['password']);
            //     unset($data['password']);
            // }
            $user->update($data);

            $this->logAction($id, 'user_updated', $data);
            return ['status' => 'success', 'message' => 'User updated successfully'];
        } catch (ModelNotFoundException $e) {
            return ['status' => 'error', 'message' => 'User not found'];
        } catch (Exception $e) {
            $this->logger->error('User update failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'User update failed'];
        }
    }

    /**
     * ✅ Authenticate a user
     */
    public function authenticate(string $email, string $password): ?string
    {
        try {
            $user = User::where('email', $email)->first();

            if (!$user || !Hash::check($password, $user->password_hash)) {
                $this->logAction(null, 'authentication_failed', ['email' => $email]);
                return null;
            }

            $this->logAction($user->id, 'authentication_successful');
            return $this->generateJWT($user);
        } catch (Exception $e) {
            $this->logger->error('Authentication failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * ✅ Generate a JWT token
     */
    private function generateJWT(User $user): string
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
            $user = User::where('email', $email)->first();

            if (!$user) {
                return false;
            }

            $token = bin2hex(random_bytes(32));
            $expiresAt = now()->addHour();

            PasswordReset::create([
                'email' => $email,
                'token' => $token,
                'expires_at' => $expiresAt
            ]);

            // ✅ Log the password reset request
            $this->logAction($user->id, 'password_reset_requested', ['email' => $email]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Password reset request failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * ✅ Log an action for auditing
     */
    private function logAction(?int $userId, string $action, array $details = []): void
    {
        // Replace direct logger entry with creation of an audit log record
        AuditLog::create([
            'user_id' => $userId,
            'action'  => $action,
            'details' => json_encode($details)
        ]);
    }
}
