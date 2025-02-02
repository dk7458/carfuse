<?php

namespace App\Services;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * UserService
 * 
 * Handles user-related operations such as creation, updates, authentication, role management,
 * password management, and logging.
 */
class UserService
{
    private PDO $db;
    private LoggerInterface $logger;
    private string $jwtSecret;

    public function __construct(PDO $db, LoggerInterface $logger, string $jwtSecret)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->jwtSecret = $jwtSecret;
    }

    /**
     * Create a new user
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
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password, phone, address, role, created_at)
                VALUES (:name, :email, :password, :phone, :address, 'user', NOW())
            ");

            $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                'phone' => $data['phone'],
                'address' => $data['address']
            ]);

            $this->logAction(null, 'user_created', ['email' => $data['email']]);
            return ['status' => 'success', 'message' => 'User created successfully'];
        } catch (PDOException $e) {
            $this->logger->error('User creation failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'User creation failed'];
        }
    }

    /**
     * Update an existing user's information
     */
    public function updateUser(int $id, array $data): array
    {
        $allowedFields = ['name', 'phone', 'address'];
        $updates = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updates)) {
            return ['status' => 'error', 'message' => 'No valid fields to update'];
        }

        try {
            $sql = "UPDATE users SET " . implode(', ', array_map(fn($k) => "$k = :$k", array_keys($updates))) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([...$updates, 'id' => $id]);

            $this->logAction($id, 'user_updated', $updates);
            return ['status' => 'success', 'message' => 'User updated successfully'];
        } catch (PDOException $e) {
            $this->logger->error('User update failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'User update failed'];
        }
    }

    /**
     * Delete a user (soft delete)
     */
    public function deleteUser(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET deleted_at = NOW() WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $this->logAction($id, 'user_deleted');
            return true;
        } catch (PDOException $e) {
            $this->logger->error('User deletion failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Authenticate a user
     */
    public function authenticate(string $email, string $password): ?string
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email AND deleted_at IS NULL");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->logAction(null, 'authentication_failed', ['email' => $email]);
            return null;
        }

        $this->logAction($user['id'], 'authentication_successful');
        return $this->generateJWT($user);
    }

    /**
     * Change a user's password
     */
    public function changePassword(int $id, string $newPassword): bool
    {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->execute(['password' => $hashedPassword, 'id' => $id]);

            $this->logAction($id, 'password_changed');
            return true;
        } catch (PDOException $e) {
            $this->logger->error('Password change failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Assign a role to a user
     */
    public function assignRole(int $id, string $role): bool
    {
        $validRoles = ['user', 'admin', 'super_admin'];
        if (!in_array($role, $validRoles)) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("UPDATE users SET role = :role WHERE id = :id");
            $stmt->execute(['role' => $role, 'id' => $id]);

            $this->logAction($id, 'role_assigned', ['role' => $role]);
            return true;
        } catch (PDOException $e) {
            $this->logger->error('Role assignment failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Generate a JWT token for a user
     */
    private function generateJWT(array $user): string
    {
        $payload = [
            'sub' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + 3600, // Token expires in 1 hour
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    /**
     * Log an action for auditing purposes
     */
    private function logAction(?int $userId, string $action, array $details = []): void
    {
        $this->logger->info($action, ['user_id' => $userId, 'details' => $details]);
    }
}
