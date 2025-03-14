<?php

namespace App\Controllers;

use App\Services\Auth\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use App\Services\Auth\TokenService;
use App\Services\RateLimiter;
use App\Helpers\ExceptionHandler;

class AuthController extends Controller
{
    protected LoggerInterface $logger;
    private AuthService $authService;
    private TokenService $tokenService;
    private RateLimiter $rateLimiter;
    protected ExceptionHandler $exceptionHandler;

    public function __construct(
        LoggerInterface $logger,
        AuthService $authService,
        TokenService $tokenService,
        RateLimiter $rateLimiter,
        ExceptionHandler $exceptionHandler
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->authService = $authService;
        $this->tokenService = $tokenService;
        $this->rateLimiter = $rateLimiter;
        $this->exceptionHandler = $exceptionHandler;
    }    

    public function login(Request $request, Response $response)
    {
        try {
            // Rewind the request body stream in case it was consumed
            $request->getBody()->rewind();
            
            // Use getParsedBody() for consistency with other endpoints
            $data = $request->getParsedBody();
            
            if (!is_array($data)) {
                $this->logger->error("Parsed body is not an array or is null in login.");
                return $this->jsonResponse($response, ["error" => "Invalid JSON input"], 400);
            }

            $this->logger->debug("Parsed request data in login: " . print_r($data, true));

            if (!isset($data['email']) || !isset($data['password'])) {
                $this->logger->warning("Missing required fields in login");
                return $this->jsonResponse($response, ["error" => "Email and password are required"], 400);
            }

            // Rate Limiting Logic
            $email = $data['email'];
            $ipAddress = $request->getServerParams()['REMOTE_ADDR'] ?? 'UNKNOWN';

        
            if ($this->rateLimiter->isRateLimited($email, $ipAddress, 'login')) {
                $this->logger->warning("Rate limit exceeded for login", ['email' => $email, 'ip' => $ipAddress]);
                return $this->jsonResponse($response, ["error" => "Too many login attempts. Please try again later."], 429);
            }

            $result = $this->authService->login($data);
            $this->logger->info('User login successful', ['email' => $data['email']]);
            
            // Set JWT token as a secure HttpOnly cookie
            setcookie('jwt', $result['token'], [
                'expires'  => time() + 3600,
                'path'     => '/',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            
            // Set refresh token as a secure HttpOnly cookie with longer expiration
            setcookie('refresh_token', $result['refresh_token'], [
                'expires'  => time() + 604800,
                'path'     => '/',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            
            // Return success message without exposing tokens in the response body
            return $this->jsonResponse($response, [
                "message" => "Login successful",
                "user_id" => $result['user_id'] ?? null,
                "name" => $result['name'] ?? null
            ]);
            
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse($response, ["error" => "Authentication failed"], 401);
        }
    }

    public function register(Request $request, Response $response)
    {
        try {
            // Use getParsedBody() since the parsed body was set in index.php
            $data = $request->getParsedBody();
            
            if (!is_array($data)) {
                $this->logger->error("Parsed body is not an array or is null.");
                return $this->jsonResponse($response, ["error" => "Invalid JSON input"], 400);
            }

            $this->logger->debug("Parsed request data in register: " . print_r($data, true));

            $requiredFields = ['name', 'surname', 'email', 'password'];
            $missingFields = array_diff($requiredFields, array_keys($data));

            if (!empty($missingFields)) {
                $this->logger->warning("Missing required fields in register: " . implode(', ', $missingFields));
                return $this->jsonResponse($response, ["error" => "Missing fields: " . implode(', ', $missingFields)], 400);
            }

            $result = $this->authService->register($data);
            $this->logger->info('User registration attempt', ['data' => $data]);

            return $this->jsonResponse($response, $result);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse($response, ["error" => "Registration failed"], 500);
        }
    }

    public function refresh(Request $request, Response $response)
    {
        try {
            // Try to get refresh token from cookie first
            $refreshToken = $_COOKIE['refresh_token'] ?? null;
            
            // If not in cookie, try to get from request body
            if (!$refreshToken) {
                $request->getBody()->rewind();
                $data = $request->getParsedBody();
                $refreshToken = $data['refresh_token'] ?? null;
            }
            
            if (!$refreshToken) {
                $this->logger->warning('Refresh token missing');
                return $this->jsonResponse($response, ["error" => "Refresh token is required"], 400);
            }
            
            $result = $this->authService->refresh(['refresh_token' => $refreshToken]);
            $this->logger->info('Token refreshed successfully');
            
            // Set the new JWT token as a cookie
            setcookie('jwt', $result['token'], [
                'expires'  => time() + 3600,
                'path'     => '/',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            
            return $this->jsonResponse($response, ["message" => "Token refreshed successfully"]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse($response, ["error" => "Token refresh failed"], 401);
        }
    }

    public function logout(Request $request, Response $response)
    {
        try {
            // Clear both JWT and refresh token cookies
            setcookie('jwt', '', [
                'expires'  => time() - 3600, // Expire in the past
                'path'     => '/',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            
            setcookie('refresh_token', '', [
                'expires'  => time() - 3600, // Expire in the past
                'path'     => '/',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            
            // Log the logout action
            $this->logger->info('User logged out successfully');
            
            // Call the service logout method if needed (e.g., to revoke tokens server-side)
            $this->authService->logout([]);
            
            return $this->jsonResponse($response, ["message" => "Logout successful"]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse($response, ["error" => "Logout failed"], 500);
        }
    }

    /**
     * Get authenticated user details
     * 
     * This endpoint assumes AuthMiddleware is applied to the route.
     * For protected routes, use AuthMiddleware with required=true.
     */
    public function userDetails(Request $request, Response $response): Response
    {
        try {
            $user = $request->getAttribute('user');
            
            if (!$user) {
                $this->logger->error("User not authenticated");
                return $this->jsonResponse($response->withStatus(401), ['error' => 'Authentication required']);
            }
            
            // Remove sensitive fields
            $userDetails = array_diff_key($user, array_flip(['password_hash']));
            
            $this->logger->info("User details retrieved successfully", ['user_id' => $user['id']]);
            return $this->jsonResponse($response, ['user' => $userDetails]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse($response, ["error" => "Failed to get user details"], 500);
        }
    }

    public function resetPasswordRequest(Request $request, Response $response)
    {
        try {
            $request->getBody()->rewind();
            $data = $request->getParsedBody();
            
            if (!is_array($data)) {
                $this->logger->error("Invalid JSON input for password reset request");
                return $this->jsonResponse($response, ["error" => "Invalid JSON input"], 400);
            }
            
            if (!isset($data['email'])) {
                $this->logger->warning("Missing email in password reset request");
                return $this->jsonResponse($response, ["error" => "Email is required"], 400);
            }
            
            $result = $this->authService->resetPasswordRequest($data);
            $this->logger->info("Password reset requested", ['email' => $data['email']]);
            return $this->jsonResponse($response, ["message" => "Password reset email sent"]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse($response, ["error" => "Password reset request failed"], 500);
        }
    }

    public function resetPassword(Request $request, Response $response)
    {
        try {
            $request->getBody()->rewind();
            $data = $request->getParsedBody();
            
            if (!is_array($data)) {
                $this->logger->error("Invalid JSON input for password reset");
                return $this->jsonResponse($response, ["error" => "Invalid JSON input"], 400);
            }
            
            // Validate required fields
            $requiredFields = ['token', 'password', 'confirm_password'];
            $missingFields = array_diff($requiredFields, array_keys($data));
            
            if (!empty($missingFields)) {
                $this->logger->warning("Missing fields in password reset", ['missing' => $missingFields]);
                return $this->jsonResponse($response, [
                    "error" => "Missing required fields: " . implode(', ', $missingFields)
                ], 400);
            }
            
            // Check if passwords match
            if ($data['password'] !== $data['confirm_password']) {
                $this->logger->warning("Password mismatch in reset");
                return $this->jsonResponse($response, ["error" => "Passwords do not match"], 400);
            }
            
            $result = $this->authService->resetPassword($data);
            $this->logger->info("Password reset completed successfully");
            return $this->jsonResponse($response, ["message" => "Password has been reset successfully"]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return $this->jsonResponse($response, ["error" => "Password reset failed"], 500);
        }
    }
}
