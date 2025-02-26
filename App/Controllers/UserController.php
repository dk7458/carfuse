<?php

namespace App\Controllers;

use App\Models\User;
use App\Helpers\ApiHelper;
use App\Services\Validator;
use App\Services\Auth\TokenService;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use App\Services\Auth\AuthService;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helpers\LoggingHelper;

/**
 * User Management Controller
 *
 * Handles profile management, password resets, and dashboard access.
 */
class UserController
{
    private Validator $validator;
    private TokenService $tokenService;
    private ExceptionHandler $exceptionHandler;
    private LoggerInterface $logger;
    private AuthService $authService;

    public function __construct(
        Validator $validator,
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler,
        AuthService $authService
    ) {
        $this->validator = $validator;
        $this->tokenService = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
        $this->logger = LoggingHelper::getLoggerByCategory('user');
        $this->authService = $authService;
    }

    /**
     * Register a new user.
     */
    public function registerUser(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);

        $rules = [
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'name'     => 'required|string',
        ];

        try {
            $this->validator->validate($data, $rules);
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            $user = User::create($data);
            $this->logger->info("User registered successfully", ['email' => $data['email']]);
            return ApiHelper::sendJsonResponse('success', 'User registered successfully', ['user_id' => $user->id], 201);
        } catch (\InvalidArgumentException $e) {
            return ApiHelper::sendJsonResponse('error', 'Validation failed', json_decode($e->getMessage(), true), 400);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * Retrieve current user profile.
     */
    public function getUserProfile(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        return $this->jsonResponse($response, $user);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $data = json_decode($request->getBody()->getContents(), true);
        $result = $this->authService->updateProfile($user, $data);
        return $this->jsonResponse($response, $result);
    }

    /**
     * Request password reset.
     */
    public function requestPasswordReset(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);

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
     * User dashboard access.
     */
    public function userDashboard(Request $request, Response $response)
    {
        // Rendering HTML for dashboard via ApiHelper response
        $html = "<html><body><h1>User Dashboard</h1><!-- ...existing dashboard HTML... --></body></html>";
        return ApiHelper::sendJsonResponse('success', 'User Dashboard', $html, 200);
    }
}
