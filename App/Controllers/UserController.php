<?php

namespace App\Controllers;

use App\Models\User;
use ApiHelper;
use Validator;
use TokenService;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * User Management Controller
 *
 * Handles profile management, password resets, and dashboard access.
 */
class UserController extends Controller
{
    private Validator $validator;
    private TokenService $tokenService;
    private ExceptionHandler $exceptionHandler;
    private LoggerInterface $userLogger;
    private LoggerInterface $authLogger;
    private LoggerInterface $auditLogger;

    public function __construct(
        Validator $validator,
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler,
        LoggerInterface $userLogger,
        LoggerInterface $authLogger,
        LoggerInterface $auditLogger
    ) {
        $this->validator = $validator;
        $this->tokenService = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
        $this->userLogger = $userLogger;
        $this->authLogger = $authLogger;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Register a new user.
     */
    public function registerUser()
    {
        $data = $_POST;
        // Validate input data
        $rules = [
            'email'    => 'required|email',
            'password' => 'required|min:6',
            // ... other rules ...
        ];
        if (!$this->validator->validate($data, $rules)) {
            return ApiHelper::sendJsonResponse('error', 'Validation failed', $this->validator->errors(), 400);
        }
        try {
            $user = User::create($data);
            if (!$user) {
                throw new \Exception("User registration failed");
            }
            $this->userLogger->info("User registered successfully", ['email' => $data['email']]);
            return ApiHelper::sendJsonResponse('success', 'User registered successfully', ['user_id' => $user->id], 201);
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
        if (!$email || !$password) {
            return ApiHelper::sendJsonResponse('error', 'Email and password required', null, 400);
        }
        try {
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
            $token = $this->tokenService->generateToken($user);
            $refreshToken = $this->tokenService->generateRefreshToken($user);
            $this->authLogger->info("User logged in", ['userId' => $user->id, 'email' => $user->email]);
            return ApiHelper::sendJsonResponse('success', 'User logged in', [
                'token'         => $token,
                'refresh_token' => $refreshToken
            ], 200);
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
        $this->authLogger->info("User logged out");
        return ApiHelper::sendJsonResponse('success', 'Logged out successfully', null, 200);
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
        return ApiHelper::sendJsonResponse('success', 'User profile retrieved', $user, 200);
    }

    /**
     * 🔹 Update user profile
     */
    public function updateProfile()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return ApiHelper::sendJsonResponse('error', 'Unauthorized', null, 401);
        }
        $data = [
            'name'    => $_POST['name'] ?? null,
            'surname' => $_POST['surname'] ?? null,
            'email'   => $_POST['email'] ?? null,
            'phone'   => $_POST['phone'] ?? null,
            'address' => $_POST['address'] ?? null,
        ];
        $rules = [
            'name'    => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email'   => 'required|email',
            'phone'   => 'nullable|string|max:15',
            'address' => 'nullable|string|max:255',
        ];
        if (!$this->validator->validate($data, $rules)) {
            return ApiHelper::sendJsonResponse('error', 'Validation failed', $this->validator->errors(), 400);
        }
        try {
            $user->update($data);
            $this->auditLogger->info("User profile updated", ['userId' => $user->id]);
            return ApiHelper::sendJsonResponse('success', 'Profile updated successfully', null, 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * 🔹 Get user profile
     */
    public function getProfile()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $user = $_SESSION['user'] ?? null;
        return ApiHelper::sendJsonResponse('success', 'User profile', $user, 200);
    }

    /**
     * 🔹 Request password reset
     */
    public function requestPasswordReset()
    {
        $email = $_POST['email'] ?? null;
        if (!$email) {
            return ApiHelper::sendJsonResponse('error', 'Invalid input', null, 400);
        }
        try {
            $token = Str::random(60);
            \App\Models\PasswordReset::create([
                'email'      => $email,
                'token'      => $token,
                'expires_at' => now()->addHour(),
            ]);
            return ApiHelper::sendJsonResponse('success', 'Password reset requested', null, 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * 🔹 User dashboard access
     */
    public function userDashboard()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Rendering HTML for dashboard via ApiHelper response
        $html = "<html><body><h1>User Dashboard</h1><!-- ...existing dashboard HTML... --></body></html>";
        return ApiHelper::sendJsonResponse('success', 'User Dashboard', $html, 200);
    }
}
