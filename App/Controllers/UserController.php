<?php

namespace App\Controllers;

use App\Services\NotificationService;
use App\Services\Validator;
use App\Services\RateLimiter;
use AuditManager\Services\AuditService;
use PDO;
use Psr\Log\LoggerInterface;
use Firebase\JWT\JWT;
use App\Services\UserService;

require_once BASE_PATH . '/App/Helpers/ViewHelper.php';

/**
 * User Management Controller
 *
 * Handles user registration, authentication, profile management, and password resets.
 * Implements enhanced security and scalability features.
 */
class UserController
{
    private PDO $appDb;
    private PDO $secureDb;
    private LoggerInterface $logger;
    private array $config;
    private Validator $validator;
    private RateLimiter $rateLimiter;
    private AuditService $auditService;
    private NotificationService $notificationService;
    protected $userService;

    public function __construct(
        PDO $appDb,
        PDO $secureDb,
        LoggerInterface $logger,
        array $config,
        Validator $validator,
        RateLimiter $rateLimiter,
        AuditService $auditService,
        NotificationService $notificationService
    ) {
        $this->appDb = $appDb;
        $this->secureDb = $secureDb;
        $this->logger = $logger;
        $this->config = $config;
        $this->validator = $validator;
        $this->rateLimiter = $rateLimiter;
        $this->auditService = $auditService;
        $this->notificationService = $notificationService;
        $this->userService = new UserService();
    }

    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
        ];

        if (!$this->validator->validate($data, $rules)) {
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors()];
        }

        try {
            // Store user in secure database
            $stmt = $this->secureDb->prepare("
                INSERT INTO users (name, email, password_hash, phone, address, role, created_at)
                VALUES (:name, :email, :password, :phone, :address, 'user', NOW())
            ");
            $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                'phone' => $data['phone'],
                'address' => $data['address'],
            ]);

            $userId = $this->secureDb->lastInsertId();

            // Log action in secure database
            $this->auditService->log(
                'user_registered',
                'A new user has been registered.',
                $userId,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            // Send notification
            $this->notificationService->sendNotification(
                $userId,
                'email',
                'Welcome to Carfuse! Your account has been created successfully.',
                ['email' => $data['email']]
            );

            return ['status' => 'success', 'message' => 'Registration successful'];
        } catch (\PDOException $e) {
            $this->logger->error('Registration failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Registration failed'];
        }
    }

    /**
     * Authenticate user and generate JWT token
     */
    public function login(string $email, string $password, string $ip): array
    {
        if ($this->rateLimiter->isRateLimited($ip)) {
            return ['status' => 'error', 'message' => 'Too many attempts'];
        }

        // Fetch user from secure database
        $stmt = $this->secureDb->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->auditService->log(
                'failed_login_attempt',
                'Failed login attempt.',
                null,
                null,
                $ip
            );
            return ['status' => 'error', 'message' => 'Invalid credentials'];
        }

        $token = $this->generateJWT($user);

        $this->auditService->log(
            'user_logged_in',
            'User logged in successfully.',
            $user['id'],
            null,
            $ip
        );

        return ['status' => 'success', 'token' => $token];
    }

    /**
     * Update user profile
     */
    public function updateProfile(int $userId, array $data): array
    {
        $rules = [
            'name' => 'string|max:255',
            'phone' => 'string|max:20',
            'address' => 'string|max:255',
        ];

        if (!$this->validator->validate($data, $rules)) {
            return ['status' => 'error', 'message' => 'Validation failed', 'errors' => $this->validator->errors()];
        }

        try {
            $allowedFields = array_keys($rules);
            $updates = array_intersect_key($data, array_flip($allowedFields));
            $sql = "UPDATE users SET " . implode(', ', array_map(fn($k) => "$k = :$k", array_keys($updates))) . " WHERE id = :id";

            $stmt = $this->secureDb->prepare($sql);
            $stmt->execute([...$updates, 'id' => $userId]);

            $this->auditService->log(
                'user_profile_updated',
                'User profile updated.',
                $userId,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            return ['status' => 'success', 'message' => 'Profile updated'];
        } catch (\PDOException $e) {
            $this->logger->error('Profile update failed', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Update failed'];
        }
    }

    /**
     * Generate password reset token
     */
    public function requestPasswordReset(string $email): array
    {
        $stmt = $this->secureDb->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['status' => 'error', 'message' => 'Email not found'];
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $this->secureDb->prepare("
            INSERT INTO password_resets (email, token, expires_at) 
            VALUES (:email, :token, :expires)
        ");
        $stmt->execute([
            'email' => $email,
            'token' => $token,
            'expires' => $expires,
        ]);

        // Send password reset notification
        $this->notificationService->sendNotification(
            $user['id'],
            'email',
            "Use this link to reset your password: {$this->config['reset_url']}?token=$token",
            ['email' => $email]
        );

        return ['status' => 'success', 'message' => 'Reset instructions sent'];
    }

    private function generateJWT(array $user): string
    {
        $payload = [
            'sub' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        return JWT::encode($payload, $this->config['jwt_secret'], 'HS256');
    }

    public function viewProfile(int $userId)
    {
        try {
            $user = $this->getUserById($userId);
            view('user/profile', ['user' => $user]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load user profile', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo 'Failed to load user profile.';
        }
    }

    public function editProfileView(int $userId)
    {
        try {
            $user = $this->getUserById($userId);
            view('user/edit_profile', ['user' => $user]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load edit profile view', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo 'Failed to load edit profile view.';
        }
    }

    public function userDashboard()
    {
        view('dashboard/user_dashboard');
    }

    // Retrieve user profile and return JSON response
    public function getProfile($request) {
        header('Content-Type: application/json');
        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                throw new Exception("User not authenticated");
            }
            $profile = $this->userService->getProfileById($userId);
            echo json_encode([
                'status' => 'success',
                'data' => $profile
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    // Update user profile with validation and log updates
    public function updateProfile($request) {
        header('Content-Type: application/json');
        try {
            $data = $request->getParsedBody(); // ...existing code...
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                throw new Exception("User not authenticated");
            }
            // Basic validation
            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address");
            }
            if (empty($data['name'])) {
                throw new Exception("Name is required");
            }
            // ...additional validation as needed...

            $result = $this->userService->updateProfile($userId, $data);
            if (!$result) {
                throw new Exception("Profile update failed");
            }
            // Log the profile update
            $logMessage = sprintf("[%s] User ID %s: Profile updated\n", date('Y-m-d H:i:s'), $userId);
            file_put_contents(__DIR__ . '/../../logs/user.log', $logMessage, FILE_APPEND);

            echo json_encode([
                'status' => 'success',
                'message' => 'Profile updated successfully'
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
