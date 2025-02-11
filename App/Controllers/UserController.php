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
        try {
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/',
                'phone' => 'required|string|max:20',
                'address' => 'required|string|max:255',
            ];

            if (!$this->validator->validate($data, $rules)) {
                http_response_code(400);
                echo json_encode(['status' => 'error','message' => 'Validation failed','data' => []]);
                exit;
            }

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

            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'Registration successful','data' => []]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Internal Server Error','data' => []]);
        }
        exit;
    }

    /**
     * Authenticate user and generate JWT token
     */
    public function login(string $email, string $password, string $ip): array
    {
        try {
            if ($this->rateLimiter->isRateLimited($ip)) {
                http_response_code(401);
                echo json_encode(['status' => 'error','message' => 'Too many requests','data' => []]);
                exit;
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
                http_response_code(401);
                echo json_encode(['status' => 'error','message' => 'Invalid credentials','data' => []]);
                exit;
            }

            $token = $this->generateJWT($user);

            $this->auditService->log(
                'user_logged_in',
                'User logged in successfully.',
                $user['id'],
                null,
                $ip
            );

            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'Login successful','data' => ['token' => $token]]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Internal Server Error','data' => []]);
        }
        exit;
    }

/**
 * Update user profile (API version)
 */
public function updateProfile($request) {
    header('Content-Type: application/json');

    try {
        // Replace session-based auth with JWT validation and CSRF protection
        $userId = validateJWT(); // Decodes JWT and returns user_id
        validateCSRFToken($request); // Validate CSRF token

        $data = $request->getParsedBody();

        // ✅ Input Validation (Ensure valid name, email, and phone)
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'string|max:20',
            'address' => 'string|max:255',
        ];

        if (!$this->validator->validate($data, $rules)) {
            throw new \Exception("Validation failed: Invalid input.");
        }

        // ✅ Delegate profile update to UserService
        $result = $this->userService->updateProfile($userId, $data);

        if (!$result) {
            throw new \Exception("Profile update failed.");
        }

        // ✅ Log the profile update
        $logMessage = sprintf("[%s] User ID %s: Profile updated\n", date('Y-m-d H:i:s'), $userId);
        file_put_contents(BASE_PATH . '/logs/user.log', $logMessage, FILE_APPEND);

        // ✅ Return JSON response
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => []
        ]);
    } catch (\Exception $e) {
        error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');

        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
            'data' => []
        ]);
    }
}


    /**
     * Generate password reset token
     */
    public function requestPasswordReset(string $email): array
    {
        try {
            $stmt = $this->secureDb->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                http_response_code(404);
                echo json_encode(['status' => 'error','message' => 'User not found','data' => []]);
                exit;
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

            http_response_code(200);
            echo json_encode(['status' => 'success','message' => 'Password reset token generated','data' => []]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            http_response_code(500);
            echo json_encode(['status' => 'error','message' => 'Internal Server Error','data' => []]);
        }
        exit;
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
            echo json_encode(['status' => 'error', 'message' => 'Failed to load user profile', 'data' => []]);
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
            echo json_encode(['status' => 'error', 'message' => 'Failed to load edit profile view', 'data' => []]);
        }
    }

    public function userDashboard()
    {
        try {
            // Enforce JWT authentication for protected endpoints
            validateJWT();
            view('dashboard/user_dashboard');
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Dashboard loaded', 'data' => []]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load user dashboard', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to load user dashboard', 'data' => []]);
        }
    }

    // Retrieve user profile and return JSON response
    public function getProfile($request) {
        header('Content-Type: application/json');
        try {
            // Replace requireAuth and session lookup with JWT validation
            $userId = validateJWT();
            $profile = $this->userService->getProfileById($userId);
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $profile
            ]);
        } catch (\Exception $e) {
            error_log(date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", 3, BASE_PATH . '/logs/api.log');
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }
}
