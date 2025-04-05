<?php

namespace App\Controllers;

use App\Helpers\ApiHelper;
use App\Helpers\ExceptionHandler;
use App\Services\Validator;
use App\Services\Auth\TokenService;
use App\Services\AuditService;
use App\Services\Auth\AuthService;
use App\Models\User; // Inject User model
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * User Management Controller
 *
 * Handles profile management, password resets, and dashboard access.
 */
class UserController extends Controller
{
    private Validator $validator;
    private TokenService $tokenService;
    protected ExceptionHandler $exceptionHandler;
    protected LoggerInterface $logger;
    private AuthService $authService;
    private AuditService $auditService;
    private User $userModel; // Added User model

    public function __construct(
        LoggerInterface $logger,
        Validator $validator,
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler,
        AuthService $authService,
        AuditService $auditService,
        User $userModel // Injected dependency
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->validator     = $validator;
        $this->tokenService  = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
        $this->authService   = $authService;
        $this->auditService  = $auditService;
        $this->userModel     = $userModel;
    }

    /**
     * Register a new user.
     */
    public function registerUser(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            $this->logger->info("Processing user registration", ['email' => $data['email'] ?? 'not provided']);

            $rules = [
                'email'    => 'required|email',
                'password' => 'required|min:6',
                'name'     => 'required|string',
            ];
            $this->validator->validate($data, $rules);

            // Check if email is already in use via User model
            if ($this->userModel->findByEmail($data['email'])) {
                return ApiHelper::sendJsonResponse('error', 'Email already in use', [], 400);
            }
            
            // Prepare and create new user (User model handles password hashing and timestamps)
            $data['role'] = 'user';
            $userId = $this->userModel->createWithDefaultRole($data);
            
            // Removed direct audit logging – User model handles logging after creation
            
            $this->logger->info("User registered successfully", [
                'user_id' => $userId,
                'email' => $data['email']
            ]);
            return ApiHelper::sendJsonResponse('success', 'User registered successfully', ['user_id' => $userId], 201);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Registration failed', [], 500);
        }
    }

    /**
     * Retrieve current user profile.
     */
    public function getUserProfile(Request $request, Response $response)
    {
        try {
            $user = $this->tokenService->validateRequest($request);
            if (!$user) {
                return ApiHelper::sendJsonResponse('error', 'User not authenticated', [], 401);
            }
            $userId = $user['id'];
            $this->logger->info("Fetching user profile", ['user_id' => $userId]);

            // Fetch user profile using the model
            $userData = $this->userModel->find($userId);
            if (!$userData) {
                return ApiHelper::sendJsonResponse('error', 'User not found', [], 404);
            }
            
            // Log profile view via AuditService if needed
            $this->auditService->logEvent('profile_viewed', 'User viewed their profile', ['user_id' => $userId], $userId, null, 'user');

            return ApiHelper::sendJsonResponse('success', 'User profile retrieved', $userData, 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Failed to retrieve profile', [], 500);
        }
    }

    /**
     * Update user profile.
     */
    public function updateUserProfile(Request $request, Response $response)
    {
        try {
            $user = $this->tokenService->validateRequest($request);
            if (!$user) {
                return ApiHelper::sendJsonResponse('error', 'User not authenticated', [], 401);
            }
            $userId = $user['id'];

            $data = json_decode($request->getBody()->getContents(), true);
            $this->logger->info("Updating user profile", ['user_id' => $userId]);

            $rules = [
                'name'       => 'string|max:100',
                'bio'        => 'string|max:500',
                'location'   => 'string|max:100',
                'avatar_url' => 'url|max:255'
            ];
            $this->validator->validate($data, $rules);

            // Delegate profile update to the User model logic
            $this->userModel->updateProfile($userId, $data);

            $this->auditService->logEvent('profile_updated', 'User updated their profile', ['user_id' => $userId, 'fields_updated' => array_keys($data)], $userId, null, 'user');

            // Retrieve updated profile
            $updatedProfile = $this->userModel->find($userId);
            return ApiHelper::sendJsonResponse('success', 'Profile updated successfully', $updatedProfile, 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Failed to update profile', [], 500);
        }
    }

    /**
     * Request password reset.
     */
    public function requestPasswordReset(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ApiHelper::sendJsonResponse('error', 'Valid email is required', [], 400);
            }
            
            $this->logger->info("Processing password reset request", ['email' => $data['email']]);
            $user = $this->userModel->findByEmail($data['email']);
            if (!$user) {
                $this->logger->info("Password reset requested for non-existent email", ['email' => $data['email']]);
                return ApiHelper::sendJsonResponse('success', 'If your email is in our system, you will receive reset instructions shortly', [], 200);
            }
            
            $token    = bin2hex(random_bytes(30));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

            // Use model method to create reset token
            $this->userModel->createPasswordReset($data['email'], $token, $ipAddress, $expiresAt);
            $this->auditService->logEvent('password_reset_requested', 'Password reset requested', ['email' => $data['email'], 'expires_at' => $expiresAt], $user['id'], null, 'user');
            
            return ApiHelper::sendJsonResponse('success', 'Password reset instructions sent to your email', [], 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Failed to process password reset request', [], 500);
        }
    }

    /**
     * Reset password with token.
     */
    public function resetPassword(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            if (!isset($data['token']) || !isset($data['password']) || strlen($data['password']) < 6) {
                return ApiHelper::sendJsonResponse('error', 'Token and password (min 6 chars) required', [], 400);
            }
            
            $this->logger->info("Processing password reset with token");
            $tokenData = $this->userModel->verifyResetToken($data['token']);
            if (!$tokenData) {
                $this->logger->warning("Invalid or expired password reset token used");
                return ApiHelper::sendJsonResponse('error', 'Invalid or expired token', [], 400);
            }
            $email = $tokenData['email'];
            $user = $this->userModel->findByEmail($email);
            if (!$user) {
                $this->logger->warning("User not found for password reset", ['email' => $email]);
                return ApiHelper::sendJsonResponse('error', 'User not found', [], 404);
            }
            
            // Update user's password using model method
            $this->userModel->updatePassword($user['id'], $data['password']);
            // Optionally, mark the token as used if desired (User model provides markResetTokenUsed)
            
            $this->logger->info("Password reset successful", [
                'user_id' => $user['id'],
                'email' => $email
            ]);
            return ApiHelper::sendJsonResponse('success', 'Password has been reset successfully', [], 200);
        } catch (\Exception $e) {
            $this->logger->error("Failed to reset password", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ApiHelper::sendJsonResponse('error', 'Failed to reset password', [], 500);
        }
    }

    /**
     * User dashboard access.
     */
    public function userDashboard(Request $request, Response $response)
    {
        try {
            $user = $this->tokenService->validateRequest($request);
            if (!$user) {
                return ApiHelper::sendJsonResponse('error', 'User not authenticated', [], 401);
            }

            $userId = $user['id'];
            $this->logger->info("User accessing dashboard", ['user_id' => $userId]);

            // Replace direct DB calls with model methods
            $userData = $this->userModel->find($userId);
            if (!$userData) {
                return ApiHelper::sendJsonResponse('error', 'User not found', [], 404);
            }

            // Get recent activity from model
            $recentActivity = $this->userModel->getRecentActivity($userId, 5);

            // Log activity via model
            $this->userModel->logActivity($userId, 'dashboard_access', 'User accessed their dashboard', $_SERVER['REMOTE_ADDR'] ?? null);

            $dashboardData = [
                'user' => $userData,
                'recent_activity' => $recentActivity
            ];

            return ApiHelper::sendJsonResponse('success', 'User Dashboard', $dashboardData, 200);
        } catch (\Exception $e) {
            $this->logger->error("Failed to load user dashboard", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->getAttribute('user_id') ?? 'unknown'
            ]);
            return ApiHelper::sendJsonResponse('error', 'Failed to load dashboard', [], 500);
        }
    }

    /**
     * Display the user profile page.
     */
    public function showProfilePage(Request $request, Response $response)
    {
        try {
            // Check if user is authenticated via session
            if (!isset($_SESSION['user_id'])) {
                return $response->withHeader('Location', '/auth/login')->withStatus(302);
            }
            
            $userId = $_SESSION['user_id'];
            $this->logger->info("User accessing profile page", ['user_id' => $userId]);

            // Fetch user profile data
            $profile = $this->userModel->findOrFail($userId);
            
            // Prepare data for the view
            $profileData = [
                'id' => $profile->id,
                'name' => $profile->name,
                'email' => $profile->email,
                'bio' => $profile->bio ?? '',
                'phone' => $profile->phone ?? '',
                'location' => $profile->location ?? '',
                'avatar_url' => $profile->avatar_url ?? '/images/default-avatar.png',
                'joined_date' => (new \DateTime($profile->created_at))->format('d.m.Y'),
                'preferences' => $this->userModel->getUserPreferences($userId)
            ];
            
            // Log audit trail
            $this->auditService->logEvent('profile_page_viewed', 'User viewed their profile page', 
                ['user_id' => $userId], $userId, null, 'user');

            // Include the profile view
            include BASE_PATH . '/public/views/profile.php';
            return $response;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return $response->withHeader('Location', '/error')->withStatus(302);
        }
    }

    /**
     * Handle user profile update.
     */
    public function updateProfile(Request $request, Response $response)
    {
        try {
            // Check if user is authenticated
            if (!isset($_SESSION['user_id'])) {
                return ApiHelper::sendJsonResponse('error', 'Nie jesteś zalogowany', [], 401);
            }
            
            $userId = $_SESSION['user_id'];
            $this->logger->info("Processing profile update", ['user_id' => $userId]);
            
            // Get form data (handle both JSON and form submissions)
            $contentType = $request->getHeaderLine('Content-Type');
            if (strstr($contentType, 'application/json')) {
                $data = json_decode($request->getBody()->getContents(), true);
            } else {
                $data = $request->getParsedBody();
            }
            
            // Validate the input
            $rules = [
                'name'     => 'required|string|max:100',
                'bio'      => 'string|max:500',
                'phone'    => 'string|max:20',
                'location' => 'string|max:100'
            ];
            
            $validateResult = $this->validator->validate($data, $rules);
            if ($validateResult !== true) {
                return ApiHelper::sendJsonResponse('error', 'Niepoprawne dane formularza', ['errors' => $validateResult], 400);
            }
            
            // Handle file upload if present
            $avatarUrl = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $avatarUrl = $this->handleAvatarUpload($_FILES['avatar'], $userId);
                if (!$avatarUrl) {
                    return ApiHelper::sendJsonResponse('error', 'Nie udało się przesłać zdjęcia profilowego', [], 500);
                }
                $data['avatar_url'] = $avatarUrl;
            }
            
            // Handle avatar removal
            if (isset($data['remove_avatar']) && $data['remove_avatar'] === '1') {
                $data['avatar_url'] = null;
            }
            
            // Extract preferences
            $preferences = $data['preferences'] ?? [];
            unset($data['preferences']);
            
            // Update profile in database
            $updated = $this->userModel->updateProfile($userId, $data);
            
            // Update preferences
            if (!empty($preferences)) {
                $this->userModel->updatePreferences($userId, $preferences);
            }
            
            // Log the update
            $this->auditService->logEvent('profile_updated', 'User updated their profile', 
                ['user_id' => $userId, 'fields_updated' => array_keys($data)], $userId, null, 'user');
            
            // Return success response
            return ApiHelper::sendJsonResponse('success', 'Profil został zaktualizowany pomyślnie', [
                'user' => $this->userModel->find($userId)
            ], 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Wystąpił błąd podczas aktualizacji profilu', [], 500);
        }
    }
    
    /**
     * Handle profile picture upload.
     */
    private function handleAvatarUpload($file, $userId)
    {
        try {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            // Validate file
            if (!in_array($file['type'], $allowedTypes)) {
                throw new \Exception("Nieprawidłowy typ pliku. Dozwolone: JPG, PNG, GIF");
            }
            
            if ($file['size'] > $maxSize) {
                throw new \Exception("Plik jest za duży. Maksymalny rozmiar to 2MB");
            }
            
            // Create unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
            $uploadDir = BASE_PATH . '/public/uploads/avatars/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $uploadPath = $uploadDir . $filename;
            
            // Move the uploaded file
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new \Exception("Nie udało się zapisać pliku");
            }
            
            return '/uploads/avatars/' . $filename;
        } catch (\Exception $e) {
            $this->logger->error("Avatar upload error", [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return null;
        }
    }
    
    /**
     * Change user password.
     */
    public function changePassword(Request $request, Response $response)
    {
        try {
            // Verify authentication
            if (!isset($_SESSION['user_id'])) {
                return ApiHelper::sendJsonResponse('error', 'Nie jesteś zalogowany', [], 401);
            }
            
            $userId = $_SESSION['user_id'];
            $this->logger->info("Processing password change", ['user_id' => $userId]);
            
            // Get form data
            $data = $request->getParsedBody();
            
            // Validate input
            if (!isset($data['current_password']) || !isset($data['new_password']) || !isset($data['confirm_password'])) {
                return ApiHelper::sendJsonResponse('error', 'Wszystkie pola są wymagane', [], 400);
            }
            
            if ($data['new_password'] !== $data['confirm_password']) {
                return ApiHelper::sendJsonResponse('error', 'Hasła nie są zgodne', [], 400);
            }
            
            if (strlen($data['new_password']) < 6) {
                return ApiHelper::sendJsonResponse('error', 'Nowe hasło musi mieć co najmniej 6 znaków', [], 400);
            }
            
            // Verify current password
            $user = $this->userModel->find($userId);
            if (!$user || !$this->authService->verifyPassword($data['current_password'], $user->password)) {
                return ApiHelper::sendJsonResponse('error', 'Nieprawidłowe aktualne hasło', [], 400);
            }
            
            // Update the password
            $this->userModel->updatePassword($userId, $data['new_password']);
            
            // Log password change
            $this->auditService->logEvent('password_changed', 'User changed their password', 
                ['user_id' => $userId], $userId, null, 'security');
                
            // Return success response
            return ApiHelper::sendJsonResponse('success', 'Hasło zostało zmienione pomyślnie', [], 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Wystąpił błąd podczas zmiany hasła', [], 500);
        }
    }
}
