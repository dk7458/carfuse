<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\UserService;
use App\Services\NotificationService;
// Removed: use App\Services\Validator;
// Removed: use Illuminate\Support\Facades\Auth;
// Removed: use Illuminate\Support\Facades\Log;
use App\Services\RateLimiter;
use App\Services\AuditService;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;

/**
 * User Management Controller
 *
 * Handles profile management, password resets, and dashboard access.
 */
class UserController extends Controller
{
    private UserService $userService;
    // private LoggerInterface $logger; // Using native error_log()
    // private Validator $validator;
    private RateLimiter $rateLimiter;
    private AuditService $auditService;
    private NotificationService $notificationService;

    public function __construct(
        /* Removed LoggerInterface, Validator, ... */
        RateLimiter $rateLimiter,
        AuditService $auditService,
        NotificationService $notificationService
    ) {
        $this->rateLimiter = $rateLimiter;
        $this->auditService = $auditService;
        $this->notificationService = $notificationService;
        $this->userService = new UserService();
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
