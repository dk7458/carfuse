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
use App\Services\Auth\AuthService;
use Exception;

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
    private AuthService $authService;

    public function __construct(
        Validator $validator,
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler,
        LoggerInterface $userLogger,
        LoggerInterface $authLogger,
        LoggerInterface $auditLogger,
        AuthService $authService
    ) {
        $this->validator = $validator;
        $this->tokenService = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
        $this->userLogger = $userLogger;
        $this->authLogger = $authLogger;
        $this->auditLogger = $auditLogger;
        $this->authService = $authService;
    }

    /**
     * Register a new user.
     */
    public function registerUser()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        // Validate input data
        $rules = [
            'email'    => 'required|email',
            'password' => 'required|min:6',
            'name'     => 'required|string',
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
     * Retrieve current user profile.
     */
    public function getUserProfile($request = null)
    {
        try {
            $token = $_COOKIE['jwt'] ?? '';
            if (!$this->authService->validateToken($token)) {
                throw new Exception("Invalid token.");
            }

            $userData = $this->authService->getUserFromToken($token);
            ApiHelper::sendJsonResponse('success', 'User profile fetched', $userData, 200);
        } catch (Exception $e) {
            ApiHelper::sendJsonResponse('error', $e->getMessage(), [], 401);
        }
    }

    /**
     * 🔹 Update user profile
     */
    public function updateProfile($request = null)
    {
        try {
            $token = $_COOKIE['jwt'] ?? '';
            if (!$this->authService->validateToken($token)) {
                throw new Exception("Invalid token.");
            }

            $userData = $this->authService->getUserFromToken($token);
            $data = json_decode(file_get_contents('php://input'), true);

            // Assume $this->authService->updateUserProfile($userData['id'], $data) exists
            $this->authService->updateUserProfile($userData['id'], $data);

            ApiHelper::sendJsonResponse('success', 'Profile updated successfully');
        } catch (Exception $e) {
            ApiHelper::sendJsonResponse('error', $e->getMessage(), [], 400);
        }
    }

    /**
     * 🔹 Request password reset
     */
    public function requestPasswordReset()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['email'])) {
            return ApiHelper::sendJsonResponse('error', 'Email is required', null, 400);
        }
        try {
            $token = Str::random(60);
            \App\Models\PasswordReset::create([
                'email'      => $data['email'],
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
        // Rendering HTML for dashboard via ApiHelper response
        $html = "<html><body><h1>User Dashboard</h1><!-- ...existing dashboard HTML... --></body></html>";
        return ApiHelper::sendJsonResponse('success', 'User Dashboard', $html, 200);
    }
}
