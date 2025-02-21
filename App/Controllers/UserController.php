<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\UserService;
use App\Services\NotificationService;
use App\Services\RateLimiter;
use App\Services\AuditService;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;
use App\Services\Auth\AuthService;
use App\Helpers\ExceptionHandler;
use function getLogger;

/**
 * User Management Controller
 *
 * Handles profile management, password resets, and dashboard access.
 */
class UserController extends Controller
{
    private UserService $userService;
    private RateLimiter $rateLimiter;
    private AuditService $auditService;
    private NotificationService $notificationService;
    private AuthService $authService;
    private ExceptionHandler $exceptionHandler;

    public function __construct(
        RateLimiter $rateLimiter,
        AuditService $auditService,
        NotificationService $notificationService,
        AuthService $authService
    ) {
        $this->rateLimiter = $rateLimiter;
        $this->auditService = $auditService;
        $this->notificationService = $notificationService;
        $this->userService = new UserService();
        $this->authService = $authService;
        $this->exceptionHandler = new ExceptionHandler(getLogger('auth'));
    }

    /**
     * Register a new user.
     */
    public function registerUser()
    {
        // Assume POST request with registration data
        $data = $_POST;
        try {
            // Move user registration logic here (bypassing AuthService)
            $user = User::create($data);
            if (!$user) {
                throw new \Exception("User registration failed");
            }
            getLogger('auth')->info("[UserController] User registered successfully (Email: {$data['email']})");
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'User registered successfully']);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * Log in an existing user.
     */
    public function login()
    {
        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;
        try {
            if (!$email || !$password) {
                throw new \Exception("Email and password required");
            }
            // Fetch user manually instead of within AuthService
            $user = User::where('email', $email)->first();
            if (!$user || !password_verify($password, $user->password_hash)) {
                throw new \Exception("Invalid credentials");
            }
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user->id;
            $_SESSION['user_role'] = $user->role ?? 'user';
            // Delegate token generation to AuthService
            $token = $this->authService->generateToken($user);
            $refreshToken = $this->authService->generateRefreshToken($user);
            getLogger('auth')->info("[UserController] User logged in: {$email}");
            header('Content-Type: application/json');
            echo json_encode([
                'status'        => 'success',
                'token'         => $token,
                'refresh_token' => $refreshToken
            ]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * Log out the current user.
     */
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();
        getLogger('auth')->info("[UserController] User logged out.");
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Logged out successfully']);
    }

    /**
     * Retrieve current user profile.
     */
    public function getUserProfile()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $user = User::find($_SESSION['user_id'] ?? null);
        header('Content-Type: application/json');
        echo json_encode($user);
    }

    /**
     * 🔹 Update user profile
     */
    public function updateProfile()
    {
        session_start();
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        // Custom extraction/validation from $_POST (assumes custom_validate() exists)
        $data = [
            'name'    => $_POST['name'] ?? null,
            'surname' => $_POST['surname'] ?? null,
            'email'   => $_POST['email'] ?? null,
            'phone'   => $_POST['phone'] ?? null,
            'address' => $_POST['address'] ?? null,
        ];
        // Assume custom_validate() throws an exception on failure.
        custom_validate($data, [
            'name'    => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email'   => 'required|email',
            'phone'   => 'nullable|string|max:15',
            'address' => 'nullable|string|max:255',
        ]);
        
        $user->update($data);
        error_log("Audit: User profile updated for user_id: {$user->id}");
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
        exit;
    }

    /**
     * 🔹 Get user profile
     */
    public function getProfile()
    {
        session_start();
        $user = $_SESSION['user'] ?? null;
        header('Content-Type: application/json');
        echo json_encode($user);
        exit;
    }

    /**
     * 🔹 Request password reset
     */
    public function requestPasswordReset()
    {
        $email = $_POST['email'] ?? null;
        if (!$email) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
            exit;
        }
        
        $token = Str::random(60);
        \App\Models\PasswordReset::create([
            'email'      => $email,
            'token'      => $token,
            'expires_at' => now()->addHour(),
        ]);
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Password reset requested']);
        exit;
    }

    /**
     * 🔹 User dashboard access
     */
    public function userDashboard()
    {
        session_start();
        // Instead of Laravel view(), use native PHP rendering or simple HTML output.
        header('Content-Type: text/html');
        echo "<html><body><h1>User Dashboard</h1><!-- ...existing dashboard HTML... --></body></html>";
        exit;
    }
}
